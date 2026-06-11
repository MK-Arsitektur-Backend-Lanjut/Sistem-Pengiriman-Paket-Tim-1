<?php

namespace App\Repositories\Eloquent;

use App\Models\Fleet;
use App\Models\FleetLog;
use App\Models\Hub;
use App\Models\Package;
use App\Models\Warehouse;
use App\Repositories\Contracts\FleetRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FleetRepository implements FleetRepositoryInterface
{
    private const FLEET_PAGINATION_SIZE = 15;

    public function getAllFleets($search = null, $status = null, $hubId = null)
    {
        $page = request()->query('page', 1);
        $key = CacheService::keyFleetList($search ?? '', $status ?? '', $hubId ?? '') . ':page:' . $page;
        
        return CacheService::remember($key, function () use ($search, $status, $hubId) {
            return $this->buildFleetQuery($search, $status, $hubId)
                ->paginate(self::FLEET_PAGINATION_SIZE)
                ->withQueryString();
        }, CacheService::TTL_SHORT, [CacheService::TAG_FLEET]);
    }

    private function buildFleetQuery($search, $status, $hubId): Builder
    {
        $query = Fleet::with('currentHub')->latest();

        $this->applyFleetSearch($query, $search);

        if ($status) {
            $query->where('status', $status);
        }

        if ($hubId) {
            $query->where('current_hub_id', $hubId);
        }

        return $query;
    }

    public function getFleetById($id)
    {
        return Fleet::with(['currentHub', 'logs.originHub', 'logs.destinationHub'])->findOrFail($id);
    }

    public function getMissingPackageIds(array $packageIds): array
    {
        $requestedIds = collect($packageIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($requestedIds->isEmpty()) {
            return [];
        }

        $existingIds = Package::query()
            ->whereIn('id', $requestedIds)
            ->pluck('id');

        return $requestedIds
            ->diff($existingIds)
            ->values()
            ->all();
    }

    public function calculateTransitDuration($fleetId)
    {
        $key = 'fleets:transit_duration:' . $fleetId;
        
        return CacheService::remember($key, function () use ($fleetId) {
            $fleet = Fleet::with('currentHub')->findOrFail($fleetId);

            $allLogs = $this->getFleetLogs((int) $fleetId);
            $completedLogs = $this->filterCompletedTransitLogs($allLogs);
            $history = $this->mapTransitHistory($completedLogs, (string) $fleet->plate_number);
            $summary = $this->buildTransitSummary($allLogs, $history, $completedLogs);

            return [
                // Legacy keys kept for existing frontend compatibility.
                'fleet_id' => $fleet->id,
                'average_duration_hours' => $summary['average_duration_hours'],
                'history' => $history,

                'fleet' => $this->formatFleetDetails($fleet),
                'summary' => $summary,
                'route_stats' => $this->buildRouteStats($history),
            ];
        }, CacheService::TTL_MEDIUM, [CacheService::TAG_FLEET]);
    }

    public function calculateLoadPlan(
        int $fleetId,
        array $packageIds = [],
        array $packageQuantities = [],
        string $strategy = 'maximize_count',
        bool $includeBreakdown = false
    ): array {
        $fleet = Fleet::findOrFail($fleetId);
        $capacityKg = (float) $fleet->capacity;

        $quantities = [];
        foreach ($packageIds as $id) {
            if ((int)$id > 0) $quantities[(int)$id] = ($quantities[(int)$id] ?? 0) + 1;
        }
        foreach ($packageQuantities as $id => $qty) {
            if ((int)$id > 0 && (int)$qty > 0) $quantities[(int)$id] = ($quantities[(int)$id] ?? 0) + (int)$qty;
        }

        $packages = Package::whereIn('id', array_keys($quantities))->get();
        $volumetricDivisor = $this->getVolumetricDivisorByFleetType((string) $fleet->type);

        $totalChargeableWeight = 0;
        $totalPackages = 0;

        foreach ($packages as $package) {
            $qty = $quantities[$package->id] ?? 0;
            if ($qty <= 0) continue;

            $volumeCm3 = (float) ($package->volume ?? ((float) $package->length * (float) $package->width * (float) $package->height));
            $actualWeightKg = (float) $package->weight;
            $volumetricWeightKg = round($volumeCm3 / $volumetricDivisor, 2);
            $chargeableWeightKg = round(max($actualWeightKg, $volumetricWeightKg), 2);

            $totalPackages += $qty;
            $totalChargeableWeight += $chargeableWeightKg * $qty;
        }

        return [
            'fleet' => [
                'id' => $fleet->id,
                'plate_number' => $fleet->plate_number,
                'capacity_kg' => $capacityKg,
            ],
            'summary' => [
                'total_packages' => $totalPackages,
                'total_chargeable_weight_kg' => round($totalChargeableWeight, 2),
                'fleet_capacity_kg' => $capacityKg,
                'can_fit_all_packages' => $totalChargeableWeight <= $capacityKg,
                'over_capacity_kg' => round(max(0, $totalChargeableWeight - $capacityKg), 2),
            ],
        ];
    }

    public function storeFleet(array $data)
    {
        $fleet = Fleet::create($data);
        CacheService::flushTag(CacheService::TAG_FLEET);
        return $fleet;
    }

    public function updateFleetStatus($id, $status)
    {
        $fleet = Fleet::findOrFail($id);
        $oldStatus = (string) $fleet->status;
        $newStatus = (string) $status;

        if ($oldStatus === $newStatus) {
            return $fleet;
        }

        $fleet->status = $newStatus;
        $fleet->save();

        $this->syncStatusLoadTransition($fleet, $oldStatus, $newStatus);
        
        CacheService::flushTag(CacheService::TAG_FLEET);

        return $fleet;
    }

    public function relocateFleet($id, $newHubId)
    {
        $fleet = Fleet::findOrFail($id);

        $oldHubId = $fleet->current_hub_id;
        $destinationHubId = (int) $newHubId;

        if ((int) $oldHubId === $destinationHubId) {
            return $fleet;
        }

        // Logic error fix: idle/maintenance fleets are EMPTY. 
        // Relocating them should NOT transfer any "ghost load" between warehouses.
        // We only update the hub location.
        $fleet->current_hub_id = $destinationHubId;
        
        if ($fleet->status === 'in_transit') {
            $fleet->status = 'idle';
        }
        $fleet->save();

        $this->logFleetRelocation($fleet, (int) ($oldHubId ?: $destinationHubId), $destinationHubId);

        CacheService::flushTag(CacheService::TAG_FLEET);

        return $fleet;
    }

    private function applyFleetSearch(Builder $query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function (Builder $builder) use ($search) {
            $builder->where('plate_number', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%");
        });
    }

    private function getFleetLogs(int $fleetId): Collection
    {
        return FleetLog::with(['originHub:id,name', 'destinationHub:id,name'])
            ->where('fleet_id', $fleetId)
            ->orderByDesc('departed_at')
            ->orderByDesc('id')
            ->get();
    }

    private function filterCompletedTransitLogs(Collection $logs): Collection
    {
        return $logs
            ->filter(fn (FleetLog $log): bool => !is_null($log->departed_at) && !is_null($log->arrived_at))
            ->values();
    }

    private function mapTransitHistory(Collection $completedLogs, string $plateNumber): Collection
    {
        return $completedLogs->map(function (FleetLog $log) use ($plateNumber) {
            $departed = Carbon::parse($log->departed_at);
            $arrived = Carbon::parse($log->arrived_at);

            return [
                'log_id' => $log->id,
                'plate_number' => $plateNumber,
                'status' => $log->status,
                'origin_hub_id' => $log->origin_hub_id,
                'origin_hub_name' => $log->originHub?->name,
                'destination_hub_id' => $log->destination_hub_id,
                'destination_hub_name' => $log->destinationHub?->name,
                'departed_at' => $log->departed_at,
                'arrived_at' => $log->arrived_at,
                'duration_hours' => round($departed->diffInMinutes($arrived) / 60, 2),
            ];
        })->values();
    }

    private function buildRouteStats(Collection $history): Collection
    {
        return $history
            ->groupBy(fn (array $item): string => $item['origin_hub_id'] . '-' . $item['destination_hub_id'])
            ->map(function (Collection $items) {
                $first = $items->first();
                $movementCount = $items->count();

                return [
                    'origin_hub_id' => $first['origin_hub_id'],
                    'origin_hub_name' => $first['origin_hub_name'],
                    'destination_hub_id' => $first['destination_hub_id'],
                    'destination_hub_name' => $first['destination_hub_name'],
                    'movement_count' => $movementCount,
                    'average_duration_hours' => $movementCount > 0 ? round((float) $items->avg('duration_hours'), 2) : 0.0,
                    'total_duration_hours' => round((float) $items->sum('duration_hours'), 2),
                ];
            })
            ->sortByDesc('movement_count')
            ->values();
    }

    private function buildTransitSummary(Collection $allLogs, Collection $history, Collection $completedLogs): array
    {
        $completedMovements = $history->count();

        return [
            'total_movements' => $allLogs->count(),
            'completed_movements' => $completedMovements,
            'ongoing_movements' => $allLogs->whereNotNull('departed_at')->whereNull('arrived_at')->count(),
            'average_duration_hours' => $completedMovements > 0 ? round((float) $history->avg('duration_hours'), 2) : 0.0,
            'total_duration_hours' => round((float) $history->sum('duration_hours'), 2),
            'fastest_duration_hours' => $completedMovements > 0 ? round((float) $history->min('duration_hours'), 2) : null,
            'slowest_duration_hours' => $completedMovements > 0 ? round((float) $history->max('duration_hours'), 2) : null,
            'first_departure_at' => $completedLogs->min('departed_at'),
            'last_arrival_at' => $completedLogs->max('arrived_at'),
            'status_breakdown' => $allLogs->groupBy('status')->map(fn (Collection $logs) => $logs->count())->toArray(),
        ];
    }

    private function formatFleetDetails(Fleet $fleet): array
    {
        return [
            'id' => $fleet->id,
            'plate_number' => $fleet->plate_number,
            'type' => $fleet->type,
            'status' => $fleet->status,
            'capacity' => $fleet->capacity,
            'current_hub' => [
                'id' => $fleet->currentHub?->id,
                'name' => $fleet->currentHub?->name,
            ],
        ];
    }



    private function syncStatusLoadTransition(Fleet $fleet, string $oldStatus, string $newStatus): void
    {
        if (!$fleet->current_hub_id) {
            return;
        }

        if ($oldStatus === 'idle' && $newStatus === 'in_transit') {
            $this->syncHubWarehouseLoad((int) $fleet->current_hub_id, -((int) $fleet->capacity));
            return;
        }

        if ($oldStatus === 'in_transit' && $newStatus === 'idle') {
            $this->syncHubWarehouseLoad((int) $fleet->current_hub_id, (int) $fleet->capacity);
        }
    }

    private function logFleetRelocation(Fleet $fleet, int $originHubId, int $destinationHubId): void
    {
        FleetLog::create([
            'fleet_id' => $fleet->id,
            'origin_hub_id' => $originHubId,
            'destination_hub_id' => $destinationHubId,
            'status' => 'arrived',
            'departed_at' => now()->subHours(random_int(1, 10)),
            'arrived_at' => now(),
        ]);
    }

    private function syncHubWarehouseLoad(int $hubId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $warehouses = Warehouse::where('hub_id', $hubId)->get();

        if ($warehouses->isEmpty()) {
            return;
        }

        $delta < 0
            ? $this->deductWarehouseLoads($warehouses, abs($delta))
            : $this->fillWarehouseLoads($warehouses, $delta);
            
        CacheService::flushTag(CacheService::TAG_WAREHOUSE, CacheService::TAG_HUB);
    }

    private function deductWarehouseLoads(Collection $warehouses, int $targetLoad): int
    {
        $remaining = $targetLoad;
        $applied = 0;

        foreach ($warehouses->sortByDesc('current_load') as $warehouse) {
            if ($remaining <= 0) {
                break;
            }

            $currentLoad = (int) $warehouse->current_load;
            if ($currentLoad <= 0) {
                continue;
            }

            $deduct = min($currentLoad, $remaining);
            if ($deduct <= 0) {
                continue;
            }

            $warehouse->decrement('current_load', $deduct);
            $remaining -= $deduct;
            $applied += $deduct;
        }

        return $applied;
    }

    private function fillWarehouseLoads(Collection $warehouses, int $targetLoad): int
    {
        $remaining = $targetLoad;
        $applied = 0;

        foreach ($warehouses->sortByDesc(function (Warehouse $warehouse): int {
            return max(0, (int) $warehouse->capacity - (int) $warehouse->current_load);
        }) as $warehouse) {
            if ($remaining <= 0) {
                break;
            }

            $availableSpace = max(0, (int) $warehouse->capacity - (int) $warehouse->current_load);
            if ($availableSpace <= 0) {
                continue;
            }

            $add = min($availableSpace, $remaining);
            if ($add <= 0) {
                continue;
            }

            $warehouse->increment('current_load', $add);
            $remaining -= $add;
            $applied += $add;
        }

        return $applied;
    }

    private function getVolumetricDivisorByFleetType(string $fleetType): int
    {
        return match ($fleetType) {
            'motorcycle' => 3500,
            'van' => 4500,
            default => 6000,
        };
    }
}
