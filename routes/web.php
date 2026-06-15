<?php

use Illuminate\Support\Facades\Route;

use App\Repositories\Contracts\FleetRepositoryInterface;
use App\Repositories\Contracts\HubRepositoryInterface;
use App\Http\Controllers\Module1MonitoringController;
use App\Http\Controllers\WarehouseWebController;
use App\Http\Controllers\FleetWebController;
use App\Http\Controllers\TrackingWebController;
use App\Http\Controllers\DashboardController;

// ── Module 3: Autentikasi JWT (Dedicated Login & Register Pages) ──
Route::get('/auth/login',    fn() => view('auth.login'))->name('auth.login');
Route::get('/auth/register', fn() => view('auth.register'))->name('auth.register');
Route::get('/auth/profile',  fn() => view('auth.profile'))->name('auth.profile');

// ── Homepage Utama ──
Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::get('/home', function () {
    return redirect()->route('home');
});

// Dashboard Modul 4 (Fleet & Hub)
Route::get('/fleet', [FleetWebController::class, 'index'])->name('fleet.index');

// Module 1: Warehouse & Package Monitoring
Route::get('/module-1-monitor', [Module1MonitoringController::class, 'index'])->name('module1.monitoring');

// Modul 3: Customer Auth & Shipping Profile (API Playground terintegrasi JWT)
Route::get('/module-3', function () {
    return view('module3');
})->name('module3');

// Modul 2: Tracking System Routes
Route::prefix('tracking')->group(function () {
    Route::get('/', [TrackingWebController::class, 'index'])->name('tracking.index');
    Route::get('/search', [TrackingWebController::class, 'search'])->name('tracking.search');
    Route::post('/search', [TrackingWebController::class, 'doSearch'])->name('tracking.doSearch');
    Route::get('/{tracking_number}', [TrackingWebController::class, 'show'])->name('tracking.show');
    Route::get('/{tracking_number}/timeline', [TrackingWebController::class, 'timeline'])->name('tracking.timeline');
    Route::post('/{tracking_number}/status', [TrackingWebController::class, 'updateStatus'])->name('tracking.updateStatus');
});

// API untuk autocomplete search
Route::get('/api/tracking/search', [TrackingWebController::class, 'apiSearch']);
