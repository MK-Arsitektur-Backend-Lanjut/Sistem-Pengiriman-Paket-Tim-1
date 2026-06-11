<?php

namespace App\Repositories\Eloquent;

use App\Models\Hub;
use App\Models\Warehouse;
use App\Repositories\Contracts\HubRepositoryInterface;
use App\Services\CacheService;

class HubRepository implements HubRepositoryInterface
{
    public function getAllHubs($search = null)
    {
        $key = CacheService::keyHubList($search ?? '');
        return CacheService::remember($key, function () use ($search) {
            return $this->fetchHubs($search);
        }, CacheService::TTL_SHORT, [CacheService::TAG_HUB, CacheService::TAG_WAREHOUSE]);
    }

    private function fetchHubs(?string $search)
    {
        $query = Hub::query();
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $hubs = $query->get();

        if ($hubs->isEmpty()) {
            return $hubs;
        }

        $hubIds = $hubs->pluck('id')->all();

        $warehouseLoads = Warehouse::query()
            ->selectRaw('hub_id, SUM(current_load) as total_load, SUM(capacity) as total_capacity')
            ->whereIn('hub_id', $hubIds)
            ->groupBy('hub_id')
            ->get()
            ->keyBy('hub_id');

        return $hubs->map(function (Hub $hub) use ($warehouseLoads) {
            $warehouseData = $warehouseLoads[$hub->id] ?? null;

            $hub->current_load = $warehouseData ? (int) $warehouseData->total_load : 0;
            $hub->capacity = $warehouseData ? (int) $warehouseData->total_capacity : 0;

            return $hub;
        });
    }

    public function checkCapacity($hubId)
    {
        $key = 'hubs:capacity:' . $hubId;
        return CacheService::remember($key, function () use ($hubId) {
            $hub = Hub::findOrFail($hubId);

            $warehouseTotals = Warehouse::query()
                ->where('hub_id', $hubId)
                ->selectRaw('SUM(current_load) as current_load, SUM(capacity) as capacity')
                ->first();
            
            $warehouseLoad = (int) ($warehouseTotals->current_load ?? 0);
            $warehouseCapacity = (int) ($warehouseTotals->capacity ?? 0);
            
            $percentage = ($warehouseCapacity > 0) ? ($warehouseLoad / $warehouseCapacity) * 100 : 0;
            
            // Return structured data for "monitoring kapasitas gudang"
            $status = 'available';
            if ($percentage >= 100) {
                $status = 'overload';
            } elseif ($percentage >= 90) {
                $status = 'full';
            }

            return [
                'hub_id' => $hub->id,
                'name' => $hub->name,
                'capacity' => $hub->capacity,
                'current_load' => $warehouseLoad,
                'utilization_percentage' => round($percentage, 2) . '%',
                'status' => $status
            ];
        }, CacheService::TTL_SHORT, [CacheService::TAG_HUB, CacheService::TAG_WAREHOUSE]);
    }
}
