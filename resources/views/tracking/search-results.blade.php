@extends('layouts.app')

@section('title', 'Hasil Pencarian - ' . $keyword)
@section('meta_description', 'Hasil pencarian paket berdasarkan kata kunci tracking.')
@section('active_nav', 'tracking')
@section('requires_auth', '1')

@push('styles')
<style>
    body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
    .result-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        transition: all 0.3s;
        cursor: pointer;
    }
    .result-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .tracking-number { font-family: 'Courier New', monospace; font-weight: bold; color: #0d6efd; }
    .status-badge { padding: 0.4em 0.7em; font-size: 0.8em; font-weight: bold; }

    .status-registered       { background-color: #6c757d; color: #fff; }
    .status-picked_up        { background-color: #17a2b8; color: #fff; }
    .status-in_transit       { background-color: #0d6efd; color: #fff; }
    .status-arrived_at_hub   { background-color: #6610f2; color: #fff; }
    .status-out_for_delivery { background-color: #fd7e14; color: #fff; }
    .status-delivered        { background-color: #28a745; color: #fff; }
    .status-failed           { background-color: #dc3545; color: #fff; }
    .status-returned         { background-color: #343a40; color: #fff; }

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
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('tracking.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1"><i class="bi bi-search"></i> Hasil Pencarian</h2>
            <p class="text-muted">
                Ditemukan <strong>{{ $results->total() }}</strong> paket untuk
                "<strong>{{ $keyword }}</strong>"
            </p>
        </div>
    </div>

    @if($results->count() > 0)
    <div class="row">
        @foreach($results as $package)
        @php $currentStatus = $package->latestLog?->status ?? $package->package_status; @endphp
        <div class="col-12 mb-3">
            <a href="{{ route('tracking.show', $package->tracking_number) }}"
               class="text-decoration-none text-dark">
                <div class="result-card p-4 bg-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="mb-2">
                                <span class="tracking-number">{{ $package->tracking_number }}</span>
                                <span class="badge status-badge status-{{ $currentStatus }} ms-2">
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

                            <div class="row g-2 g-md-3">
                                <div class="col-12 col-md-4">
                                    <small class="text-muted">Pengirim</small>
                                    <h6 class="fw-bold mb-0">{{ $package->sender_name }}</h6>
                                    <small class="text-muted"><i class="bi bi-geo-alt-fill"></i> {{ $package->origin }}</small>
                                </div>
                                <div class="col-12 col-md-4">
                                    <small class="text-muted">Penerima</small>
                                    <h6 class="fw-bold mb-0">{{ $package->receiver_name }}</h6>
                                    <small class="text-muted"><i class="bi bi-geo-alt-fill"></i> {{ $package->destination }}</small>
                                </div>
                                <div class="col-12 col-md-4">
                                    <small class="text-muted">Berat / Dimensi</small>
                                    <h6 class="fw-bold mb-0">{{ $package->weight }} kg</h6>
                                    <small class="text-muted">{{ $package->length }}×{{ $package->width }}×{{ $package->height }} cm</small>
                                </div>
                            </div>

                            <hr class="my-2">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i>
                                Terdaftar: {{ $package->created_at?->format('d M Y H:i') ?? '-' }}
                            </small>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-arrow-right"></i> Lihat Detail
                            </button>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-center mt-4 mb-4">
        {{ $results->links() }}
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-4 fw-bold">Paket Tidak Ditemukan</h4>
                <p class="text-muted mb-3">Tidak ada paket yang cocok dengan kata kunci "<strong>{{ $keyword }}</strong>"</p>
                <a href="{{ route('tracking.index') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
