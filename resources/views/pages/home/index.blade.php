@extends('layouts.app')

@section('title', 'SiPaket Tim 1 - Sistem Pengiriman Paket')
@section('meta_description', 'Dashboard utama Sistem Pengiriman Paket Tim 1 - kelola armada, pantau gudang, lacak kiriman, dan hitung ongkos kirim.')
@section('active_nav', 'home')

@push('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%);
        padding: 5rem 0 4rem;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .hero-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #bfdbfe;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.35rem 1rem;
        border-radius: 50px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 1.25rem;
    }

    .hero-title {
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 800;
        color: #ffffff;
        line-height: 1.15;
        letter-spacing: -0.5px;
        margin-bottom: 1rem;
    }

    .hero-subtitle {
        color: #bfdbfe;
        font-size: 1.05rem;
        max-width: 520px;
        line-height: 1.7;
        margin-bottom: 2rem;
    }

    .hero-stats {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .hero-stat-num {
        font-size: 1.75rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }

    .hero-stat-label {
        font-size: 0.8rem;
        color: #93c5fd;
        font-weight: 500;
        margin-top: 0.2rem;
    }

    .hero-icon-block {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        padding: 2.5rem;
        text-align: center;
    }

    .hero-icon-block i {
        font-size: 5rem;
        color: rgba(255, 255, 255, 0.85);
        display: block;
    }

    .hero-icon-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1.25rem;
    }

    .hero-icon-mini {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        font-size: 0.78rem;
        color: #bfdbfe;
        font-weight: 500;
    }

    .hero-icon-mini i {
        display: block;
        font-size: 1.4rem;
        margin-bottom: 0.3rem;
        color: #93c5fd;
    }

    .section-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--brand-primary);
        margin-bottom: 0.5rem;
    }

    .section-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.3px;
    }

    .module-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s;
        height: 100%;
        text-decoration: none;
        display: block;
        color: inherit;
    }

    .module-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(37, 99, 235, 0.12);
        border-color: var(--brand-accent);
        color: inherit;
    }

    .module-card-header {
        padding: 1.75rem 1.75rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 1.1rem;
    }

    .module-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .module-card-body {
        padding: 0 1.75rem 1.5rem;
    }

    .module-number {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
    }

    .module-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0;
    }

    .module-desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.65;
        margin-bottom: 1.25rem;
    }

    .feature-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.3rem 0.7rem;
        border-radius: 50px;
        margin: 0.2rem 0.15rem;
    }

    .module-footer {
        border-top: 1px solid var(--border);
        padding: 1rem 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .module-cta {
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--brand-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        transition: gap 0.2s;
    }

    .module-card:hover .module-cta {
        gap: 0.6rem;
    }

    .mod-1 .module-icon-wrap {
        background: #fff7ed;
        color: #ea580c;
    }

    .mod-1 .module-number {
        color: #ea580c;
    }

    .mod-1 .feature-pill {
        background: #fff7ed;
        color: #ea580c;
        border: 1px solid #fed7aa;
    }

    .mod-2 .module-icon-wrap {
        background: #f0fdf4;
        color: #16a34a;
    }

    .mod-2 .module-number {
        color: #16a34a;
    }

    .mod-2 .feature-pill {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .mod-3 .module-icon-wrap {
        background: #fdf4ff;
        color: #9333ea;
    }

    .mod-3 .module-number {
        color: #9333ea;
    }

    .mod-3 .feature-pill {
        background: #fdf4ff;
        color: #9333ea;
        border: 1px solid #e9d5ff;
    }

    .mod-4 .module-icon-wrap {
        background: #eff6ff;
        color: #2563eb;
    }

    .mod-4 .module-number {
        color: #2563eb;
    }

    .mod-4 .feature-pill {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    .api-section {
        background: #0f172a;
        border-radius: 20px;
        padding: 2.5rem;
        color: #e2e8f0;
    }

    .api-section h2 {
        color: #fff;
        font-weight: 800;
    }

    .api-badge {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        text-transform: uppercase;
    }

    .api-badge.get {
        background: #064e3b;
        color: #6ee7b7;
    }

    .api-badge.post {
        background: #1e3a8a;
        color: #93c5fd;
    }

    .api-badge.put {
        background: #78350f;
        color: #fcd34d;
    }

    .api-badge.patch {
        background: #4a1d96;
        color: #c4b5fd;
    }

    .api-endpoint {
        font-family: 'Courier New', monospace;
        font-size: 0.82rem;
        color: #94a3b8;
    }

    .api-endpoint span {
        color: #e2e8f0;
    }

    .api-row {
        padding: 0.6rem 0;
        border-bottom: 1px solid #1e293b;
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex-wrap: wrap;
    }

    .api-row:last-child {
        border-bottom: none;
    }

    .api-group-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #475569;
        padding: 1rem 0 0.5rem;
    }

    .stack-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.45rem 0.9rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--card-bg);
        color: var(--text-main);
        transition: border-color 0.2s;
    }

    .stack-badge:hover {
        border-color: var(--brand-accent);
    }
</style>
@endpush

@section('content')
<section class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center gy-4">
            <div class="col-lg-7">
                <div class="hero-badge">
                    <i class="bi bi-stars me-1"></i> Arsitektur Backend Lanjut - UTS 2026
                </div>
                <h1 class="hero-title">
                    Sistem Pengiriman<br>Paket <span style="color: #93c5fd;">Terintegrasi</span>
                </h1>
                <p class="hero-subtitle">
                    Platform logistik end-to-end untuk mengelola gudang, melacak kiriman, mengautentikasi pelanggan, dan memantau armada kendaraan secara real-time.
                </p>
                <div class="hero-stats">
                    <div>
                        <div class="hero-stat-num">4</div>
                        <div class="hero-stat-label">Modul Aktif</div>
                    </div>
                    <div style="border-left: 1px solid rgba(255, 255, 255, 0.2); padding-left: 1.5rem;">
                        <div class="hero-stat-num">25K+</div>
                        <div class="hero-stat-label">Data Pengiriman</div>
                    </div>
                    <div style="border-left: 1px solid rgba(255, 255, 255, 0.2); padding-left: 1.5rem;">
                        <div class="hero-stat-num">16+</div>
                        <div class="hero-stat-label">REST API Endpoint</div>
                    </div>
                    <div style="border-left: 1px solid rgba(255, 255, 255, 0.2); padding-left: 1.5rem;">
                        <div class="hero-stat-num">50</div>
                        <div class="hero-stat-label">Hub Nasional</div>
                    </div>
                </div>

                {{-- CTA selalu tampil, arahkan ke login --}}
                <div class="mt-4">
                    <a href="{{ url('/auth/login') }}" class="btn btn-light fw-bold px-4 py-2 me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login ke Akun
                    </a>
                    <a href="{{ url('/auth/register') }}" class="btn btn-outline-light fw-bold px-4 py-2">
                        <i class="bi bi-person-plus me-1"></i> Daftar Gratis
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-icon-block">
                    <i class="bi bi-boxes"></i>
                    <div class="hero-icon-grid">
                        <div class="hero-icon-mini"><i class="bi bi-building"></i>Warehouse</div>
                        <div class="hero-icon-mini"><i class="bi bi-geo-alt"></i>Tracking</div>
                        <div class="hero-icon-mini"><i class="bi bi-person-check"></i>Auth API</div>
                        <div class="hero-icon-mini"><i class="bi bi-truck"></i>Fleet</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="mb-5">
            <div class="section-label">Navigasi Modul</div>
            <h2 class="section-title">Fitur Unggulan Aplikasi</h2>
            <p class="text-secondary mt-2" style="max-width: 520px; font-size: 0.925rem;">
                Setiap modul dirancang dengan arsitektur Repository Pattern, validasi ketat, dan REST API yang terdokumentasi.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <a href="{{ url('/module-1-monitor') }}" class="module-card mod-1">
                    <div class="module-card-header">
                        <div class="module-icon-wrap"><i class="bi bi-building-fill"></i></div>
                        <div>
                            <div class="module-number">Modul 01</div>
                            <div class="module-title">Warehouse & Package</div>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <p class="module-desc">Monitoring inventaris gudang secara real-time, manajemen paket masuk/keluar, dan laporan kapasitas gudang.</p>
                        <div>
                            <span class="feature-pill"><i class="bi bi-check2"></i>10 Gudang</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>30+ Paket</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>CRUD API</span>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Warehouse</span>
                        <span class="module-cta">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ url('/tracking') }}" class="module-card mod-2">
                    <div class="module-card-header">
                        <div class="module-icon-wrap"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="module-number">Modul 02</div>
                            <div class="module-title">Tracking System</div>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <p class="module-desc">Lacak status pengiriman secara detail menggunakan nomor resi dengan timeline perjalanan paket.</p>
                        <div>
                            <span class="feature-pill"><i class="bi bi-check2"></i>25K Shipments</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>Timeline</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>History</span>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span class="badge bg-success" style="font-size: 0.7rem;">Live Track</span>
                        <span class="module-cta">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                {{-- Module 3: link langsung ke auth/login (tanpa overlay JS) --}}
                <a href="{{ url('/auth/login') }}" class="module-card mod-3">
                    <div class="module-card-header">
                        <div class="module-icon-wrap"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <div class="module-number">Modul 03</div>
                            <div class="module-title">Auth & Kalkulator</div>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <p class="module-desc">Autentikasi pelanggan dengan JWT Token, profile pengiriman, dan kalkulasi ongkir dinamis.</p>
                        <div>
                            <span class="feature-pill"><i class="bi bi-check2"></i>JWT Login</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>Bearer Token</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>Ongkir</span>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span class="badge text-white" style="background: #9333ea; font-size: 0.7rem;">JWT Auth</span>
                        <span class="module-cta">Login untuk akses <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ url('/fleet') }}" class="module-card mod-4">
                    <div class="module-card-header">
                        <div class="module-icon-wrap"><i class="bi bi-truck-front-fill"></i></div>
                        <div>
                            <div class="module-number">Modul 04</div>
                            <div class="module-title">Fleet & Hub</div>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <p class="module-desc">Pantau dan kelola armada kendaraan secara real-time. Update status, relokasi, dan cek laporan transit.</p>
                        <div>
                            <span class="feature-pill"><i class="bi bi-check2"></i>Live Status</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>Relocate</span>
                            <span class="feature-pill"><i class="bi bi-check2"></i>Transit Report</span>
                        </div>
                    </div>
                    <div class="module-footer">
                        <span class="badge bg-primary" style="font-size: 0.7rem;">Real-time</span>
                        <span class="module-cta">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background: #f1f5f9;">
    <div class="container">
        <div class="api-section">
            <div class="section-label" style="color: #93c5fd;">REST API</div>
            <h2 class="mb-1" style="font-size: 1.5rem;">Daftar Endpoint Backend</h2>
            <p style="color: #64748b; font-size: 0.875rem;">Semua endpoint tersedia di bawah prefix <code style="color: #7dd3fc;">/api/v1</code></p>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div class="api-group-title">Modul 1 - Warehouse & Package</div>
                    <div class="api-row"><span class="api-badge get">GET</span><span class="api-endpoint">/api/v1/<span>warehouse</span></span></div>
                    <div class="api-row"><span class="api-badge post">POST</span><span class="api-endpoint">/api/v1/<span>warehouse</span></span></div>
                    <div class="api-row"><span class="api-badge put">PUT</span><span class="api-endpoint">/api/v1/<span>warehouse/{id}</span></span></div>
                </div>
                <div class="col-md-6">
                    <div class="api-group-title">Modul 4 - Fleet & Hub</div>
                    <div class="api-row"><span class="api-badge get">GET</span><span class="api-endpoint">/api/v1/<span>fleet</span></span></div>
                    <div class="api-row"><span class="api-badge post">POST</span><span class="api-endpoint">/api/v1/<span>fleet</span></span></div>
                    <div class="api-row"><span class="api-badge put">PUT</span><span class="api-endpoint">/api/v1/<span>fleet/{id}/status</span></span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="section-label">Tech Stack</div>
                <h2 class="section-title mb-3">Dibangun dengan<br>Teknologi Modern</h2>
                <p class="text-secondary" style="font-size: 0.9rem; line-height: 1.75;">
                    Menggunakan Laravel 11 dengan pola Repository-Service, MySQL untuk persistensi data, dan Docker untuk portabilitas deployment.
                </p>
            </div>
            <div class="col-lg-7">
                <div class="d-flex flex-wrap gap-2">
                    <span class="stack-badge"><i class="bi bi-lightning-fill text-danger"></i> Laravel 11</span>
                    <span class="stack-badge"><i class="bi bi-database-fill text-warning"></i> MySQL 8</span>
                    <span class="stack-badge"><i class="bi bi-box2-fill text-info"></i> Docker</span>
                    <span class="stack-badge"><i class="bi bi-shield-lock-fill text-success"></i> JWT Auth</span>
                    <span class="stack-badge"><i class="bi bi-diagram-3-fill text-primary"></i> Repository Pattern</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-4" style="background: var(--brand-primary);">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6">
                <p class="mb-0 text-white fw-semibold">
                    <i class="bi bi-rocket-takeoff-fill me-2"></i>
                    Mulai eksplorasi fitur sistem sekarang
                </p>
            </div>
            <div class="col-md-6 d-flex gap-2 justify-content-md-end flex-wrap">
                <a href="{{ url('/tracking') }}" class="btn btn-light btn-sm fw-bold px-3"><i class="bi bi-search me-1"></i> Lacak Paket</a>
                <a href="{{ url('/module-1-monitor') }}" class="btn btn-outline-light btn-sm fw-bold px-3"><i class="bi bi-building me-1"></i> Monitor Gudang</a>
                <a href="{{ url('/auth/login') }}" class="btn btn-outline-light btn-sm fw-bold px-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- SECTION: MONITORING DASHBOARD & ANALYTICS -->
<!-- ════════════════════════════════════════════════════════════ -->
<section class="py-5">
    <div class="container">
        <div class="mb-5">
            <div class="section-label">📊 Real-Time Analytics</div>
            <h2 class="section-title">System Monitoring Dashboard</h2>
            <p class="text-secondary mt-2" style="max-width: 620px; font-size: 0.925rem;">
                Pantau performa sistem pengiriman paket secara real-time dengan data terintegrasi dari semua modul.
            </p>
        </div>

        <!-- METRICS CARDS -->
        <div class="row g-3 mb-5">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9;">Total Gudang</div>
                                <div style="font-size: 1.8rem; font-weight: 800;">{{ $metrics['total_warehouses'] }}</div>
                            </div>
                            <i class="bi bi-building" style="font-size: 2rem; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9;">Total Paket</div>
                                <div style="font-size: 1.8rem; font-weight: 800;">{{ number_format($metrics['total_packages']) }}</div>
                            </div>
                            <i class="bi bi-box-seam" style="font-size: 2rem; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9;">Total Hub</div>
                                <div style="font-size: 1.8rem; font-weight: 800;">{{ $metrics['total_hubs'] }}</div>
                            </div>
                            <i class="bi bi-geo-alt" style="font-size: 2rem; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-size: 0.75rem; opacity: 0.9;">Total Armada</div>
                                <div style="font-size: 1.8rem; font-weight: 800;">{{ $metrics['total_fleets'] }}</div>
                            </div>
                            <i class="bi bi-truck" style="font-size: 2rem; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 1 -->
        <div class="row g-4 mb-4">
            <!-- WAREHOUSE CAPACITY USAGE -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-warning"></i>Warehouse Capacity Usage</h6>
                    </div>
                    <div class="card-body p-4">
                        <canvas id="warehouseChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- PACKAGE CATEGORY DISTRIBUTION -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-2 text-info"></i>Package Category Distribution</h6>
                    </div>
                    <div class="card-body p-4">
                        <canvas id="categoryChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 2 -->
        <div class="row g-4 mb-4">
            <!-- PACKAGE STATUS -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-success"></i>Package Status</h6>
                    </div>
                    <div class="card-body p-4">
                        <canvas id="statusChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- HUB PERFORMANCE -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Hub Performance</h6>
                    </div>
                    <div class="card-body p-4">
                        <canvas id="hubChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- FLEET UTILIZATION -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2 text-danger"></i>Fleet Utilization</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="fleetList" style="font-size: 0.875rem;">
                            @forelse($fleetData as $fleet)
                                <div class="mb-3 pb-3" style="border-bottom: 1px solid #eee;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">{{ $fleet['name'] }}</span>
                                        <span class="badge bg-primary">{{ $fleet['utilization'] }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $fleet['utilization'] }}%;"></div>
                                    </div>
                                    <small class="text-muted">{{ $fleet['packages_count'] }}/{{ $fleet['max_capacity'] }} units</small>
                                </div>
                            @empty
                                <div class="text-muted text-center py-4">Tidak ada data armada</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW 3 -->
        <div class="row g-4">
            <!-- PACKAGE TREND (7 DAYS) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Package Activity Trend (7 Days)</h6>
                    </div>
                    <div class="card-body p-4">
                        <canvas id="trendChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- TOP WAREHOUSES -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom p-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-star me-2 text-warning"></i>Top Warehouses by Usage</h6>
                    </div>
                    <div class="card-body p-4">
                        <div style="font-size: 0.85rem;">
                            @forelse($topWarehouses as $warehouse)
                                <div class="mb-3 pb-3" style="border-bottom: 1px solid #eee;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">{{ $warehouse['warehouse_name'] }}</span>
                                        <span class="badge" style="background: {{ $warehouse['usage_percentage'] > 90 ? '#dc3545' : ($warehouse['usage_percentage'] > 70 ? '#ffc107' : '#28a745') }};">
                                            {{ $warehouse['usage_percentage'] }}%
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $warehouse['usage_percentage'] }}%; background: {{ $warehouse['usage_percentage'] > 90 ? '#dc3545' : ($warehouse['usage_percentage'] > 70 ? '#ffc107' : '#28a745') }};"></div>
                                    </div>
                                    <small class="text-muted">{{ $warehouse['current_load'] }}/{{ $warehouse['capacity'] }} units</small>
                                </div>
                            @empty
                                <div class="text-muted text-center py-4">Tidak ada data gudang</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // DATA FROM CONTROLLER
    const warehouseData = @json($warehouseData);
    const packageStats = @json($packageStats);
    const hubData = @json($hubData);
    const packageTrend = @json($packageTrend);

    // CHART 1: WAREHOUSE CAPACITY
    const warehouseCtx = document.getElementById('warehouseChart').getContext('2d');
    new Chart(warehouseCtx, {
        type: 'doughnut',
        data: {
            labels: warehouseData.map(w => w.warehouse_name),
            datasets: [{
                data: warehouseData.map(w => w.usage_percentage),
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF9F40'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11 }, padding: 10 }
                }
            }
        }
    });

    // CHART 2: PACKAGE CATEGORY
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: ['Small (≤1000 cm³)', 'Medium (1-5k cm³)', 'Large (>5k cm³)'],
            datasets: [{
                data: [packageStats.by_category.small, packageStats.by_category.medium, packageStats.by_category.large],
                backgroundColor: ['#3498db', '#2ecc71', '#e74c3c'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11 }, padding: 10 }
                }
            }
        }
    });

    // CHART 3: PACKAGE STATUS
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(packageStats.by_status),
            datasets: [{
                label: 'Count',
                data: Object.values(packageStats.by_status),
                backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe'],
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    // CHART 4: HUB PERFORMANCE
    const hubCtx = document.getElementById('hubChart').getContext('2d');
    new Chart(hubCtx, {
        type: 'bar',
        data: {
            labels: hubData.map(h => h.name),
            datasets: [{
                label: 'Packages',
                data: hubData.map(h => h.packages_count),
                backgroundColor: '#4facfe',
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    // CHART 5: PACKAGE TREND
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: Object.keys(packageTrend),
            datasets: [{
                label: 'Package Activity',
                data: Object.values(packageTrend),
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2ecc71',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: true, labels: { font: { size: 12 } } }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endpush
@endsection
