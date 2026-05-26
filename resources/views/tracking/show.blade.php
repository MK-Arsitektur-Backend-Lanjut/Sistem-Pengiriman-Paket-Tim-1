@extends('layouts.app')

@section('title', 'Detail Paket - ' . $package->tracking_number)
@section('meta_description', 'Detail paket dan ringkasan timeline pengiriman.')
@section('active_nav', 'tracking')
@section('requires_auth', '1')

@push('styles')
<style>
    body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
    .badge-status { padding: 0.75em 1.25em; font-size: 1rem; font-weight: bold; }
    .info-card { border: 1px solid #e9ecef; }

    .status-registered       { background-color: #6c757d; color: #fff; }
    .status-picked_up        { background-color: #17a2b8; color: #fff; }
    .status-in_transit       { background-color: #0d6efd; color: #fff; }
    .status-arrived_at_hub   { background-color: #6610f2; color: #fff; }
    .status-out_for_delivery { background-color: #fd7e14; color: #fff; }
    .status-delivered        { background-color: #28a745; color: #fff; }
    .status-failed           { background-color: #dc3545; color: #fff; }
    .status-returned         { background-color: #343a40; color: #fff; }

    .timeline-dot {
        width: 40px; height: 40px; flex-shrink: 0;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
</style>
@endpush

@section('content')
@php
    $currentStatus = $package->latestLog?->status ?? $package->package_status;
    $statusLabel = match($currentStatus) {
        'registered'       => 'Terdaftar',
        'picked_up'        => 'Dijemput Armada',
        'in_transit'       => 'Dalam Perjalanan',
        'arrived_at_hub'   => 'Tiba di Hub',
        'out_for_delivery' => 'Sedang Diantar',
        'delivered'        => 'Terkirim ✓',
        'failed'           => 'Gagal',
        'returned'         => 'Dikembalikan',
        default            => $currentStatus,
    };
@endphp

<div class="container-fluid container-lg pb-5">
    <!-- Breadcrumb & Back -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('tracking.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-1" style="font-family: 'Courier New', monospace;">
                        {{ $package->tracking_number }}
                    </h2>
                    <p class="text-muted mb-0">Terdaftar: {{ $package->created_at?->format('d M Y H:i') ?? '-' }}</p>
                </div>
                <span class="badge badge-status status-{{ $currentStatus }}">
                    {{ $statusLabel }}
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8 mb-4">

            <!-- Pengirim & Penerima -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill"></i> Data Pengirim & Penerima</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <p class="text-muted mb-1 small text-uppercase fw-bold">Pengirim</p>
                            <h6 class="fw-bold">{{ $package->sender_name }}</h6>
                            <p class="text-muted mb-0"><i class="bi bi-geo-alt-fill"></i> {{ $package->origin }}</p>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="text-muted mb-1 small text-uppercase fw-bold">Penerima</p>
                            <h6 class="fw-bold">{{ $package->receiver_name }}</h6>
                            <p class="text-muted mb-0"><i class="bi bi-geo-alt-fill"></i> {{ $package->destination }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dimensi & Berat -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam-fill"></i> Dimensi & Berat Paket</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 g-md-3">
                        <div class="col-6 col-sm-3">
                            <p class="text-muted mb-1 small">Berat</p>
                            <h6 class="fw-bold">{{ $package->weight }} kg</h6>
                        </div>
                        <div class="col-6 col-sm-3">
                            <p class="text-muted mb-1 small">Panjang</p>
                            <h6 class="fw-bold">{{ $package->length }} cm</h6>
                        </div>
                        <div class="col-6 col-sm-3">
                            <p class="text-muted mb-1 small">Lebar</p>
                            <h6 class="fw-bold">{{ $package->width }} cm</h6>
                        </div>
                        <div class="col-6 col-sm-3">
                            <p class="text-muted mb-1 small">Tinggi</p>
                            <h6 class="fw-bold">{{ $package->height }} cm</h6>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Volume</p>
                            <h6 class="fw-bold">{{ number_format($package->volume, 0) }} cm³</h6>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Kategori</p>
                            <h6 class="fw-bold">{{ ucfirst($package->getDimensionCategory()) }}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rute: Origin → Destination (kota) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3"></i> Rute Pengiriman</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 g-md-4 align-items-center">
                        <div class="col-12 col-md-5 text-center text-md-start">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mx-md-0 mb-2" style="width: 50px; height: 50px;">
                                <i class="bi bi-geo-alt-fill text-white" style="font-size: 1.5rem;"></i>
                            </div>
                            <p class="text-muted mt-1 small mb-0">Asal</p>
                            <h6 class="fw-bold">{{ $package->origin }}</h6>
                            @if($package->warehouse)
                                <small class="text-muted">Gudang: {{ $package->warehouse->warehouse_name }}</small>
                            @endif
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-right-circle-fill text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div class="col-12 col-md-5 text-center text-md-end">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 50px; height: 50px;">
                                <i class="bi bi-geo-alt-fill text-white" style="font-size: 1.5rem;"></i>
                            </div>
                            <p class="text-muted mt-1 small mb-0">Tujuan</p>
                            <h6 class="fw-bold">{{ $package->destination }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Timeline Singkat -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history"></i> Riwayat Singkat</h5>
                </div>
                <div class="card-body">
                    @if($logs->count() > 0)
                        @foreach($logs->take(5) as $log)
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <div class="timeline-dot bg-primary">
                                    <i class="bi bi-check text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">
                                    {{ $log->status_label }}
                                </h6>
                                <p class="text-muted small mb-1">
                                    {{ $log->recorded_at?->format('d M Y H:i') }}
                                </p>
                                @if($log->hub)
                                    <p class="text-muted small mb-1">
                                        <i class="bi bi-building"></i> {{ $log->hub->name }}
                                    </p>
                                @endif
                                @if($log->notes)
                                    <p class="text-muted small mb-0">{{ $log->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @if(!$loop->last) <hr> @endif
                        @endforeach

                        @if($logs->count() > 5)
                            <p class="text-muted small text-center mb-2">
                                + {{ $logs->count() - 5 }} update lainnya
                            </p>
                        @endif

                        <a href="{{ route('tracking.timeline', $package->tracking_number) }}"
                           class="btn btn-sm btn-outline-primary w-100 mt-2">
                            <i class="bi bi-arrow-right"></i> Lihat Timeline Lengkap
                        </a>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">Belum ada update tracking</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Info Tambahan -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle"></i> Informasi</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Status Saat Ini</p>
                        <h6 class="fw-bold">{{ $statusLabel }}</h6>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Tanggal Daftar</p>
                        <h6 class="fw-bold">{{ $package->created_at?->format('d M Y H:i') ?? '-' }}</h6>
                    </div>
                    @if($package->latestLog)
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Update Terakhir</p>
                        <h6 class="fw-bold">{{ $package->latestLog->recorded_at?->format('d M Y H:i') ?? '-' }}</h6>
                    </div>
                    @endif
                    <div>
                        <p class="text-muted mb-1 small">Total Log Tracking</p>
                        <h6 class="fw-bold">{{ $logs->count() }} record</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
