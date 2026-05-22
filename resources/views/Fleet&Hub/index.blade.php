@extends('layouts.app')

@section('title', 'Modul 4 - Fleet & Hub Management')
@section('meta_description', 'Dashboard Modul 4 untuk monitoring kapasitas hub dan manajemen armada secara real-time.')
@section('active_nav', 'module4')
@section('requires_auth', '1')

@php
    $fleetPageItems = collect($fleets->items());
    $idleCount = $fleetPageItems->where('status', 'idle')->count();
    $transitCount = $fleetPageItems->where('status', 'in_transit')->count();
    $maintenanceCount = $fleetPageItems->where('status', 'maintenance')->count();

    $kpiCards = [
        [
            'icon' => 'bi-truck',
            'icon_class' => 'kpi-icon-fleet',
            'pill_class' => '',
            'label' => 'Total Armada',
            'value' => $fleets->total(),
            'caption' => 'seluruh unit',
        ],
        [
            'icon' => 'bi-building',
            'icon_class' => 'kpi-icon-hub',
            'pill_class' => 'pill-hub',
            'label' => 'Hub Aktif',
            'value' => $hubs->count(),
            'caption' => 'lokasi terdeteksi',
        ],
        [
            'icon' => 'bi-play-circle',
            'icon_class' => 'kpi-icon-idle',
            'pill_class' => 'pill-idle',
            'label' => 'Idle',
            'value' => $idleCount,
            'caption' => 'di halaman ini',
        ],
        [
            'icon' => 'bi-arrow-left-right',
            'icon_class' => 'kpi-icon-transit',
            'pill_class' => 'pill-transit',
            'label' => 'Transit / Maint',
            'value' => $transitCount + $maintenanceCount,
            'caption' => 'bergerak atau servis',
        ],
    ];
@endphp

@include('Fleet&Hub.partials.styles')

@section('content')
<section class="py-4">
    <div class="container">
        <div class="m4-hero p-4 p-lg-5 mb-4">
            <div class="m4-hero-content d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <span class="badge text-bg-light text-primary mb-2">Modul 4 - Operasional Armada</span>
                    <h1 class="h3 fw-bold mb-2">Fleet & Hub Control Center</h1>
                    <p class="mb-0 text-white-50">Pantau kapasitas hub, ubah status armada, relokasi lintas hub, dan baca laporan transit tanpa pindah halaman.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ url('/home') }}" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-house me-1"></i> Home</a>
                    <button class="btn btn-outline-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addFleetModal">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Armada
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @foreach($kpiCards as $kpi)
                <div class="col-md-6 col-xl-3">
                    <div class="kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="kpi-icon {{ $kpi['icon_class'] }}"><i class="bi {{ $kpi['icon'] }}"></i></span>
                            <span class="pill-info {{ $kpi['pill_class'] }}">{{ $kpi['label'] }}</span>
                        </div>
                        <div class="h4 fw-bold mb-0">{{ $kpi['value'] }}</div>
                        <small class="text-muted">{{ $kpi['caption'] }}</small>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="soft-card h-100 hub-monitor-card">
                    <div class="p-3 p-lg-4 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 fw-bold mb-1"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Monitor Kapasitas Hub</h2>
                            <small class="text-muted">sumber data beban: warehouse aggregate</small>
                        </div>
                    </div>
                    <div class="hub-scroll-area">
                        <ul class="list-group list-group-flush">
                            @foreach($hubs->take(15) as $hub)
                                @php
                                    $percentage = $hub->capacity > 0 ? round(($hub->current_load / $hub->capacity) * 100) : 0;
                                    $color = $percentage >= 90 ? 'danger' : ($percentage >= 70 ? 'warning' : 'success');
                                @endphp
                                <li class="list-group-item px-3 py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong class="small">{{ $hub->name }}</strong>
                                        <span class="badge bg-{{ $color }}">{{ $percentage }}%</span>
                                    </div>
                                    <div class="progress hub-progress mb-2">
                                        <div class="progress-bar bg-{{ $color }}" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ number_format($hub->current_load) }} / {{ number_format($hub->capacity) }} kapasitas terisi</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="soft-card h-100">
                    <div class="p-3 p-lg-4 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                            <div>
                                <h2 class="h6 fw-bold mb-1"><i class="bi bi-radar text-primary me-2"></i>Live Fleet Tracking</h2>
                                <small class="text-muted">status, relokasi hub, dan laporan transit per armada</small>
                            </div>
                            <form method="GET" action="/" class="d-flex gap-2 search-mini">
                                <input type="text" name="search_fleet" class="form-control form-control-sm" placeholder="Cari nopol / tipe" value="{{ request('search_fleet') }}">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Armada</th>
                                    <th>Tipe</th>
                                    <th>Kapasitas</th>
                                    <th>Status</th>
                                    <th>Lokasi Hub</th>
                                    <th class="pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fleets as $fleet)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fleet-row-icon"><i class="bi bi-truck"></i></span>
                                                <div>
                                                    <strong class="d-block">{{ $fleet->plate_number }}</strong>
                                                    <small class="text-muted">ID #{{ $fleet->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="text-capitalize text-muted fw-semibold">{{ $fleet->type }}</span></td>
                                        <td><span class="text-muted fw-semibold">{{ number_format($fleet->capacity) }} Kg/M³</span></td>
                                        <td>
                                            <select
                                                class="form-select form-select-sm fleet-status-select"
                                                data-current="{{ $fleet->status }}"
                                                onchange="updateStatus({{ $fleet->id }}, this)"
                                            >
                                                <option value="idle" {{ $fleet->status == 'idle' ? 'selected' : '' }}>Idle</option>
                                                <option value="in_transit" {{ $fleet->status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                                <option value="maintenance" {{ $fleet->status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select
                                                class="form-select form-select-sm fleet-hub-select"
                                                data-current="{{ $fleet->current_hub_id }}"
                                                onchange="relocateFleet({{ $fleet->id }}, this)"
                                            >
                                                @foreach($allHubs as $hubOption)
                                                    <option value="{{ $hubOption->id }}" {{ $fleet->current_hub_id == $hubOption->id ? 'selected' : '' }}>
                                                        {{ $hubOption->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="pe-4">
                                            <button class="btn btn-sm btn-primary rounded-pill" onclick="viewTransitReport({{ $fleet->id }})">
                                                <i class="bi bi-clock-history me-1"></i>Transit API
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">Tidak ada armada ditemukan untuk filter saat ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-3 border-top d-flex justify-content-end">
                        {{ $fleets->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('Fleet&Hub.partials.add-fleet-modal')
@endsection

@include('Fleet&Hub.partials.scripts')
