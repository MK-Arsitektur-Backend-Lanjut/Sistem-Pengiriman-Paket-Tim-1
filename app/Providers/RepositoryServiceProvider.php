<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Module 4: Fleet & Hub
use App\Repositories\Contracts\FleetRepositoryInterface;
use App\Repositories\Eloquent\FleetRepository;
use App\Repositories\Contracts\HubRepositoryInterface;
use App\Repositories\Eloquent\HubRepository;

// Module 1: Warehouse & Package
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\Eloquent\WarehouseRepository;
use App\Repositories\Contracts\PackageRepositoryInterface;
use App\Repositories\Eloquent\PackageRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Module 4: Fleet & Hub Repository Bindings
        $this->app->bind(FleetRepositoryInterface::class, FleetRepository::class);
        $this->app->bind(HubRepositoryInterface::class, HubRepository::class);

        // Module 1: Warehouse & Package Repository Bindings
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(PackageRepositoryInterface::class, PackageRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
