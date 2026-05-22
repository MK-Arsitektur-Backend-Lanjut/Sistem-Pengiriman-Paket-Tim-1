@extends('layouts.app')

@section('title', 'Modul 2 - Timeline Paket')
@section('meta_description', 'Timeline kronologis perjalanan paket dari asal hingga tujuan.')
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
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 19px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #0d6efd, #17a2b8, #28a745);
        }
        @media (min-width: 576px) {
            .timeline::before {
                left: 39px;
            }
        }
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 50px;
        }
        @media (min-width: 576px) {
            .timeline-item {
                padding-left: 100px;
            }
        }
        .timeline-marker {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #0d6efd;
            z-index: 10;
            font-size: 1rem;
        }
        @media (min-width: 576px) {
            .timeline-marker {
                width: 80px;
                height: 80px;
                font-size: 1.5rem;
            }
        }
        .timeline-item:nth-child(odd) .timeline-marker { border-color: #0d6efd; }
        .timeline-item:nth-child(even) .timeline-marker { border-color: #17a2b8; }
        .timeline-item:last-child .timeline-marker { border-color: #28a745; }

        .timeline-content {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        @media (min-width: 576px) {
            .timeline-content {
                padding: 20px;
            }
        }
        .timeline-content h5 {
            margin-bottom: 10px;
            color: #0d6efd;
            font-size: 1rem;
        }
        @media (min-width: 576px) {
            .timeline-content h5 {
                font-size: 1.1rem;
            }
        }
        .timeline-time {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: bold;
        }
        .timeline-date {
            font-size: 1rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 5px 0;
        }
        @media (min-width: 576px) {
            .timeline-date {
                font-size: 1.1rem;
            }
        }
        .timeline-notes {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 3px solid #0d6efd;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        @media (min-width: 576px) {
            .timeline-notes {
                font-size: 0.9rem;
            }
        }
        .tracking-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
@endpush

@section('content')

<div class="container-fluid container-lg pb-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1">Timeline Pengiriman</h2>
            <p class="text-muted">Riwayat kronologis selengkap dari paket Anda</p>
            <div class="mt-3">
                <span class="tracking-number">{{ $shipment->tracking_number }}</span>
                <span class="badge bg-info ms-2">{{ $histories->count() }} Update</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Timeline -->
            <div class="timeline">
                @forelse($histories as $index => $history)
                <div class="timeline-item">
                    <div class="timeline-marker">
                        @switch($history->status)
                            @case('pending')
                                <i class="bi bi-hourglass-split text-warning"></i>
                                @break
                            @case('in_transit')
                                <i class="bi bi-truck text-primary"></i>
                                @break
                            @case('arrived')
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
                            @default
                                <i class="bi bi-question-circle"></i>
                        @endswitch
                    </div>
                    <div class="timeline-content">
                        <h5>
                            @switch($history->status)
                                @case('pending')
                                    Paket Terdaftar
                                    @break
                                @case('in_transit')
                                    Dalam Perjalanan
                                    @break
                                @case('arrived')
                                    Tiba di Hub Transit
                                    @break
                                @case('out_for_delivery')
                                    Dalam Pengiriman Final
                                    @break
                                @case('delivered')
                                    Berhasil Diterima
                                    @break
                                @case('failed')
                                    Pengiriman Gagal
                                    @break
                                @default
                                    Update Paket
                            @endswitch
                        </h5>
                        <div class="timeline-date">
                            {{ $history->recorded_at instanceof \DateTime ? $history->recorded_at->format('d M Y') : \Carbon\Carbon::parse($history->recorded_at)->format('d M Y') }}
                        </div>
                        <div class="timeline-time">
                            <i class="bi bi-clock"></i> {{ $history->recorded_at instanceof \DateTime ? $history->recorded_at->format('H:i:s') : \Carbon\Carbon::parse($history->recorded_at)->format('H:i:s') }}
                        </div>
                        
                        @if($history->from_hub_id || $history->to_hub_id)
                        <div class="timeline-notes mt-3">
                            <strong><i class="bi bi-diagram-3"></i> Rute:</strong><br>
                            @if($history->from_hub_id)
                                <span class="badge bg-light text-dark">Dari: Hub #{{ $history->from_hub_id }}</span>
                            @endif
                            @if($history->to_hub_id)
                                <span class="badge bg-light text-dark">Ke: Hub #{{ $history->to_hub_id }}</span>
                            @endif
                        </div>
                        @endif

                        @if($history->notes)
                        <div class="timeline-notes">
                            <strong><i class="bi bi-chat-left-text"></i> Catatan:</strong><br>
                            {{ $history->notes }}
                        </div>
                        @endif

                        <div class="timeline-notes" style="border-left-color: #6c757d; background: #f0f0f0; margin-top: 10px;">
                            <small><i class="bi bi-info-circle"></i> Dicatat: {{ $history->created_at instanceof \DateTime ? $history->created_at->format('d M Y H:i:s') : \Carbon\Carbon::parse($history->created_at)->format('d M Y H:i:s') }}</small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Belum ada riwayat untuk paket ini
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <!-- Ringkasan -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle"></i> Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Status Terakhir</p>
                        <h6 class="fw-bold">{{ $histories->first()?->status ?? '-' }}</h6>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Update Pertama</p>
                        <h6 class="fw-bold">{{ $histories->last() ? ($histories->last()->recorded_at instanceof \DateTime ? $histories->last()->recorded_at->format('d M Y H:i') : \Carbon\Carbon::parse($histories->last()->recorded_at)->format('d M Y H:i')) : '-' }}</h6>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-1 small">Update Terakhir</p>
                        <h6 class="fw-bold">{{ $histories->first() ? ($histories->first()->recorded_at instanceof \DateTime ? $histories->first()->recorded_at->format('d M Y H:i') : \Carbon\Carbon::parse($histories->first()->recorded_at)->format('d M Y H:i')) : '-' }}</h6>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Updated</p>
                        <h6 class="fw-bold">{{ $histories->count() }} Kali</h6>
                    </div>
                </div>
            </div>

            <!-- Data Pengirim -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person"></i> Pengirim</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">{{ $shipment->sender_name }}</h6>
                    <p class="text-muted mb-1 small">{{ $shipment->sender_phone }}</p>
                    <p class="text-muted small">{{ $shipment->sender_address }}</p>
                </div>
            </div>

            <!-- Data Penerima -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-check"></i> Penerima</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">{{ $shipment->receiver_name }}</h6>
                    <p class="text-muted mb-1 small">{{ $shipment->receiver_phone }}</p>
                    <p class="text-muted small">{{ $shipment->receiver_address }}</p>
                </div>
            </div>

            <!-- Paket -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box"></i> Paket</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <p class="text-muted mb-1 small">Berat</p>
                        <h6 class="fw-bold">{{ $shipment->weight }} kg</h6>
                    </div>
                    <div class="mb-2">
                        <p class="text-muted mb-1 small">Dimensi</p>
                        <h6 class="fw-bold">{{ $shipment->getDimensions() }}</h6>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Volume</p>
                        <h6 class="fw-bold">{{ number_format($shipment->getVolume(), 2) }} L</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
