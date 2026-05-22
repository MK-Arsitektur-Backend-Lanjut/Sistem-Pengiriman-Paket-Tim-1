<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - SiPaket Tim 1</title>
    <meta name="description" content="Daftar akun pelanggan SiPaket untuk mulai melacak dan mengelola pengiriman paket Anda.">
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
            background: linear-gradient(135deg, #064e3b 0%, #059669 50%, #10b981 100%);
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
        }

        .brand-text {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
        }

        .brand-text span { color: #a7f3d0; }

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

        .auth-steps {
            list-style: none;
            z-index: 1;
        }

        .auth-steps li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: rgba(255,255,255,0.85);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            flex-shrink: 0;
            color: #fff;
        }

        .step-info strong {
            display: block;
            font-weight: 600;
            color: #fff;
        }

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

        /* ─── Right Panel ─── */
        .auth-right {
            width: 520px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 3rem;
            overflow-y: auto;
        }

        .auth-form-header {
            margin-bottom: 1.8rem;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.83rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.35rem;
        }

        .form-group label span {
            color: #ef4444;
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 2.6rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.88rem;
            font-family: inherit;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
            background: #fff;
        }

        .form-control.no-icon {
            padding-left: 0.9rem;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .input-icon-right {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
        }

        .input-icon-right:hover { color: #059669; }

        .btn-success {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #059669, #047857);
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
            margin-top: 0.8rem;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #047857, #065f46);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
        }

        .btn-success:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

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

        .auth-footer-text {
            text-align: center;
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 1.2rem;
        }

        .auth-footer-text a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer-text a:hover { text-decoration: underline; }

        .password-strength {
            height: 4px;
            border-radius: 4px;
            background: #e2e8f0;
            margin-top: 6px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }

        .strength-text {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 3px;
        }

        @media (max-width: 900px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; padding: 2rem; }
        }

        @media (max-width: 560px) {
            .form-row { grid-template-columns: 1fr; }
            .auth-right { padding: 1.5rem; }
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

        <h1 class="auth-headline">Mulai Perjalanan<br>Pengiriman Anda 🚀</h1>
        <p class="auth-subline">Buat akun dalam hitungan detik dan nikmati kemudahan mengelola pengiriman paket dengan teknologi JWT yang aman.</p>

        <ol class="auth-steps">
            <li>
                <div class="step-num">1</div>
                <div class="step-info">
                    <strong>Isi Formulir Pendaftaran</strong>
                    Masukkan nama, email, dan password Anda
                </div>
            </li>
            <li>
                <div class="step-num">2</div>
                <div class="step-info">
                    <strong>Dapatkan JWT Token</strong>
                    Token aman untuk mengakses semua endpoint API
                </div>
            </li>
            <li>
                <div class="step-num">3</div>
                <div class="step-info">
                    <strong>Mulai Kirim Paket</strong>
                    Buat shipment dari gudang dan lacak real-time
                </div>
            </li>
        </ol>

        <a href="/" class="back-home"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
    </div>

    <!-- Right Form Panel -->
    <div class="auth-right">
        <div class="auth-form-header">
            <h1>Buat Akun Baru</h1>
            <p>Semua field bertanda <span style="color:#ef4444">*</span> wajib diisi.</p>
        </div>

        <div id="alertBox"></div>

        <form id="registerForm" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama Lengkap <span>*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Budi Santoso" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Nomor HP</label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Alamat Email <span>*</span></label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Alamat Pengiriman</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-geo-alt input-icon"></i>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Jl. Contoh No.1, Kota, Provinsi">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span>*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 8 karakter" required autocomplete="new-password" style="padding-right:2.5rem">
                        <button type="button" class="input-icon-right" id="togglePwd"><i class="bi bi-eye" id="eyeIcon1"></i></button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password <span>*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password" required autocomplete="new-password" style="padding-right:2.5rem">
                        <button type="button" class="input-icon-right" id="togglePwdConf"><i class="bi bi-eye" id="eyeIcon2"></i></button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-success" id="registerBtn">
                <span class="spinner" id="registerSpinner"></span>
                <i class="bi bi-person-plus-fill" id="registerBtnIcon"></i>
                Buat Akun
            </button>
        </form>

        <div class="auth-footer-text">
            Sudah punya akun?
            <a href="/auth/login">Masuk sekarang</a>
        </div>

        <div class="auth-footer-text" style="margin-top: 0.6rem;">
            <a href="/" style="color: #64748b; font-weight: 500;">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <script>
        const API_BASE = '/api/v1';
        const TOKEN_KEY = 'module3_jwt_token';
        const alertBox = document.getElementById('alertBox');

        // Tidak perlu redirect otomatis jika sudah login

        function showAlert(message, type = 'danger') {
            const icon = type === 'danger' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill';
            alertBox.innerHTML = `
                <div class="alert alert-${type}">
                    <i class="bi ${icon}"></i>
                    <span>${message}</span>
                </div>
            `;
            alertBox.scrollIntoView({ behavior: 'smooth' });
        }

        function clearAlert() { alertBox.innerHTML = ''; }

        // Toggle password visibility
        document.getElementById('togglePwd').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eyeIcon1');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
            eye.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        document.getElementById('togglePwdConf').addEventListener('click', function() {
            const pwd = document.getElementById('password_confirmation');
            const eye = document.getElementById('eyeIcon2');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
            eye.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const bar = document.getElementById('strengthBar');
            const txt = document.getElementById('strengthText');
            let strength = 0;

            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const levels = [
                { width: '0%', color: '#e2e8f0', text: '' },
                { width: '25%', color: '#ef4444', text: 'Lemah' },
                { width: '50%', color: '#f59e0b', text: 'Sedang' },
                { width: '75%', color: '#3b82f6', text: 'Kuat' },
                { width: '100%', color: '#10b981', text: 'Sangat Kuat' },
            ];

            bar.style.width = levels[strength].width;
            bar.style.background = levels[strength].color;
            txt.textContent = val.length > 0 ? `Kekuatan: ${levels[strength].text}` : '';
            txt.style.color = levels[strength].color;
        });

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearAlert();

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                showAlert('Password dan konfirmasi password tidak cocok.');
                return;
            }

            if (password.length < 8) {
                showAlert('Password minimal 8 karakter.');
                return;
            }

            const btn = document.getElementById('registerBtn');
            const spinner = document.getElementById('registerSpinner');
            const icon = document.getElementById('registerBtnIcon');

            btn.disabled = true;
            spinner.style.display = 'block';
            icon.style.display = 'none';

            const payload = {
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
            };
            if (phone) payload.phone = phone;
            if (address) payload.address = address;

            try {
                const response = await fetch(`${API_BASE}/auth/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (!response.ok) {
                    const errMsg = result.message
                        || (result.errors ? Object.values(result.errors).flat().join(' | ') : null)
                        || 'Registrasi gagal. Periksa kembali data Anda.';
                    throw new Error(errMsg);
                }

                // Simpan JWT token
                const token = result.data?.token;
                if (token) {
                    localStorage.setItem(TOKEN_KEY, token);
                    if (result.data?.user) {
                        localStorage.setItem('module3_user', JSON.stringify(result.data.user));
                    }
                }

                showAlert('Akun berhasil dibuat! Mengalihkan ke dashboard...', 'success');

                setTimeout(() => {
                    window.location.href = '/';
                }, 1000);

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
