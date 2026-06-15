<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Package;
use App\Models\Hub;
use App\Models\Fleet;
use App\Models\PackageHistory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── WAREHOUSE DATA ──
        $warehouseData = Warehouse::select(
            'warehouse_name',
            'capacity',
            'current_load',
            DB::raw('ROUND((current_load/capacity)*100, 2) as usage_percentage')
        )->get();

        // ── PACKAGE STATISTICS ──
        $packageStats = [
            'total' => Package::count(),
            'by_category' => [
                'small' => Package::where('volume', '<=', 1000)->count(),
                'medium' => Package::where('volume', '>', 1000)->where('volume', '<=', 5000)->count(),
                'large' => Package::where('volume', '>', 5000)->count(),
            ],
            'by_status' => Package::selectRaw('package_status, count(*) as count')
                ->groupBy('package_status')
                ->pluck('count', 'package_status')
                ->toArray(),
        ];

        // ── HUB DATA ──
        $hubData = Hub::select('hubs.id', 'hubs.name')
            ->selectRaw('COUNT(DISTINCT packages.id) as packages_count')
            ->selectRaw('COUNT(DISTINCT fleets.id) as fleets_count')
            ->leftJoin('packages', 'packages.hub_id', '=', 'hubs.id')
            ->leftJoin('fleets', 'fleets.current_hub_id', '=', 'hubs.id')
            ->groupBy('hubs.id', 'hubs.name')
            ->get()
            ->map(function ($hub) {
                return [
                    'name' => $hub->name,
                    'packages_count' => (int)$hub->packages_count,
                    'fleets_count' => (int)$hub->fleets_count,
                ];
            });

        // ── FLEET DATA ──
        $fleetData = Fleet::select('fleets.id', 'fleets.plate_number', 'fleets.capacity', 'fleets.status')
            ->selectRaw('COUNT(packages.id) as packages_count')
            ->leftJoin('packages', 'packages.fleet_id', '=', 'fleets.id')
            ->groupBy('fleets.id', 'fleets.plate_number', 'fleets.capacity', 'fleets.status')
            ->get()
            ->map(function ($fleet) {
                return [
                    'name' => $fleet->plate_number,
                    'status' => $fleet->status,
                    'max_capacity' => $fleet->capacity,
                    'packages_count' => (int)$fleet->packages_count,
                    'utilization' => $fleet->capacity > 0 
                        ? round(($fleet->packages_count / $fleet->capacity) * 100, 2) 
                        : 0,
                ];
            });

        // ── PACKAGE HISTORY TREND (Last 7 Days) ──
        $trendData = DB::table('package_histories')
            ->selectRaw('DATE(recorded_at) as date, COUNT(*) as count')
            ->where('recorded_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(recorded_at)'))
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates
        $packageTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $packageTrend[$date] = $trendData[$date] ?? 0;
        }

        // ── TOP WAREHOUSES BY CAPACITY USAGE ──
        $topWarehouses = $warehouseData
            ->sortByDesc('usage_percentage')
            ->take(5);

        // ── OVERALL METRICS ──
        $metrics = [
            'total_warehouses' => Warehouse::count(),
            'total_hubs' => Hub::count(),
            'total_fleets' => Fleet::count(),
            'total_packages' => Package::count(),
            'avg_warehouse_usage' => round($warehouseData->avg('usage_percentage'), 2),
            'avg_fleet_utilization' => round($fleetData->avg('utilization'), 2),
        ];

        return view('pages.home.index', [
            'warehouseData' => $warehouseData,
            'packageStats' => $packageStats,
            'hubData' => $hubData,
            'fleetData' => $fleetData,
            'packageTrend' => $packageTrend,
            'topWarehouses' => $topWarehouses,
            'metrics' => $metrics,
        ]);
    }
}
