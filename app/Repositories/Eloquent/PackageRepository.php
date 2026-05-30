<?php

namespace App\Repositories\Eloquent;

use App\Models\Package;
use App\Models\Warehouse;
use App\Repositories\Contracts\PackageRepositoryInterface;

class PackageRepository implements PackageRepositoryInterface
{
    public function getAllPackages($filters = [])
    {
        $query = Package::with('warehouse');

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['category'])) {
            $packages = $query->get();
            $packages = $packages->filter(function ($package) use ($filters) {
                return $package->getDimensionCategory() === $filters['category'];
            });
            return $packages;
        }

        if (isset($filters['status'])) {
            $query->where('package_status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function getAllPackagesPaginated($filters = [], $perPage = 15)
    {
        $query = Package::with(['warehouse.hub', 'hub', 'fleet', 'latestLog'])->orderByDesc('created_at');

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['status'])) {
            $query->where('package_status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('origin', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getPackageById($id)
    {
        return Package::with('warehouse')->findOrFail($id);
    }

    public function createPackage($data)
    {
        if (isset($data['length'], $data['width'], $data['height'])) {
            $data['volume'] = $this->calculateVolume(
                $data['length'],
                $data['width'],
                $data['height']
            );
        }

        $package = Package::create($data);

        // Sinkronisasi current_load & status warehouse berdasarkan jumlah paket
        $this->syncWarehouseLoad($package->warehouse_id);

        return $package;
    }

    public function updatePackage($id, $data)
    {
        $package = Package::findOrFail($id);
        $oldWarehouseId = $package->warehouse_id;

        if (isset($data['length'], $data['width'], $data['height'])) {
            $data['volume'] = $this->calculateVolume(
                $data['length'],
                $data['width'],
                $data['height']
            );
        }

        $package->update($data);
        $package->refresh();

        // Sync warehouse lama (jika pindah warehouse)
        if ($oldWarehouseId !== $package->warehouse_id) {
            $this->syncWarehouseLoad($oldWarehouseId);
        }
        // Sync warehouse baru
        $this->syncWarehouseLoad($package->warehouse_id);

        return $package;
    }

    public function deletePackage($id)
    {
        $package = Package::findOrFail($id);
        $warehouseId = $package->warehouse_id;
        $result = $package->delete();

        // Sinkronisasi current_load & status warehouse setelah hapus paket
        $this->syncWarehouseLoad($warehouseId);

        return $result;
    }

    /**
     * Sinkronisasi current_load dan status warehouse berdasarkan jumlah paket.
     * current_load = COUNT(packages WHERE warehouse_id = ?)
     * status = ditentukan oleh Warehouse::resolveStatus()
     */
    private function syncWarehouseLoad(int $warehouseId): void
    {
        $warehouse = Warehouse::find($warehouseId);
        if ($warehouse) {
            $warehouse->recalculateLoad();
        }
    }

    public function getPackagesByWarehouse($warehouseId)
    {
        return Package::where('warehouse_id', $warehouseId)
            ->with('warehouse')
            ->get();
    }

    public function getStatistics()
    {
        $packages = Package::with('warehouse')->get();

        $totalPackages = $packages->count();
        $smallPackages = $packages->filter(function ($p) {
            return $p->getDimensionCategory() === 'small';
        })->count();
        $mediumPackages = $packages->filter(function ($p) {
            return $p->getDimensionCategory() === 'medium';
        })->count();
        $largePackages = $packages->filter(function ($p) {
            return $p->getDimensionCategory() === 'large';
        })->count();

        return [
            'total_packages' => $totalPackages,
            'small_packages' => $smallPackages,
            'medium_packages' => $mediumPackages,
            'large_packages' => $largePackages,
            'by_warehouse' => $packages->groupBy('warehouse_id')->map(function ($group) {
                return [
                    'warehouse_id' => $group->first()->warehouse_id,
                    'warehouse_name' => $group->first()->warehouse->warehouse_name ?? 'N/A',
                    'count' => $group->count(),
                ];
            })->values(),
        ];
    }

    public function calculateDimensionCategory($dimensions)
    {
        $volume = $this->calculateVolume(
            $dimensions['length'] ?? 0,
            $dimensions['width'] ?? 0,
            $dimensions['height'] ?? 0
        );

        if ($volume <= 1000) {
            return 'small';
        }

        if ($volume <= 5000) {
            return 'medium';
        }

        return 'large';
    }

    public function calculateVolume($length, $width, $height)
    {
        return (int) ($length * $width * $height);
    }

    public function getPackagesByCategory()
    {
        $packages = Package::with('warehouse')->get();

        return [
            'small' => $packages->filter(function ($p) {
                return $p->getDimensionCategory() === 'small';
            })->values(),
            'medium' => $packages->filter(function ($p) {
                return $p->getDimensionCategory() === 'medium';
            })->values(),
            'large' => $packages->filter(function ($p) {
                return $p->getDimensionCategory() === 'large';
            })->values(),
        ];
    }

    public function findPackageByTrackingNumber(string $trackingNumber)
    {
        $cacheKey = \App\Services\CacheService::keyShipmentByTracking($trackingNumber);

        return \App\Services\CacheService::remember(
            $cacheKey,
            fn () => Package::with(['warehouse.hub', 'hub', 'fleet', 'histories.hub', 'histories.fleet', 'latestLog'])
                ->where('tracking_number', $trackingNumber)
                ->firstOrFail(),
            \App\Services\CacheService::TTL_SHORT,
            [\App\Services\CacheService::TAG_SHIPMENT, \App\Services\CacheService::TAG_PACKAGE]
        );
    }

    public function updatePackageStatus(string $trackingNumber, array $data)
    {
        $package = Package::where('tracking_number', $trackingNumber)->firstOrFail();

        $updateData = [];
        if (isset($data['status'])) {
            $updateData['package_status'] = $data['status'];
        }
        if (array_key_exists('hub_id', $data)) {
            $updateData['hub_id'] = $data['hub_id'];
        }
        if (array_key_exists('fleet_id', $data)) {
            $updateData['fleet_id'] = $data['fleet_id'];
        }

        $package->update($updateData);

        // Catat riwayat di tabel package_histories
        \App\Models\PackageHistory::create([
            'package_id'  => $package->id,
            'status'      => $data['status'] ?? $package->package_status,
            'hub_id'      => array_key_exists('hub_id', $data) ? $data['hub_id'] : $package->hub_id,
            'fleet_id'    => array_key_exists('fleet_id', $data) ? $data['fleet_id'] : $package->fleet_id,
            'notes'       => $data['notes'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        // Invalidasi cache
        \App\Services\CacheService::forget(\App\Services\CacheService::keyShipmentByTracking($trackingNumber));
        \App\Services\CacheService::flushTag(\App\Services\CacheService::TAG_SHIPMENT, \App\Services\CacheService::TAG_PACKAGE);

        return $package->fresh(['warehouse.hub', 'hub', 'fleet', 'histories.hub', 'histories.fleet']);
    }
}
