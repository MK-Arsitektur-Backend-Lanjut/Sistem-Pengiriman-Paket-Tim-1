<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Package;
use App\Models\Hub;
use App\Services\CacheService;

class Module1MonitoringController extends Controller
{
    /**
     * Display the monitoring dashboard for Module 1.
     * Data berat (warehouse + package) di-cache di Redis agar tidak query setiap request.
     */
    public function index()
    {
        try {
            // ── Cache warehouse statistics (5 menit) ──────────────────────────
            $warehouseStats = CacheService::remember(
                CacheService::keyWarehouseStats(),
                fn () => $this->buildWarehouseStats(),
                CacheService::TTL_MEDIUM,
                [CacheService::TAG_WAREHOUSE, CacheService::TAG_STATS]
            );

            // ── Cache package list (1 menit – lebih sering berubah) ──────────
            $packagesByDimension = CacheService::remember(
                CacheService::keyPackageList(),
                fn () => $this->buildPackageList(),
                CacheService::TTL_SHORT,
                [CacheService::TAG_PACKAGE]
            );

            // ── Hub list (statis, cache 30 menit) ────────────────────────────
            $hubs = CacheService::remember(
                CacheService::keyHubList(),
                fn () => Hub::orderBy('name')->get(['id', 'name']),
                CacheService::TTL_LONG,
                [CacheService::TAG_HUB]
            );

            $dimensionCategories = $packagesByDimension->groupBy('dimension_category')->map->count();

            $data = [
                // Warehouse Statistics
                'total_warehouses'       => $warehouseStats['total_warehouses'],
                'active_warehouses'      => $warehouseStats['active_warehouses'],
                'total_capacity'         => $warehouseStats['total_capacity'],
                'total_current_load'     => $warehouseStats['total_current_load'],
                'overall_usage_percentage' => $warehouseStats['overall_usage_percentage'],

                // Package Statistics
                'total_packages'         => $packagesByDimension->count(),
                'packages_by_status'     => $packagesByDimension->groupBy('status')->map->count(),
                'packages_by_dimension'  => $dimensionCategories,

                // Data Lists
                'warehouses'             => $warehouseStats['warehouses'],
                'packages'               => $packagesByDimension,
                'all_hubs'               => $hubs,

                // Chart data
                'warehouse_names'        => $warehouseStats['warehouses']->pluck('warehouse_name')->toArray(),
                'warehouse_loads'        => $warehouseStats['warehouses']->pluck('current_load')->toArray(),
            ];

            return view('module1.monitoring', $data);

        } catch (\Exception $e) {
            return view('module1.monitoring', [
                'error'                  => 'Failed to load monitoring data: ' . $e->getMessage(),
                'total_warehouses'       => 0,
                'active_warehouses'      => 0,
                'total_capacity'         => 0,
                'total_current_load'     => 0,
                'overall_usage_percentage' => 0,
                'total_packages'         => 0,
                'packages_by_status'     => collect(),
                'packages_by_dimension'  => collect(),
                'warehouses'             => collect(),
                'packages'               => collect(),
                'all_hubs'               => collect(),
                'warehouse_names'        => [],
                'warehouse_loads'        => [],
            ]);
        }
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function buildWarehouseStats(): array
    {
        $warehouses = Warehouse::with(['packages', 'hub'])->get();

        $totalCapacity    = $warehouses->sum('capacity');
        $totalCurrentLoad = $warehouses->sum('current_load');

        $warehouseUsage = $warehouses->map(function ($warehouse) {
            $usagePercentage = $warehouse->capacity > 0
                ? round(($warehouse->current_load / $warehouse->capacity) * 100, 2)
                : 0;

            return [
                'id'               => $warehouse->id,
                'warehouse_name'   => $warehouse->warehouse_name,
                'location'         => $warehouse->location,
                'hub_id'           => $warehouse->hub_id,
                'hub_name'         => $warehouse->hub?->name,
                'capacity'         => $warehouse->capacity,
                'current_load'     => $warehouse->current_load,
                'usage_percentage' => $usagePercentage,
                'status'           => $warehouse->status,
                'package_count'    => $warehouse->packages->count(),
                'created_at'       => $warehouse->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'total_warehouses'       => $warehouses->count(),
            'active_warehouses'      => $warehouses->where('status', 'active')->count(),
            'total_capacity'         => $totalCapacity,
            'total_current_load'     => $totalCurrentLoad,
            'overall_usage_percentage' => $totalCapacity > 0
                ? round(($totalCurrentLoad / $totalCapacity) * 100, 2)
                : 0,
            'warehouses'             => $warehouseUsage,
        ];
    }

    private function buildPackageList()
    {
        return Package::with('warehouse')->get()->map(function ($package) {
            return [
                'id'               => $package->id,
                'tracking_number'  => $package->tracking_number,
                'sender_name'      => $package->sender_name,
                'receiver_name'    => $package->receiver_name,
                'origin'           => $package->origin,
                'destination'      => $package->destination,
                'weight'           => $package->weight,
                'length'           => $package->length,
                'width'            => $package->width,
                'height'           => $package->height,
                'volume'           => $package->volume,
                'dimension_category' => $package->getDimensionCategory(),
                'warehouse_name'   => $package->warehouse->warehouse_name ?? 'Unknown',
                'status'           => $package->package_status,
                'created_at'       => $package->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }
}
