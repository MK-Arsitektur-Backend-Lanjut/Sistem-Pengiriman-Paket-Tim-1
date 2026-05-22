@extends('layouts.app')

@section('title', 'Modul 2 - Detail Paket')
@section('meta_description', 'Detail paket dan ringkasan timeline pengiriman.')
@section('active_nav', 'tracking')
@section('requires_auth', '1')

@push('styles')
<style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .navbar-brand { 
            font-weight: bold;
            font-size: 0.9rem;
        }
        .navbar-brand i {
            font-size: 1.2rem;
            margin-right: 0.3rem;
        }
        @media (min-width: 576px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            .navbar-brand i {
                font-size: 1.5rem;
            }
        }
        .badge-status {
            padding: 0.75em 1.25em;
            font-size: 1rem;
            font-weight: bold;
        }
        .info-card { border: 1px solid #e9ecef; }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in-transit { background-color: #0d6efd; }
        .status-in-hub { background-color: #17a2b8; }
        .status-on-delivery { background-color: #fd7e14; }
        .status-delivered { background-color: #28a745; }
        .status-failed { background-color: #dc3545; }
    </style>
@endpush

@section('content')

<div class="container-fluid container-lg pb-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="fw-bold mb-1">{{ $shipment->tracking_number }}</h2>
                    <p class="text-muted mb-0">Terdaftar: {{ $shipment->created_at?->format('d M Y H:i') ?? '-' }}</p>
                </div>
                <span class="badge badge-status status-{{ $shipment->status }}">
                    @switch($shipment->status)
                        @case('pending')
                            Menunggu
                            @break
                        @case('in_transit')
                            Dalam Perjalanan
                            @break
                        @case('in_hub')
                            Di Hub
                            @break
                        @case('on_delivery')
                            Pengiriman
                            @break
                        @case('delivered')
                            Terkirim ✓
                            @break
                        @case('failed')
                            Gagal
                            @break
                    @endswitch
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8 mb-4">
            <!-- Informasi Pengirim -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-fill"></i> Data Pengirim</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-md-6">
                            <p class="text-muted mb-1">Nama</p>
                            <h6 class="fw-bold mb-3">{{ $shipment->sender_name }}</h6>

                            <p class="text-muted mb-1">Telepon</p>
                            <h6 class="fw-bold mb-3">{{ $shipment->sender_phone }}</h6>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="text-muted mb-1">Alamat</p>
                            <h6 class="fw-bold">{{ $shipment->sender_address }}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Penerima -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-check-fill"></i> Data Penerima</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-md-6">
                            <p class="text-muted mb-1">Nama</p>
                            <h6 class="fw-bold mb-3">{{ $shipment->receiver_name }}</h6>

                            <p class="text-muted mb-1">Telepon</p>
                            <h6 class="fw-bold mb-3">{{ $shipment->receiver_phone }}</h6>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="text-muted mb-1">Alamat</p>
                            <h6 class="fw-bold">{{ $shipment->receiver_address }}</h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Paket -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam-fill"></i> Dimensi & Berat Paket</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <p class="text-muted mb-1">Berat</p>
                            <h6 class="fw-bold">{{ $shipment->weight }} kg</h6>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <p class="text-muted mb-1">Panjang</p>
                            <h6 class="fw-bold">{{ $shipment->length }} cm</h6>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <p class="text-muted mb-1">Lebar</p>
                            <h6 class="fw-bold">{{ $shipment->width }} cm</h6>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <p class="text-muted mb-1">Tinggi</p>
                            <h6 class="fw-bold">{{ $shipment->height }} cm</h6>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted mb-1">Volume</p>
                    <h6 class="fw-bold">{{ number_format($shipment->getVolume(), 2) }} Liter</h6>
                </div>
            </div>

            <!-- Rute Pengiriman -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3"></i> Rute Pengiriman</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 g-md-4">
                        <div class="col-12 col-md-5">
                            <div class="text-center text-md-start">
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mx-md-0 mb-2" style="width: 50px; height: 50px;">
                                    <i class="bi bi-geo-alt-fill text-white" style="font-size: 1.5rem;"></i>
                                </div>
                                <p class="text-muted mt-2 small">Asal</p>
                                <h6 class="fw-bold">{{ $shipment->originHub?->name ?? 'Hub #' . $shipment->origin_hub_id }}</h6>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-center justify-content-center">
                            <div class="border-2 border-primary w-100 d-none d-md-block" style="border-top-width: 3px;"></div>
                            <div class="border-2 border-primary h-100 d-md-none" style="border-left-width: 3px; min-height: 60px;"></div>
                        </div>
                        <div class="col-12 col-md-5">
                            <div class="text-center text-md-end">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mx-md-0 mb-2" style="width: 50px; height: 50px;">
                                    <i class="bi bi-geo-alt-fill text-white" style="font-size: 1.5rem;"></i>
                                </div>
                                <p class="text-muted mt-2 small">Tujuan</p>
                                <h6 class="fw-bold">{{ $shipment->destinationHub?->name ?? 'Hub #' . $shipment->destination_hub_id }}</h6>
                            </div>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history"></i> Timeline Singkat</h5>
                </div>
                <div class="card-body">
                    @if($histories->count() > 0)
                        @foreach($histories as $history)
                        <div class="d-flex mb-3">
                            <div class="me-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                    <i class="bi bi-check text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">
                                    @switch($history->status)
                                        @case('pending')
                                            Paket Terdaftar
                                            @break
                                        @case('in_transit')
                                            Dalam Perjalanan
                                            @break
                                        @case('arrived')
                                            Tiba di Hub
                                            @break
                                        @case('out_for_delivery')
                                            Pengiriman Final
                                            @break
                                        @case('delivered')
                                            Berhasil Diterima
                                            @break
                                        @case('failed')
                                            Pengiriman Gagal
                                            @break
                                    @endswitch
                                </h6>
                                <p class="text-muted small mb-1">{{ $history->recorded_at->format('d M Y H:i') }}</p>
                                @if($history->notes)
                                    <p class="text-muted small mb-0">{{ $history->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @if(!$loop->last)
                            <hr>
                        @endif
                        @endforeach
                        <a href="{{ route('tracking.timeline', $shipment->tracking_number) }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                            <i class="bi bi-arrow-right"></i> Lihat Timeline Lengkap
                        </a>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">Belum ada update</p>
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
                        <h6 class="fw-bold">
                            @switch($shipment->status)
                                @case('pending')
                                    Menunggu Pengiriman
                                    @break
                                @case('in_transit')
                                    Dalam Perjalanan
                                    @break
                                @case('in_hub')
                                    Tiba di Hub
                                    @break
                                @case('on_delivery')
                                    Pengiriman ke Penerima
                                    @break
                                @case('delivered')
                                    Sudah Diterima
                                    @break
                                @case('failed')
                                    Pengiriman Gagal
                                    @break
                            @endswitch
                        </h6>
                    </div>
                    @if($shipment->sent_at)
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Tanggal Pengiriman</p>
                        <h6 class="fw-bold">{{ $shipment->sent_at instanceof \DateTime ? $shipment->sent_at->format('d M Y H:i') : \Carbon\Carbon::parse($shipment->sent_at)->format('d M Y H:i') }}</h6>
                    </div>
                    @endif
                    @if($shipment->delivered_at)
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Tanggal Diterima</p>
                        <h6 class="fw-bold">{{ $shipment->delivered_at instanceof \DateTime ? $shipment->delivered_at->format('d M Y H:i') : \Carbon\Carbon::parse($shipment->delivered_at)->format('d M Y H:i') }}</h6>
                    </div>
                    @endif
                    <div>
                        <p class="text-muted mb-1 small">Total Update</p>
                        <h6 class="fw-bold">{{ $histories->count() }} history record</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
