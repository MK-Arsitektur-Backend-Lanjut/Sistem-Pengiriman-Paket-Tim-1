@extends('layouts.app')

@section('title', 'Timeline - ' . $package->tracking_number)
@section('meta_description', 'Timeline kronologis perjalanan paket dari asal hingga tujuan.')
@section('active_nav', 'tracking')
@section('requires_auth', '1')

@push('styles')
<style>
    body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
    .timeline {
        position: relative;
        padding: 20px 0;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 19px;
        top: 0; bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #0d6efd, #17a2b8, #28a745);
    }
    @media (min-width: 576px) {
        .timeline::before { left: 39px; }
    }
    .timeline-item {
        position: relative;
        margin-bottom: 30px;
        padding-left: 50px;
    }
    @media (min-width: 576px) {
        .timeline-item { padding-left: 100px; }
    }
    .timeline-marker {
        position: absolute;
        left: 0; top: 0;
        width: 40px; height: 40px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 3px solid #0d6efd;
        z-index: 10;
        font-size: 1rem;
    }
    @media (min-width: 576px) {
        .timeline-marker { width: 80px; height: 80px; font-size: 1.5rem; }
    }
    .timeline-item:nth-child(odd)  .timeline-marker { border-color: #0d6efd; }
    .timeline-item:nth-child(even) .timeline-marker { border-color: #17a2b8; }
    .timeline-item:last-child      .timeline-marker { border-color: #28a745; }

    .timeline-content {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    @media (min-width: 576px) { .timeline-content { padding: 20px; } }
    .timeline-content h5 { margin-bottom: 10px; color: #0d6efd; font-size: 1rem; }
    @media (min-width: 576px) { .timeline-content h5 { font-size: 1.1rem; } }
    .timeline-time  { font-size: 0.85rem; color: #6c757d; font-weight: bold; }
    .timeline-date  { font-size: 1rem; font-weight: bold; color: #2c3e50; margin: 5px 0; }
    .timeline-notes {
        margin-top: 10px; padding: 10px;
        background: #f8f9fa;
        border-left: 3px solid #0d6efd;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    .tracking-number { font-family: 'Courier New', monospace; font-weight: bold; color: #0d6efd; }
</style>
@endpush

@section('content')
@php
    $currentStatus = $package->latestLog?->status ?? $package->package_status;
@endphp

<div class="container-fluid container-lg pb-5">
    <!-- Back -->
    <div class="row mb-3">
        <div class="col-12 d-flex gap-2 flex-wrap">
            <a href="{{ route('tracking.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Daftar Paket
            </a>
            <a href="{{ route('tracking.show', $package->tracking_number) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> Detail Paket
            </a>
        </div>
    </div>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1">Timeline Pengiriman</h2>
            <p class="text-muted">Riwayat kronologis selengkap perjalanan paket</p>
            <div class="mt-2">
                <span class="tracking-number">{{ $package->tracking_number }}</span>
                <span class="badge bg-info ms-2">{{ $logs->count() }} Log</span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Timeline -->
        <div class="col-lg-8">
            <div class="timeline">
                @forelse($logs as $index => $log)
                <div class="timeline-item">
                    <div class="timeline-marker">
                        @switch($log->status)
                            @case('registered')
                                <i class="bi bi-box text-secondary"></i>
                                @break
                            @case('picked_up')
                                <i class="bi bi-person-check text-info"></i>
                                @break
                            @case('in_transit')
                                <i class="bi bi-truck text-primary"></i>
                                @break
                            @case('arrived_at_hub')
                                <i class="bi bi-building text-info"></i>
                                @break
                            @case('out_for_delivery')
                                <i class="bi bi-pin-map-fill text-warning"></i>
                                @break
                            @case('delivered')
                                <i class="bi bi-check-circle text-success"></i>
                                @break
                            @case('failed')
                                <i class="bi bi-exclamation-circle text-danger"></i>
                                @break
                            @case('returned')
                                <i class="bi bi-arrow-return-left text-dark"></i>
                                @break
                            @default
                                <i class="bi bi-question-circle"></i>
                        @endswitch
                    </div>
                    <div class="timeline-content">
                        <h5>{{ $log->status_label }}</h5>
                        <div class="timeline-date">
                            {{ $log->recorded_at?->format('d M Y') ?? '-' }}
                        </div>
                        <div class="timeline-time">
                            <i class="bi bi-clock"></i>
                            {{ $log->recorded_at?->format('H:i:s') ?? '-' }}
                        </div>

                        @if($log->hub || $log->location_note)
                        <div class="timeline-notes mt-3">
                            <strong><i class="bi bi-geo-alt-fill"></i> Lokasi:</strong><br>
                            @if($log->hub)
                                <span class="badge bg-light text-dark"><i class="bi bi-building"></i> Hub: {{ $log->hub->name }}</span>
                            @endif
                            @if($log->location_note)
                                <span class="ms-1 text-muted small">{{ $log->location_note }}</span>
                            @endif
                        </div>
                        @endif

                        @if($log->fleet)
                        <div class="timeline-notes" style="border-left-color: #fd7e14;">
                            <strong><i class="bi bi-truck"></i> Armada:</strong>
                            {{ $log->fleet->plate_number ?? 'Armada #' . $log->fleet_id }}
                        </div>
                        @endif

                        @if($log->notes)
                        <div class="timeline-notes">
                            <strong><i class="bi bi-chat-left-text"></i> Catatan:</strong><br>
                            {{ $log->notes }}
                        </div>
                        @endif

                        <div class="timeline-notes" style="border-left-color: #6c757d; background: #f0f0f0; margin-top: 10px;">
                            <small><i class="bi bi-info-circle"></i>
                                Dicatat: {{ $log->created_at?->format('d M Y H:i:s') ?? '-' }}
                            </small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Belum ada riwayat untuk paket ini.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Ringkasan -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle"></i> Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Status Terakhir</p>
                        <h6 class="fw-bold">{{ $logs->last()?->status_label ?? '-' }}</h6>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Update Pertama</p>
                        <h6 class="fw-bold">{{ $logs->first()?->recorded_at?->format('d M Y H:i') ?? '-' }}</h6>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Update Terakhir</p>
                        <h6 class="fw-bold">{{ $logs->last()?->recorded_at?->format('d M Y H:i') ?? '-' }}</h6>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Log</p>
                        <h6 class="fw-bold">{{ $logs->count() }} record</h6>
                    </div>
                </div>
            </div>

            <!-- Pengirim & Penerima -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-people"></i> Pengirim & Penerima</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-1 small">Pengirim</p>
                    <h6 class="fw-bold">{{ $package->sender_name }}</h6>
                    <p class="text-muted small"><i class="bi bi-geo-alt-fill"></i> {{ $package->origin }}</p>
                    <hr>
                    <p class="text-muted mb-1 small">Penerima</p>
                    <h6 class="fw-bold">{{ $package->receiver_name }}</h6>
                    <p class="text-muted small"><i class="bi bi-geo-alt-fill"></i> {{ $package->destination }}</p>
                </div>
            </div>

            <!-- Paket -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box"></i> Info Paket</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <p class="text-muted mb-1 small">Berat</p>
                        <h6 class="fw-bold">{{ $package->weight }} kg</h6>
                    </div>
                    <div class="mb-2">
                        <p class="text-muted mb-1 small">Dimensi</p>
                        <h6 class="fw-bold">{{ $package->length }}×{{ $package->width }}×{{ $package->height }} cm</h6>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Volume</p>
                        <h6 class="fw-bold">{{ number_format($package->volume, 0) }} cm³</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
