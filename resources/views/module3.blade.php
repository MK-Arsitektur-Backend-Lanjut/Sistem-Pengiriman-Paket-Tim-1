@extends('layouts.app')

@section('title', 'Modul 3 - Customer Auth & Shipping Profile')
@section('meta_description', 'Playground API Modul 3 untuk autentikasi customer, profile pengiriman, dan kalkulator ongkir.')
@section('active_nav', 'module3')

@push('styles')
<style>
    .page-module3 {
        padding: 1.5rem 0 2.5rem;
    }

    .card-soft {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .btn-brand {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border: none;
    }

    .btn-brand:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
    }
</style>
@endpush

@section('content')
<section class="page-module3">
    <div class="container">
        <section class="card-soft p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <h3 class="h4 fw-bold mb-1">Module 3 — Customer Auth & Shipping (JWT)</h3>
                    <p class="text-secondary mb-0">Autentikasi pelanggan dengan <strong>JSON Web Token (JWT)</strong>. Token tersimpan otomatis saat login/register.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span id="tokenBadge" class="badge text-bg-secondary">Memuat...</span>
                    <span id="navUserName" class="text-secondary" style="font-size:0.85rem;"></span>
                    <button id="btnLogout" type="button" class="btn btn-outline-danger btn-sm">Logout</button>
                </div>
            </div>

            <div id="globalAlert"></div>

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h4 class="h5 fw-bold">Customer Authentication</h4>
                            <p class="text-secondary small">Gunakan register/login untuk mendapatkan bearer token.</p>

                            <ul class="nav nav-pills mb-3" id="auth-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="register-tab" data-bs-toggle="pill" data-bs-target="#register-pane" type="button" role="tab">Register</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-pane" type="button" role="tab">Login</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="auth-tab-content">
                                <div class="tab-pane fade show active" id="register-pane" role="tabpanel">
                                    <form id="registerForm" class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Name</label>
                                            <input name="name" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input name="email" type="email" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input name="phone" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Device Name</label>
                                            <input name="device_name" class="form-control form-control-sm" value="web-client">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <input name="address" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password</label>
                                            <input name="password" type="password" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm Password</label>
                                            <input name="password_confirmation" type="password" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-12 d-grid">
                                            <button type="submit" class="btn btn-brand text-white">Register & Get Token</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="login-pane" role="tabpanel">
                                    <form id="loginForm" class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input name="email" type="email" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password</label>
                                            <input name="password" type="password" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Device Name</label>
                                            <input name="device_name" class="form-control form-control-sm" value="web-client">
                                        </div>
                                        <div class="col-12 d-grid">
                                            <button type="submit" class="btn btn-brand text-white">Login</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small text-secondary">Current Token</label>
                                <textarea id="tokenPreview" class="form-control form-control-sm" rows="3" readonly></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h4 class="h5 fw-bold mb-0">Customer Shipping Profile</h4>
                                <button id="btnLoadProfile" type="button" class="btn btn-outline-primary btn-sm">Load Profile</button>
                            </div>
                            <p class="text-secondary small">Endpoint: GET/PUT customer shipping profile (per user login).</p>

                            <form id="profileForm" class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Sender Name</label>
                                    <input name="sender_name" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sender Phone</label>
                                    <input name="sender_phone" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Default Pickup Address</label>
                                    <input name="default_pickup_address" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Origin City</label>
                                    <input name="default_origin_city" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Postal Code</label>
                                    <input name="default_origin_postal_code" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Service</label>
                                    <select name="preferred_service_type" class="form-select form-select-sm" required>
                                        <option value="regular">Regular</option>
                                        <option value="express">Express</option>
                                        <option value="same_day">Same Day</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Package Type</label>
                                    <input name="preferred_package_type" class="form-control form-control-sm" placeholder="box / envelope / pallet">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-success">Save Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="h5 fw-bold">Dynamic Shipping Calculator</h4>
                            <p class="text-secondary small">Endpoint: POST shipping-cost/calculate dengan perhitungan dinamis berdasarkan rules database.</p>

                            <form id="calculatorForm" class="row g-2 align-items-end">
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input name="weight_kg" type="number" step="0.1" min="0.1" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Distance (km)</label>
                                    <input name="distance_km" type="number" step="0.1" min="1" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Service Type</label>
                                    <select name="service_type" class="form-select form-select-sm" required>
                                        <option value="regular">Regular</option>
                                        <option value="express">Express</option>
                                        <option value="same_day">Same Day</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Declared Value (IDR)</label>
                                    <input name="declared_value" type="number" min="0" step="1000" class="form-control form-control-sm" value="0">
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="isFragile" name="is_fragile">
                                        <label class="form-check-label" for="isFragile">Fragile Package</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="useInsurance" name="use_insurance">
                                        <label class="form-check-label" for="useInsurance">Use Insurance</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 d-grid">
                                    <button type="submit" class="btn btn-primary">Calculate Shipping Cost</button>
                                </div>
                            </form>

                            <div class="table-responsive mt-3">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <th style="width: 240px;">Base Cost</th>
                                            <td id="calcBase">-</td>
                                        </tr>
                                        <tr>
                                            <th>Distance Cost</th>
                                            <td id="calcDistance">-</td>
                                        </tr>
                                        <tr>
                                            <th>Weight Cost</th>
                                            <td id="calcWeight">-</td>
                                        </tr>
                                        <tr>
                                            <th>Fuel Surcharge</th>
                                            <td id="calcFuel">-</td>
                                        </tr>
                                        <tr>
                                            <th>Fragile Surcharge</th>
                                            <td id="calcFragile">-</td>
                                        </tr>
                                        <tr>
                                            <th>Insurance Cost</th>
                                            <td id="calcInsurance">-</td>
                                        </tr>
                                        <tr class="table-primary">
                                            <th>Total Cost</th>
                                            <td id="calcTotal" class="fw-bold">-</td>
                                        </tr>
                                        <tr>
                                            <th>Estimated SLA (days)</th>
                                            <td id="calcSla">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const API_BASE  = '/api/v1';
    const TOKEN_KEY = 'module3_jwt_token';

    // ── Guard: redirect ke login jika belum terautentikasi ──
    (function guardAuth() {
        if (!localStorage.getItem(TOKEN_KEY)) {
            window.location.replace('/auth/login');
        }
    })();

    const globalAlert  = document.getElementById('globalAlert');
    const tokenBadge   = document.getElementById('tokenBadge');
    const tokenPreview = document.getElementById('tokenPreview');
    const navUserName  = document.getElementById('navUserName');

    // Tampilkan info user dari localStorage
    (function showUserInfo() {
        const user = JSON.parse(localStorage.getItem('module3_user') || '{}');
        if (user.name && navUserName) {
            navUserName.textContent = '👤 ' + user.name;
        }
    })();

    function showAlert(message, type = 'info') {
        globalAlert.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    }

    function toCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 2,
        }).format(Number(value || 0));
    }

    function saveToken(token) {
        localStorage.setItem(TOKEN_KEY, token);
        refreshTokenUI();
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY) || '';
    }

    function clearToken() {
        localStorage.removeItem(TOKEN_KEY);
        refreshTokenUI();
    }

    function refreshTokenUI() {
        const token = getToken();
        if (token) {
            tokenBadge.className = 'badge text-bg-success';
            tokenBadge.textContent = 'Token: Aktif';
            tokenPreview.value = token;
        } else {
            tokenBadge.className = 'badge text-bg-secondary';
            tokenBadge.textContent = 'Token: Belum login';
            tokenPreview.value = '';
        }
    }

    function formToObject(form) {
        const data = Object.fromEntries(new FormData(form).entries());
        Object.keys(data).forEach((key) => {
            if (data[key] === '') {
                data[key] = null;
            }
        });
        return data;
    }

    async function callApi(path, method = 'GET', body = null, withAuth = false) {
        const headers = {
            Accept: 'application/json',
        };

        if (body !== null) {
            headers['Content-Type'] = 'application/json';
        }

        if (withAuth) {
            const token = getToken();
            if (!token) {
                throw new Error('Silakan login terlebih dahulu untuk endpoint ini.');
            }
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`${API_BASE}${path}`, {
            method,
            headers,
            body: body !== null ? JSON.stringify(body) : null,
        });

        let payload = {};
        try {
            payload = await response.json();
        } catch (_) {
            payload = {};
        }

        if (!response.ok) {
            const errorMessage = payload.message || Object.values(payload.errors || {}).flat().join(' | ') || 'Request gagal diproses.';
            throw new Error(errorMessage);
        }

        return payload;
    }

    document.getElementById('registerForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const payload = formToObject(event.target);
            const result = await callApi('/auth/register', 'POST', payload, false);
            if (result?.data?.token) {
                saveToken(result.data.token);
            }
            showAlert(result.message || 'Register berhasil.', 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    document.getElementById('loginForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const payload = formToObject(event.target);
            const result = await callApi('/auth/login', 'POST', payload, false);
            if (result?.data?.token) {
                saveToken(result.data.token);
            }
            showAlert(result.message || 'Login berhasil.', 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    document.getElementById('btnLogout').addEventListener('click', async () => {
        try {
            await callApi('/auth/logout', 'POST', {}, true);
        } catch (_) {}
        finally {
            clearToken();
            localStorage.removeItem('module3_user');
            window.location.replace('/home');
        }
    });

    document.getElementById('btnLoadProfile').addEventListener('click', async () => {
        try {
            const result = await callApi('/customer/shipping-profile', 'GET', null, true);
            const data = result.data || {};
            const form = document.getElementById('profileForm');
            Object.keys(data).forEach((key) => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = data[key] ?? '';
                }
            });
            showAlert(result.message || 'Profil berhasil diambil.', 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    document.getElementById('profileForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const payload = formToObject(event.target);
            const result = await callApi('/customer/shipping-profile', 'PUT', payload, true);
            showAlert(result.message || 'Profil berhasil disimpan.', 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    document.getElementById('calculatorForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const payload = formToObject(event.target);
            payload.is_fragile = document.getElementById('isFragile').checked;
            payload.use_insurance = document.getElementById('useInsurance').checked;
            payload.weight_kg = Number(payload.weight_kg);
            payload.distance_km = Number(payload.distance_km);
            payload.declared_value = Number(payload.declared_value || 0);

            const result = await callApi('/customer/shipping-cost/calculate', 'POST', payload, true);
            const breakdown = result.data?.cost_breakdown || {};

            document.getElementById('calcBase').textContent = toCurrency(breakdown.base_cost);
            document.getElementById('calcDistance').textContent = toCurrency(breakdown.distance_cost);
            document.getElementById('calcWeight').textContent = toCurrency(breakdown.weight_cost);
            document.getElementById('calcFuel').textContent = toCurrency(breakdown.fuel_surcharge);
            document.getElementById('calcFragile').textContent = toCurrency(breakdown.fragile_surcharge);
            document.getElementById('calcInsurance').textContent = toCurrency(breakdown.insurance_cost);
            document.getElementById('calcTotal').textContent = toCurrency(result.data?.total_cost || 0);
            document.getElementById('calcSla').textContent = `${result.data?.estimated_sla_days ?? '-'} hari`;

            showAlert(result.message || 'Perhitungan ongkir berhasil.', 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });

    refreshTokenUI();
</script>
@endpush
