<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CustomerAuthController;
use App\Http\Controllers\API\ShippingCalculatorController;
use App\Http\Controllers\API\ShippingProfileController;
use App\Http\Controllers\API\TrackingController;
use App\Http\Controllers\API\WarehouseController;
use App\Http\Controllers\API\PackageController;
use App\Http\Controllers\API\FleetController;
use App\Http\Controllers\API\HubController;

Route::prefix('v1')->group(function (): void {

    // ══════════════════════════════════════════════════════════════════
    // Modul 3: Authentication (JWT) - Public Endpoints
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('auth')->group(function () {
        Route::post('/register', [CustomerAuthController::class, 'register'])->name('api.auth.register');
        Route::post('/login',    [CustomerAuthController::class, 'login'])->name('api.auth.login');
    });

    // ══════════════════════════════════════════════════════════════════
    // Modul 2: Tracking System - Public Endpoints
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('tracking')->group(function () {
        Route::get('/',                          [TrackingController::class, 'index']);       // Daftar semua paket
        Route::get('/search',                    [TrackingController::class, 'search']);      // Pencarian resi
        Route::get('/{tracking_number}',         [TrackingController::class, 'show']);        // Detail paket + semua log
        Route::get('/{tracking_number}/history', [TrackingController::class, 'showHistory']); // Riwayat kronologis
    });

    // ══════════════════════════════════════════════════════════════════
    // Modul 1: Warehouse Management - Public Endpoints (CRUD)
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('warehouse')->group(function () {
        Route::get('/',        [WarehouseController::class, 'index']);
        Route::post('/',       [WarehouseController::class, 'store']);
        Route::get('/{id}',    [WarehouseController::class, 'show']);
        Route::put('/{id}',    [WarehouseController::class, 'update']);
        Route::delete('/{id}', [WarehouseController::class, 'destroy']);
    });

    Route::prefix('package')->group(function () {
        Route::get('/',               [PackageController::class, 'index']);
        Route::post('/register',      [PackageController::class, 'store']);
        Route::get('/{id}',           [PackageController::class, 'show']);
        Route::put('/{id}',           [PackageController::class, 'update']);
        Route::delete('/{id}',        [PackageController::class, 'destroy']);
        Route::get('/{id}/dimension', [PackageController::class, 'getDimension']);
        // M2 Integration: hub tujuan yang tersedia untuk paket ini
        Route::get('/{id}/available-destination-hubs', [TrackingController::class, 'availableDestinationHubs']);
    });

    // ══════════════════════════════════════════════════════════════════
    // PROTECTED ROUTES: Require JWT Authentication (auth:api)
    // ══════════════════════════════════════════════════════════════════
    Route::middleware('auth:api')->group(function (): void {

        // ── Modul 3: Auth Protected Endpoints ──
        Route::prefix('auth')->group(function () {
            Route::post('/logout',  [CustomerAuthController::class, 'logout'])->name('auth.logout');
            Route::post('/refresh', [CustomerAuthController::class, 'refresh'])->name('auth.refresh');
            Route::get('/me',       [CustomerAuthController::class, 'me'])->name('auth.me');
        });

        // ── Modul 3: Customer Profile & Shipping Calculator ──
        Route::get('/customer/shipping-profile',         [ShippingProfileController::class, 'show']);
        Route::put('/customer/shipping-profile',         [ShippingProfileController::class, 'upsert']);
        Route::post('/customer/shipping-cost/calculate', [ShippingCalculatorController::class, 'calculate']);

        // ── Modul 3 → Modul 2 Integration: Customer Shipments ──
        Route::get('/customer/shipments',                 [TrackingController::class, 'customerShipments']);
        Route::get('/customer/shipments/{tracking_number}', [TrackingController::class, 'customerShipmentDetail']);

        // ── Modul 2: Create Shipment from Package (M1 + M3 Integration) ──
        Route::post('/shipment/from-package/{package_id}', [TrackingController::class, 'createFromPackage']);

        // ── Modul 2: Update Lokasi Paket (append log baru ke shipment_logs) ──
        Route::patch('/tracking/{tracking_number}/status', [TrackingController::class, 'updateStatus']);
    });

    // ══════════════════════════════════════════════════════════════════
    // Modul 4: Fleet Management & Hub Monitoring
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('fleet')->group(function () {
        Route::get('/',                 [FleetController::class, 'index']);
        Route::post('/',                [FleetController::class, 'store']);
        Route::post('/{id}/load-plan',  [FleetController::class, 'calculateLoadPlan']);
        Route::put('/{id}/status',      [FleetController::class, 'updateStatus']);
        Route::put('/{id}/relocate',    [FleetController::class, 'relocate']);
        Route::get('/{id}',             [FleetController::class, 'show']);
        Route::get('/{id}/duration',    [FleetController::class, 'getTransitDuration']);
    });

    Route::prefix('hub')->group(function () {
        Route::get('/',              [HubController::class, 'index']);
        Route::get('/{id}/capacity', [HubController::class, 'checkCapacity']);
    });
});
