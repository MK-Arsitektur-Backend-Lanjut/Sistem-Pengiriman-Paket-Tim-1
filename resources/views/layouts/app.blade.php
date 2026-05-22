<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SiPaket Tim 1')</title>
    <meta name="description" content="@yield('meta_description', 'Sistem Pengiriman Paket terintegrasi untuk manajemen gudang, tracking, auth, dan fleet.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-dark: #1e3a8a;
            --brand-accent: #3b82f6;
            --surface: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--surface);
            color: var(--text-main);
            min-height: 100vh;
        }

        main {
            min-height: calc(100vh - 180px);
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0.875rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .navbar-brand-text {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: var(--text-main);
        }

        .navbar-brand-text span {
            color: var(--brand-primary);
        }

        .nav-pill-link {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 0.4rem 0.85rem;
            border-radius: 50px;
            transition: all 0.2s;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .nav-pill-link:hover {
            background: #eff6ff;
            color: var(--brand-primary);
            border-color: #bfdbfe;
        }

        .nav-pill-link.is-active {
            background: #eff6ff;
            color: var(--brand-primary);
            border-color: #bfdbfe;
        }

        .pulse-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            position: relative;
        }

        .pulse-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.3);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.6;
            }
            50% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        .footer-custom {
            background: #0f172a;
            color: #64748b;
            padding: 2rem 0;
            font-size: 0.85rem;
        }

        .footer-custom a {
            color: #94a3b8;
            text-decoration: none;
        }

        .footer-custom a:hover {
            color: #ffffff;
        }
    </style>

    @stack('styles')
</head>
<body>
@include('layouts.partials.navbar', ['activePage' => trim($__env->yieldContent('active_nav'))])

<main>
    @yield('content')
</main>

@include('layouts.partials.footer')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /**
     * Global JWT Auth Guard — SiPaket Tim 1
     * Runs on every page. Pages that require auth set @section('requires_auth','1').
     * Also hides nav links with class="needs-auth" when not logged in.
     */
    (function globalAuthGuard() {
        const TOKEN_KEY   = 'module3_jwt_token';
        const token       = localStorage.getItem(TOKEN_KEY);
        const requireAuth = '@yield('requires_auth')' === '1';

        // Redirect ke home jika halaman butuh auth tapi tidak ada token
        if (requireAuth && !token) {
            window.location.replace('/home');
            return;
        }

        // Sembunyikan/tampilkan elemen navbar berdasarkan status login
        document.addEventListener('DOMContentLoaded', function () {
            // Elemen yang hanya tampil saat login
            document.querySelectorAll('.nav-needs-auth').forEach(function (el) {
                el.style.display = token ? '' : 'none';
            });
            // Elemen yang hanya tampil saat belum login
            document.querySelectorAll('.nav-no-auth').forEach(function (el) {
                el.style.display = token ? 'none' : '';
            });
        });
    })();
</script>

@stack('scripts')
</body>
</html>

