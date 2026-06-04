@extends('layouts.app')

@section('title', 'Modul 2 - Cari Paket')
@section('meta_description', 'Halaman pencarian paket menggunakan nomor resi atau data pengirim.')
@section('active_nav', 'tracking')
@section('requires_auth', '1')

@push('styles')
<style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: calc(100vh - 180px);
            font-family: 'Inter', sans-serif;
        }
        .tracking-search-wrap {
            min-height: calc(100vh - 240px);
            display: flex;
            align-items: center;
        }
        .search-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px 20px;
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }
        @media (min-width: 576px) {
            .search-container {
                padding: 50px 40px;
            }
        }
        .search-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        @media (min-width: 576px) {
            .search-icon {
                font-size: 4rem;
            }
        }
        .search-form input {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        @media (min-width: 576px) {
            .search-form input {
                padding: 15px 20px;
                font-size: 1.1rem;
            }
        }
        .search-form input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-search {
            border-radius: 12px;
            padding: 12px 30px;
            font-size: 0.95rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: transform 0.2s;
        }
        @media (min-width: 576px) {
            .btn-search {
                padding: 15px 40px;
                font-size: 1rem;
            }
        }
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
@endpush

@section('content')

<div class="container tracking-search-wrap py-4">
    <div class="w-100">
        <div class="mb-3">
            <a href="{{ route('tracking.index') }}" class="text-white text-decoration-none fw-semibold">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Tracking
            </a>
        </div>

        <div class="search-container">
        <div class="text-center mb-4">
            <i class="bi bi-search search-icon"></i>
            <h1 class="fw-bold mb-2">Lacak Paket Anda</h1>
            <p class="text-muted mb-0">Masukkan nomor resi atau nama pengirim untuk melacak paket</p>
        </div>

        <form method="POST" action="{{ route('tracking.doSearch') }}" class="search-form">
            @csrf
            <div class="mb-4">
                <input 
                    type="text" 
                    class="form-control form-control-lg" 
                    name="keyword" 
                    placeholder="Nomor Resi / Nama Pengirim / Telepon..."
                    autofocus
                    autocomplete="off"
                    id="searchInput"
                >
                <div id="suggestions" class="list-group mt-2" style="display: none; max-height: 250px; overflow-y: auto;"></div>
            </div>

            <button type="submit" class="btn btn-search text-white w-100 fw-bold">
                <i class="bi bi-search"></i> Cari Paket
            </button>
        </form>

        <div class="mt-5 pt-3 border-top">
            <p class="text-muted small text-center mb-3">💡 Tips Pencarian:</p>
            <ul class="text-muted small" style="list-style: none; padding: 0;">
                <li><i class="bi bi-check-circle text-success"></i> Cari dengan nomor resi lengkap</li>
                <li><i class="bi bi-check-circle text-success"></i> Atau gunakan nama pengirim/penerima</li>
                <li><i class="bi bi-check-circle text-success"></i> Nomor telepon juga bisa digunakan</li>
            </ul>
        </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Autocomplete search
    const searchInput = document.getElementById('searchInput');
    const suggestionsList = document.getElementById('suggestions');

    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value.trim();
        
        if (query.length < 3) {
            suggestionsList.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`/api/tracking/search?q=${encodeURIComponent(query)}`);
            const results = await response.json();

            if (results.length > 0) {
                suggestionsList.innerHTML = results.map(item => `
                    <a href="/tracking/${item.tracking_number}" class="list-group-item list-group-item-action">
                        <strong>${item.tracking_number}</strong><br>
                        <small class="text-muted">${item.sender_name} → ${item.receiver_name}</small>
                    </a>
                `).join('');
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Hide suggestions on focus out
    document.addEventListener('click', (e) => {
        if (e.target !== searchInput) {
            suggestionsList.style.display = 'none';
        }
    });
</script>
@endpush
@endsection
