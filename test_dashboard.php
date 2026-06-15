<?php

require 'vendor/autoload.php';

use App\Models\Warehouse;
use App\Models\Package;
use App\Models\Hub;
use App\Models\Fleet;
use App\Models\PackageHistory;
use Illuminate\Support\Facades\DB;

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Dashboard Queries...\n\n";

echo "1. Warehouse Count: ";
$count = Warehouse::count();
echo $count . "\n";

echo "2. Package Count: ";
$count = Package::count();
echo $count . "\n";

echo "3. Hub Count: ";
$count = Hub::count();
echo $count . "\n";

echo "4. Fleet Count: ";
$count = Fleet::count();
echo $count . "\n";

echo "5. Hub with JOIN Query (fixed): ";
$hubData = Hub::select('hubs.id', 'hubs.name')
    ->selectRaw('COUNT(DISTINCT packages.id) as packages_count')
    ->selectRaw('COUNT(DISTINCT fleets.id) as fleets_count')
    ->leftJoin('packages', 'packages.hub_id', '=', 'hubs.id')
    ->leftJoin('fleets', 'fleets.current_hub_id', '=', 'hubs.id')
    ->groupBy('hubs.id', 'hubs.name')
    ->get();
echo count($hubData) . " hubs found\n";

echo "6. Fleet with JOIN Query (fixed): ";
$fleetData = Fleet::select('fleets.id', 'fleets.plate_number', 'fleets.capacity', 'fleets.status')
    ->selectRaw('COUNT(packages.id) as packages_count')
    ->leftJoin('packages', 'packages.fleet_id', '=', 'fleets.id')
    ->groupBy('fleets.id', 'fleets.plate_number', 'fleets.capacity', 'fleets.status')
    ->get();
echo count($fleetData) . " fleets found\n";

echo "7. Package Trend Query: ";
$trendData = DB::table('package_histories')
    ->selectRaw('DATE(recorded_at) as date, COUNT(*) as count')
    ->where('recorded_at', '>=', now()->subDays(7))
    ->groupBy(DB::raw('DATE(recorded_at)'))
    ->orderBy('date')
    ->pluck('count', 'date');
echo count($trendData) . " trend entries found\n";

echo "\n✓ All queries completed successfully!\n";
