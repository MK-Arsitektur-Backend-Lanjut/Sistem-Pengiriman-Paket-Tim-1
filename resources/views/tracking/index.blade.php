@extends('layouts.app')

@section('title', 'Modul 2 - Tracking System')
@section('meta_description', 'Monitoring dan pelacakan status pengiriman paket secara real-time.')
@section('active_nav', 'tracking')
@section('requires_auth', '1')

@push('styles')
<style>
    body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
    .card-stat {
        border-left: 4px solid #0d6efd;
        transition: 0.3s;
        background: white;
    }
    .card-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .status-badge { font-size: 0.8em; padding: 0.4em 0.7em; }

    /* Status colors — sesuai shipment_logs.status */
    .status-registered       { background-color: #6c757d; color: #fff; }
    .status-picked_up        { background-color: #17a2b8; color: #fff; }
    .status-in_transit       { background-color: #0d6efd; color: #fff; }
    .status-arrived_at_hub   { background-color: #6610f2; color: #fff; }
    .status-out_for_delivery { background-color: #fd7e14; color: #fff; }
    .status-delivered        { background-color: #28a745; color: #fff; }
    .status-failed           { background-color: #dc3545; color: #fff; }
    .status-returned         { background-color: #343a40; color: #fff; }

    .package-row { border-bottom: 1px solid #e9ecef; padding: 1rem 0; }
    .package-row:hover { background-color: #f8f9fa; }
    .tracking-number {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        color: #0d6efd;
    }
    .d-flex nav .pagination {
        --bs-pagination-padding-x: 0.4rem;
        --bs-pagination-padding-y: 0.25rem;
        --bs-pagination-font-size: 0.8rem;
        gap: 0.2rem;
    }
    .d-flex nav .pagination .page-link { min-width: auto; line-height: 1; }
    .d-flex nav .pagination svg { width: 0.75rem; height: 0.75rem; }
</style>
@endpush

@section('content')

<div class="container-fluid container-lg pb-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-2 fw-bold"><i class="bi bi-search"></i> Sistem Pelacakan Paket</h2>
            <p class="text-muted">Pantau status pengiriman paket secara real-time dengan riwayat kronologis perjalanan dari gudang asal hingga tujuan.</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-6 col-lg-3 mb-3">
            <div class="card card-stat border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-2 p-md-3">
                    <div class="me-2 me-md-3" style="color: #6c757d; font-size: 1.5rem;">
                        <i class="bi bi-box"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.7rem;">Total Paket</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
            <div class="card card-stat border-0 shadow-sm h-100" style="border-left-color: #ffc107;">
                <div class="card-body d-flex align-items-center p-2 p-md-3">
                    <div class="me-2 me-md-3" style="color: #ffc107; font-size: 1.5rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.7rem;">Terdaftar</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['registered'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
            <div class="card card-stat border-0 shadow-sm h-100" style="border-left-color: #0d6efd;">
                <div class="card-body d-flex align-items-center p-2 p-md-3">
                    <div class="me-2 me-md-3" style="color: #0d6efd; font-size: 1.5rem;">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.7rem;">Dalam Perjalanan</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['in_transit'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 mb-3">
            <div class="card card-stat border-0 shadow-sm h-100" style="border-left-color: #28a745;">
                <div class="card-body d-flex align-items-center p-2 p-md-3">
                    <div class="me-2 me-md-3" style="color: #28a745; font-size: 1.5rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-0 text-uppercase fw-bold" style="font-size: 0.7rem;">Terkirim</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($stats['delivered'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('tracking.index') }}" class="row g-2 g-md-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">Cari Paket</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="No. Resi / Pengirim / Penerima / Kota"
                                   value="{{ $search }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">-- Semua --</option>
                                <option value="registered"       {{ $status == 'registered'       ? 'selected' : '' }}>Terdaftar</option>
                                <option value="picked_up"        {{ $status == 'picked_up'        ? 'selected' : '' }}>Dijemput</option>
                                <option value="in_transit"       {{ $status == 'in_transit'       ? 'selected' : '' }}>Dalam Perjalanan</option>
                                <option value="arrived_at_hub"   {{ $status == 'arrived_at_hub'   ? 'selected' : '' }}>Tiba di Hub</option>
                                <option value="out_for_delivery" {{ $status == 'out_for_delivery' ? 'selected' : '' }}>Sedang Diantar</option>
                                <option value="delivered"        {{ $status == 'delivered'        ? 'selected' : '' }}>Terkirim</option>
                                <option value="failed"           {{ $status == 'failed'           ? 'selected' : '' }}>Gagal</option>
                                <option value="returned"         {{ $status == 'returned'         ? 'selected' : '' }}>Dikembalikan</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-search"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Package List -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-list-ul"></i>
                        Daftar Paket ({{ $packages->total() }} total)
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($packages->count() > 0)
                        @foreach($packages as $package)
                        @php $currentStatus = $package->latestLog?->status ?? $package->package_status; @endphp
                        <div class="package-row px-3 py-3 d-flex flex-column flex-md-row justify-content-between align-items-start">
                            <div class="flex-grow-1 mb-3 mb-md-0">
                                <div class="mb-2">
                                    <span class="tracking-number d-block d-md-inline">{{ $package->tracking_number }}</span>
                                    <span class="badge status-badge status-{{ str_replace('_', '_', $currentStatus) }} ms-0 ms-md-2 mt-2 mt-md-0 d-inline-block">
                                        @switch($currentStatus)
                                            @case('registered')       Terdaftar        @break
                                            @case('picked_up')        Dijemput         @break
                                            @case('in_transit')       Dalam Perjalanan @break
                                            @case('arrived_at_hub')   Tiba di Hub      @break
                                            @case('out_for_delivery') Sedang Diantar   @break
                                            @case('delivered')        Terkirim         @break
                                            @case('failed')           Gagal            @break
                                            @case('returned')         Dikembalikan     @break
                                            @default                  {{ $currentStatus }}
                                        @endswitch
                                    </span>
                                </div>

                                <div class="row g-2 g-lg-3 text-muted" style="font-size: 0.9rem;">
                                    <div class="col-12 col-sm-6 col-lg-auto">
                                        <strong><i class="bi bi-person-fill"></i> Pengirim:</strong>
                                        {{ $package->sender_name }}<br>
                                        <strong><i class="bi bi-geo-alt-fill"></i></strong>
                                        {{ $package->origin }}
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-auto">
                                        <strong><i class="bi bi-person-check-fill"></i> Penerima:</strong>
                                        {{ $package->receiver_name }}<br>
                                        <strong><i class="bi bi-geo-alt-fill"></i></strong>
                                        {{ $package->destination }}
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-auto">
                                        <strong><i class="bi bi-box-seam-fill"></i> Berat:</strong>
                                        {{ $package->weight }} kg<br>
                                        <strong><i class="bi bi-bounding-box"></i></strong>
                                        {{ $package->length }}×{{ $package->width }}×{{ $package->height }} cm
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-auto">
                                        <strong><i class="bi bi-calendar"></i> Terdaftar:</strong>
                                        {{ $package->created_at?->format('d M Y') ?? '-' }}<br>
                                        @if($package->latestLog)
                                            <strong>Update:</strong>
                                            {{ $package->latestLog->recorded_at?->format('d M Y H:i') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="ms-0 ms-md-3 w-100 w-md-auto d-flex gap-2 flex-column flex-sm-row">
                                <a href="{{ route('tracking.show', $package->tracking_number) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <a href="{{ route('tracking.timeline', $package->tracking_number) }}"
                                   class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-diagram-3"></i> Timeline
                                </a>
                            </div>
                        </div>
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4 mb-4">
                            {{ $packages->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Tidak ada paket ditemukan</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
