<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\HubRepositoryInterface;
use App\Repositories\Contracts\FleetRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FleetWebController extends Controller
{
    public function index(Request $request, HubRepositoryInterface $hubRepo, FleetRepositoryInterface $fleetRepo, WarehouseRepositoryInterface $warehouseRepo)
    {
        if(!Schema::hasTable('hubs')) {
            return "Database sedang disiapkan, ini wajar saat instalasi. Harap refresh halaman.";
        }

        $hubs    = $hubRepo->getAllHubs($request->search_hub);
        $fleets  = $fleetRepo->getAllFleets($request->search_fleet); // returns pagination

        $allHubs = $hubRepo->getAllHubs();
        $warehousesCount = $warehouseRepo->getWarehouseCount();
        $warehouses = $warehouseRepo->getLimitWarehouses(15);

        return view('Fleet&Hub.index', compact('hubs', 'allHubs', 'fleets', 'warehouses', 'warehousesCount'));
    }
}
