@php
    $active = $activePage ?? '';
@endphp

<nav class="navbar-custom">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ url('/home') }}" class="navbar-brand-text text-decoration-none">
            <i class="bi bi-box-seam-fill me-1" style="color: var(--brand-primary);"></i>
            Si<span>Paket</span> <span class="fw-normal text-secondary fs-6">Tim 1</span>
        </a>

        <div class="d-flex align-items-center gap-1 flex-wrap">
            <a href="{{ url('/home') }}" class="nav-pill-link {{ $active === 'home' ? 'is-active' : '' }}">
                <i class="bi bi-house me-1"></i>Home
            </a>
            <a href="{{ url('/module-1-monitor') }}" class="nav-pill-link {{ $active === 'module1' ? 'is-active' : '' }}">
                <i class="bi bi-building me-1"></i>Warehouse
            </a>
            <a href="{{ url('/tracking') }}" class="nav-pill-link {{ $active === 'tracking' ? 'is-active' : '' }}">
                <i class="bi bi-search me-1"></i>Tracking
            </a>
            <a href="{{ url('/') }}" class="nav-pill-link {{ $active === 'module4' ? 'is-active' : '' }}">
                <i class="bi bi-truck me-1"></i>Fleet & Hub
            </a>

            <span class="ms-2 d-flex align-items-center gap-2 text-success" style="font-size: 0.78rem; font-weight: 600;">
                <span class="pulse-dot"></span> Online
            </span>
        </div>
    </div>
</nav>
