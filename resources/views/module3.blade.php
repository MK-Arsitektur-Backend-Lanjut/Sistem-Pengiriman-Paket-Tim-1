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
                    <h3 class="h4 fw-bold mb-1">Dynamic Shipping Calculator</h3>
                    <p class="text-secondary mb-0">Hitung biaya pengiriman secara dinamis berdasarkan berat, jarak, dan tipe layanan.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span id="navUserName" class="text-secondary fw-semibold" style="font-size:0.9rem;"></span>
                    <a href="{{ url('/auth/profile') }}" class="btn btn-outline-primary btn-sm rounded-pill">
                        <i class="bi bi-person-gear"></i> Manage Profile
                    </a>
                </div>
            </div>

            <div id="globalAlert"></div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-lg-4">
                            <form id="calculatorForm" class="row g-3 align-items-end">
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Weight (kg)</label>
                                    <div class="input-group input-group-sm">
                                        <input name="weight_kg" type="number" step="0.1" min="0.1" class="form-control" required>
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Distance (km)</label>
                                    <div class="input-group input-group-sm">
                                        <input name="distance_km" type="number" step="0.1" min="1" class="form-control" required>
                                        <span class="input-group-text">km</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Service Type</label>
                                    <select name="service_type" class="form-select form-select-sm" required>
                                        <option value="regular">Regular</option>
                                        <option value="express">Express</option>
                                        <option value="same_day">Same Day</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Declared Value</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input name="declared_value" type="number" min="0" step="1000" class="form-control" value="0">
                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isFragile" name="is_fragile">
                                        <label class="form-check-label small" for="isFragile">Fragile Package</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="useInsurance" name="use_insurance">
                                        <label class="form-check-label small" for="useInsurance">Use Insurance</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-8 d-grid">
                                    <button type="submit" class="btn btn-primary fw-bold">
                                        <i class="bi bi-calculator me-2"></i>Calculate Shipping Cost
                                    </button>
                                </div>
                            </form>

                            <div class="table-responsive mt-4">
                                <table class="table table-hover align-middle shadow-sm rounded-3 overflow-hidden">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Component</th>
                                            <th class="text-end">Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Base Cost</td>
                                            <td id="calcBase" class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Distance Cost</td>
                                            <td id="calcDistance" class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Weight Cost</td>
                                            <td id="calcWeight" class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Fuel Surcharge</td>
                                            <td id="calcFuel" class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Fragile Surcharge</td>
                                            <td id="calcFragile" class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Insurance Cost</td>
                                            <td id="calcInsurance" class="text-end">-</td>
                                        </tr>
                                        <tr class="table-primary fw-bold fs-5">
                                            <td>Total Cost</td>
                                            <td id="calcTotal" class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small italic">Estimated Delivery (SLA)</td>
                                            <td id="calcSla" class="text-end text-muted small">-</td>
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
    const navUserName  = document.getElementById('navUserName');

    // Tampilkan info user dari localStorage
    (function showUserInfo() {
        const user = JSON.parse(localStorage.getItem('module3_user') || '{}');
        if (user.name && navUserName) {
            navUserName.textContent = '👤 ' + user.name;
        }
    })();

    function showAlert(message, type = 'info') {
        globalAlert.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    }

    function toCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 2,
        }).format(Number(value || 0));
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY) || '';
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
        const headers = { Accept: 'application/json' };
        if (body !== null) headers['Content-Type'] = 'application/json';
        if (withAuth) {
            const token = getToken();
            if (!token) throw new Error('Silakan login terlebih dahulu.');
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`${API_BASE}${path}`, {
            method, headers, body: body !== null ? JSON.stringify(body) : null,
        });

        let payload = {};
        try { payload = await response.json(); } catch (_) {}

        if (!response.ok) {
            if (response.status === 401) {
                localStorage.removeItem(TOKEN_KEY);
                localStorage.removeItem('module3_user');
                window.location.href = '/auth/login';
                throw new Error('Sesi berakhir. Silakan login kembali.');
            }
            const errorMessage = payload.message || Object.values(payload.errors || {}).flat().join(' | ') || 'Request gagal.';
            throw new Error(errorMessage);
        }
        return payload;
    }

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
</script>
@endpush
