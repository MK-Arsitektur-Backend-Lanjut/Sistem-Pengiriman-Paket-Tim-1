<?php

namespace App\Repositories\Eloquent;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function getAllWarehouses($filters = [])
    {
        $query = Warehouse::with('packages');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('warehouse_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function getWarehouseById($id)
    {
        return Warehouse::with('packages')->findOrFail($id);
    }

    public function createWarehouse($data)
    {
        return Warehouse::create($data);
    }

    public function updateWarehouse($id, $data)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($data);
        return $warehouse->refresh();
    }

    public function deleteWarehouse($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        return $warehouse->delete();
    }

    public function hasPackages($id)
    {
        return Warehouse::findOrFail($id)->packages()->exists();
    }

    public function getStatistics()
    {
        $warehouses = Warehouse::with('packages')->get();

        $totalWarehouses = $warehouses->count();
        $availableWarehouses = $warehouses->where('status', 'available')->count();
        $fullWarehouses = $warehouses->where('status', 'full')->count();
        $overloadWarehouses = $warehouses->where('status', 'overload')->count();
        $totalPackages = $warehouses->sum(function ($w) {
            return $w->packages->count();
        });
        $totalCapacity = $warehouses->sum('capacity');
        $totalCurrentLoad = $warehouses->sum('current_load');

        return [
            'total_warehouses' => $totalWarehouses,
            'available_warehouses' => $availableWarehouses,
            'full_warehouses' => $fullWarehouses,
            'overload_warehouses' => $overloadWarehouses,
            // Backward-compat alias untuk view yang masih pakai 'active_warehouses'
            'active_warehouses' => $availableWarehouses,
            'total_packages' => $totalPackages,
            'total_capacity' => $totalCapacity,
            'total_current_load' => $totalCurrentLoad,
            'total_usage_percentage' => $totalCapacity > 0
                ? round(($totalCurrentLoad / $totalCapacity) * 100, 2)
                : 0,
        ];
    }

    public function calculateUsagePercentage($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        if ($warehouse->capacity <= 0) {
            return 0;
        }

        return round(($warehouse->current_load / $warehouse->capacity) * 100, 2);
    }

    public function getWarehouseCount()
    {
        return Warehouse::count();
    }

    public function getLimitWarehouses($limit)
    {
        return Warehouse::limit($limit)->get();
    }
}
