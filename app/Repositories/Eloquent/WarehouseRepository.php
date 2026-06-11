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
        $stats = Warehouse::query()
            ->selectRaw("
                COUNT(*) as total_warehouses,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_warehouses,
                SUM(CASE WHEN status = 'full' THEN 1 ELSE 0 END) as full_warehouses,
                SUM(CASE WHEN status = 'overload' THEN 1 ELSE 0 END) as overload_warehouses,
                SUM(capacity) as total_capacity,
                SUM(current_load) as total_current_load
            ")
            ->first();

        $totalPackages = \App\Models\Package::count();

        $totalCapacity = (int) ($stats->total_capacity ?? 0);
        $totalCurrentLoad = (int) ($stats->total_current_load ?? 0);

        return [
            'total_warehouses' => (int) ($stats->total_warehouses ?? 0),
            'available_warehouses' => (int) ($stats->available_warehouses ?? 0),
            'full_warehouses' => (int) ($stats->full_warehouses ?? 0),
            'overload_warehouses' => (int) ($stats->overload_warehouses ?? 0),
            // Backward-compat alias untuk view yang masih pakai 'active_warehouses'
            'active_warehouses' => (int) ($stats->available_warehouses ?? 0),
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
