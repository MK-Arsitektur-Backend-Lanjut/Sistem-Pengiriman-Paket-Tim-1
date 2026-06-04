<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SiPaket Tim 1</title>
    <meta name="description" content="Login ke akun pelanggan SiPaket untuk melacak dan mengelola pengiriman paket Anda.">
    <style>
        @font-face {
            font-family: 'Inter';
            src: url('/vendor/fonts/inter.woff2') format('woff2');
            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }
    </style>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-dark: #1e3a8a;
            --brand-accent: #3b82f6;
            --brand-light: #eff6ff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #f1f5f9;
        }

        /* ─── Left Panel ─── */
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            top: -150px;
            right: -150px;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            bottom: -80px;
            left: 60px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 3rem;
            text-decoration: none;
            z-index: 1;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
            backdrop-filter: blur(8px);
        }

        .brand-text {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .brand-text span { color: #93c5fd; }

        .auth-headline {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 1.2rem;
            z-index: 1;
        }

        .auth-subline {
            color: rgba(255,255,255,0.75);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
            max-width: 360px;
            z-index: 1;
        }

        .auth-features {
            list-style: none;
            z-index: 1;
        }

        .auth-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.85);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .auth-features li .feat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        /* ─── Right Panel ─── */
        .auth-right {
            width: 480px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
            overflow-y: auto;
        }

        .auth-form-header {
            margin-bottom: 2rem;
        }

        .auth-form-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }

        .auth-form-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            background: #fff;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper .form-control {
            padding-left: 2.8rem;
        }

        .input-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }

        .input-icon-right {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
        }

        .input-icon-right:hover { color: #2563eb; }

        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 0.5rem;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,99,235,0.35);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .auth-footer-text {
            text-align: center;
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 1.5rem;
        }

        .auth-footer-text a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-text a:hover { text-decoration: underline; }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.87rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            text-decoration: none;
            margin-top: 3rem;
            z-index: 1;
            transition: color 0.2s;
        }

        .back-home:hover { color: #fff; }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; padding: 2rem; }
        }

        @media (max-width: 480px) {
            .auth-right { padding: 1.5rem; }
        }

        /* Animated float */
        .float-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,0.9);
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 2rem;
            z-index: 1;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            position: relative;
        }

        .pulse-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: rgba(74, 222, 128, 0.4);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.5); opacity: 0; }
        }
    </style>
</head>
<body>
    <!-- Left Branding Panel -->
    <div class="auth-left">
        <a href="/" class="brand-logo">
            <div class="brand-icon"><i class="bi bi-box-seam-fill"></i></div>
            <span class="brand-text">Si<span>Paket</span> Tim 1</span>
        </a>

        <div class="float-badge">
            <span class="pulse-dot"></span>
            Sistem Aktif & Aman
        </div>

        <h1 class="auth-headline">Selamat Datang<br>Kembali! 👋</h1>
        <p class="auth-subline">Login ke akun Anda untuk melacak paket, mengelola pengiriman, dan melihat riwayat transaksi secara real-time.</p>

        <ul class="auth-features">
            <li>
                <div class="feat-icon"><i class="bi bi-shield-lock-fill"></i></div>
                Autentikasi aman dengan JWT Token
            </li>
            <li>
                <div class="feat-icon"><i class="bi bi-geo-alt-fill"></i></div>
                Lacak paket real-time (Modul 2)
            </li>
            <li>
                <div class="feat-icon"><i class="bi bi-box-fill"></i></div>
                Kelola paket & gudang (Modul 1)
            </li>
            <li>
                <div class="feat-icon"><i class="bi bi-truck"></i></div>
                Pantau armada pengiriman (Modul 4)
            </li>
        </ul>

        <a href="/" class="back-home"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
    </div>

    <!-- Right Form Panel -->
    <div class="auth-right">
        <div class="auth-form-header">
            <h1>Masuk ke Akun</h1>
            <p>Masukkan email dan password Anda untuk melanjutkan.</p>
        </div>

        <div id="alertBox"></div>

        <form id="loginForm" novalidate>
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="nama@email.com"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Minimal 8 karakter"
                        required
                        autocomplete="current-password"
                        style="padding-right: 2.8rem;"
                    >
                    <button type="button" class="input-icon-right" id="togglePassword" title="Tampilkan/sembunyikan password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="loginBtn">
                <span class="spinner" id="loginSpinner"></span>
                <i class="bi bi-box-arrow-in-right" id="loginBtnIcon"></i>
                Masuk
            </button>
        </form>

        <div class="divider">atau</div>

        <div class="auth-footer-text">
            Belum punya akun?
            <a href="/auth/register">Daftar sekarang</a>
        </div>

        <div class="auth-footer-text" style="margin-top: 0.8rem;">
            <a href="/" style="color: #64748b; font-weight: 500;">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <script>
        const API_BASE = '/api/v1';
        const TOKEN_KEY = 'module3_jwt_token';
        const alertBox = document.getElementById('alertBox');

        // Token sudah ada? Biarkan saja di halaman ini (jangan redirect agar tidak loop)
        // User bisa login dengan akun lain atau kembali ke homepage manual

        function showAlert(message, type = 'danger') {
            const icon = type === 'danger' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill';
            alertBox.innerHTML = `
                <div class="alert alert-${type}">
                    <i class="bi ${icon}"></i>
                    <span>${message}</span>
                </div>
            `;
        }

        function clearAlert() {
            alertBox.innerHTML = '';
        }

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.className = 'bi bi-eye-slash';
            } else {
                pwd.type = 'password';
                eye.className = 'bi bi-eye';
            }
        });

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearAlert();

            const btn = document.getElementById('loginBtn');
            const spinner = document.getElementById('loginSpinner');
            const icon = document.getElementById('loginBtnIcon');

            btn.disabled = true;
            spinner.style.display = 'block';
            icon.style.display = 'none';

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            try {
                const response = await fetch(`${API_BASE}/auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email, password }),
                });

                const result = await response.json();

                if (!response.ok) {
                    const errMsg = result.message
                        || (result.errors ? Object.values(result.errors).flat().join(' ') : null)
                        || 'Login gagal. Periksa email dan password Anda.';
                    throw new Error(errMsg);
                }

                // Simpan JWT token
                const token = result.data?.token;
                if (token) {
                    localStorage.setItem(TOKEN_KEY, token);
                    // Simpan info user juga
                    if (result.data?.user) {
                        localStorage.setItem('module3_user', JSON.stringify(result.data.user));
                    }
                }

                showAlert('Login berhasil! Mengalihkan...', 'success');

                setTimeout(() => {
                    window.location.href = '/';
                }, 800);

            } catch (error) {
                showAlert(error.message);
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
                icon.style.display = 'inline-block';
            }
        });
    </script>
</body>
</html>
