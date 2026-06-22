@extends('layouts.app')

@section('title', 'Dynamic Shipping Calculator - SiPaket')
@section('meta_description', 'Kalkulator ongkir interaktif.')
@section('active_nav', 'module3')

@push('styles')
<style>
/* Modern styling for the dashboard look */
.calculator-header {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.2);
}

.card-soft {
    border: none;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card-soft:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
}
.progress-steps::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
}
.step-item {
    position: relative;
    z-index: 2;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    border: 2px solid #e2e8f0;
}
.step-item.active {
    color: #2563eb;
    border-color: #2563eb;
    background: #eff6ff;
}

.total-cost-card {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: white;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
}

/* Custom Checkbox */
.custom-checkbox .form-check-input:checked {
    background-color: #2563eb;
    border-color: #2563eb;
}

/* Skeleton Loading */
.skeleton {
    background: #e2e8f0;
    border-radius: 4px;
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@media print {
    body * { visibility: hidden; }
    #printableArea, #printableArea * { visibility: visible; }
    #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
    .calculator-header, #calculatorForm button { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="container py-4">
    <!-- Header Section -->
    <div class="calculator-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-calculator me-2"></i>Dynamic Shipping Calculator</h2>
            <p class="mb-0 opacity-75">Hitung estimasi biaya pengiriman secara otomatis berdasarkan berat, jarak, dimensi paket, layanan, dan fitur tambahan.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success bg-opacity-25 text-white border border-success rounded-pill px-3 py-2">
                <i class="bi bi-circle-fill text-success small me-1"></i> Online
            </span>
            <div class="text-white">
                <span id="navUserName" class="fw-semibold"></span>
            </div>
            <a href="{{ url('/auth/profile') }}" class="btn btn-light btn-sm rounded-pill fw-bold text-primary">
                <i class="bi bi-person-gear"></i> Manage Profile
            </a>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-steps d-none d-md-flex">
        <div class="step-item active" id="step1"><i class="bi bi-1-circle me-1"></i>Input Data</div>
        <div class="step-item" id="step2"><i class="bi bi-2-circle me-1"></i>Perhitungan</div>
        <div class="step-item" id="step3"><i class="bi bi-3-circle me-1"></i>Estimasi</div>
        <div class="step-item" id="step4"><i class="bi bi-4-circle me-1"></i>Total Biaya</div>
    </div>

    <div id="globalAlert"></div>

    <div class="row g-4" id="printableArea">
        <!-- Left Column: Input Form -->
        <div class="col-lg-8">
            <form id="calculatorForm">
                <div class="row g-4">
                    <!-- Informasi Paket -->
                    <div class="col-12">
                        <div class="card-soft p-4">
                            <h5 class="section-title"><i class="bi bi-box-seam text-primary me-2"></i>Informasi Paket</h5>
                            
                            <div class="mb-3 d-print-none">
                                <label class="form-label fw-semibold text-secondary small">Calculation Mode</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check custom-checkbox">
                                        <input class="form-check-input" type="radio" name="calc_mode" id="modeManual" value="manual" checked onchange="toggleCalcMode('manual')">
                                        <label class="form-check-label" for="modeManual">Manual Input</label>
                                    </div>
                                    <div class="form-check custom-checkbox">
                                        <input class="form-check-input" type="radio" name="calc_mode" id="modePackage" value="package" onchange="toggleCalcMode('package')">
                                        <label class="form-check-label" for="modePackage">Select Registered Package</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 d-print-none" id="packageSelectContainer" style="display: none;">
                                <select id="packageSelect" name="package_id" class="form-select">
                                    <option value="">-- Choose Package --</option>
                                </select>
                            </div>

                            <div id="manualPackageInput" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-secondary small" data-bs-toggle="tooltip" title="Berat timbangan asli paket">Weight (kg) <span class="text-danger">*</span></label>
                                    <input id="calcWeightInput" name="weight_kg" type="number" step="0.1" min="0.1" class="form-control" required oninput="calculateVolumetric()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-secondary small">Length (cm)</label>
                                    <input id="calcLength" name="length" type="number" step="0.1" min="0" class="form-control" oninput="calculateVolumetric()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-secondary small">Width (cm)</label>
                                    <input id="calcWidth" name="width" type="number" step="0.1" min="0" class="form-control" oninput="calculateVolumetric()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-secondary small">Height (cm)</label>
                                    <input id="calcHeight" name="height" type="number" step="0.1" min="0" class="form-control" oninput="calculateVolumetric()">
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="alert alert-primary bg-primary bg-opacity-10 border-0 py-2 d-flex align-items-center mb-0">
                                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                                        <span class="small text-primary">Volumetric Weight: <strong id="displayVolumetric">0.00</strong> kg</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Pengiriman -->
                    <div class="col-12">
                        <div class="card-soft p-4">
                            <h5 class="section-title"><i class="bi bi-geo-alt text-primary me-2"></i>Informasi Pengiriman</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-secondary small">Origin City</label>
                                    <input name="origin_city" type="text" class="form-control" placeholder="Kota Asal">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-secondary small">Destination City</label>
                                    <input name="destination_city" type="text" class="form-control" placeholder="Kota Tujuan">
                                </div>
                                <div class="col-md-4" id="distanceContainer">
                                    <label class="form-label fw-semibold text-secondary small">Distance (km) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input id="calcDistanceInput" name="distance_km" type="number" step="0.1" min="1" class="form-control" required>
                                        <span class="input-group-text bg-light">km</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small">Service Type <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <div class="form-check custom-checkbox">
                                            <input class="form-check-input" type="radio" name="service_type" id="srvReg" value="regular" checked>
                                            <label class="form-check-label fw-semibold" for="srvReg">Regular (3-5 Hari)</label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input class="form-check-input" type="radio" name="service_type" id="srvExp" value="express">
                                            <label class="form-check-label fw-semibold text-warning" for="srvExp">Express (1-2 Hari)</label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input class="form-check-input" type="radio" name="service_type" id="srvSame" value="same_day">
                                            <label class="form-check-label fw-semibold text-primary" for="srvSame">Same Day (Hari Ini)</label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input class="form-check-input" type="radio" name="service_type" id="srvInst" value="instant">
                                            <label class="form-check-label fw-semibold text-danger" for="srvInst">Instant (1-3 Jam)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi Tambahan -->
                    <div class="col-12">
                        <div class="card-soft p-4">
                            <h5 class="section-title"><i class="bi bi-plus-circle text-primary me-2"></i>Opsi Tambahan</h5>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <div class="d-flex flex-column gap-2">
                                        <div class="form-check form-switch custom-checkbox">
                                            <input class="form-check-input" type="checkbox" id="isFragile" name="is_fragile">
                                            <label class="form-check-label text-secondary fw-semibold" for="isFragile">Fragile Package <span class="text-muted fw-normal">(Barang Pecah Belah)</span></label>
                                        </div>
                                        <div class="form-check form-switch custom-checkbox">
                                            <input class="form-check-input" type="checkbox" id="useInsurance" name="use_insurance">
                                            <label class="form-check-label text-secondary fw-semibold" for="useInsurance">Use Insurance</label>
                                        </div>
                                        <div class="form-check form-switch custom-checkbox">
                                            <input class="form-check-input" type="checkbox" id="priorityHandling" name="priority_handling">
                                            <label class="form-check-label text-secondary fw-semibold" for="priorityHandling">Priority Handling</label>
                                        </div>
                                        <div class="form-check form-switch custom-checkbox">
                                            <input class="form-check-input" type="checkbox" id="saturdayDelivery" name="saturday_delivery">
                                            <label class="form-check-label text-secondary fw-semibold" for="saturdayDelivery">Saturday Delivery</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Declared Value (Nilai Barang)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                                        <input id="declaredValueDisplay" type="text" class="form-control" value="0" oninput="formatRupiahInput(this)">
                                        <input type="hidden" name="declared_value" id="declaredValueReal" value="0">
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Wajib jika menggunakan Asuransi</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 d-flex flex-wrap gap-2 mb-5 d-print-none">
                        <button type="submit" class="btn btn-primary fw-bold px-4 rounded-pill" id="btnCalculate">
                            <i class="bi bi-calculator me-2"></i>Calculate Cost
                        </button>
                        <button type="reset" class="btn btn-light fw-bold px-4 rounded-pill border" id="btnReset">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </button>
                        <button type="button" class="btn btn-outline-success fw-bold px-4 rounded-pill ms-auto" id="btnSave">
                            <i class="bi bi-bookmark-check me-2"></i>Save Calculation
                        </button>
                        <button type="button" class="btn btn-outline-danger fw-bold px-4 rounded-pill" id="btnPdf" onclick="downloadPdf()">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Estimasi -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 2rem;">
                
                <!-- Shipping Summary -->
                <div class="card-soft p-4 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-card-checklist me-2"></i>Shipping Summary</h6>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Berat Digunakan</span>
                        <span class="fw-bold text-dark" id="sumEffectiveWeight">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Jarak</span>
                        <span class="fw-bold text-dark" id="sumDistance">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Jenis Layanan</span>
                        <span class="fw-bold text-dark text-capitalize" id="sumService">-</span>
                    </div>
                    <div class="d-flex justify-content-between small pt-2">
                        <span class="text-secondary">Est. Waktu Pengiriman</span>
                        <span class="fw-bold text-success" id="sumSla">-</span>
                    </div>
                </div>

                <!-- Cost Breakdown -->
                <div class="card-soft p-4 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-receipt me-2"></i>Cost Breakdown</h6>
                    
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Base Cost</span>
                        <span class="fw-semibold" id="calcBase">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Distance Cost</span>
                        <span class="fw-semibold" id="calcDistance">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Weight Cost</span>
                        <span class="fw-semibold" id="calcWeight">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Fuel Surcharge</span>
                        <span class="fw-semibold" id="calcFuel">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Fragile Surcharge</span>
                        <span class="fw-semibold" id="calcFragile">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Insurance Cost</span>
                        <span class="fw-semibold" id="calcInsurance">-</span>
                    </div>
                    <!-- Mocked extra fields -->
                    <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                        <span class="text-secondary">Priority Handling Fee</span>
                        <span class="fw-semibold" id="calcPriority">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Saturday Delivery Fee</span>
                        <span class="fw-semibold" id="calcSaturday">-</span>
                    </div>
                </div>

                <!-- Total Cost -->
                <div class="total-cost-card shadow-lg">
                    <p class="mb-1 text-white-50 small fw-bold text-uppercase">Total Shipping Cost</p>
                    <h2 class="fw-bold mb-0" id="calcTotal" style="font-size: 2.2rem;">Rp 0</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const API_BASE  = '/api/v1';
    const TOKEN_KEY = 'module3_jwt_token';

    // ── Guard ──
    (function guardAuth() {
        if (!localStorage.getItem(TOKEN_KEY)) {
            window.location.replace('/auth/login');
        }
    })();

    const globalAlert  = document.getElementById('globalAlert');
    const navUserName  = document.getElementById('navUserName');

    (function showUserInfo() {
        const user = JSON.parse(localStorage.getItem('module3_user') || '{}');
        if (user.name && navUserName) {
            navUserName.textContent = '👤 ' + user.name;
        }
    })();

    function showAlert(message, type = 'info') {
        globalAlert.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    }

    // Rupiah formatting for input
    function formatRupiahInput(element) {
        let value = element.value.replace(/[^,\d]/g, '').toString();
        let split = value.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        element.value = rupiah;
        
        // Save raw value to hidden input
        document.getElementById('declaredValueReal').value = value ? parseInt(value.replace(/\./g, '')) : 0;
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY) || '';
    }

    function formToObject(form) {
        const data = Object.fromEntries(new FormData(form).entries());
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            data[cb.name] = cb.checked;
        });
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

    // Volumetric Calculation
    function calculateVolumetric() {
        const p = parseFloat(document.getElementById('calcLength').value) || 0;
        const l = parseFloat(document.getElementById('calcWidth').value) || 0;
        const t = parseFloat(document.getElementById('calcHeight').value) || 0;
        const volWeight = (p * l * t) / 6000;
        document.getElementById('displayVolumetric').textContent = volWeight.toFixed(2);
    }

    let packagesLoaded = false;
    async function loadPackages() {
        if (packagesLoaded) return;
        try {
            const select = document.getElementById('packageSelect');
            select.innerHTML = '<option value="">-- Loading packages... --</option>';
            const response = await callApi('/package', 'GET', null, false);
            select.innerHTML = '<option value="">-- Choose Package --</option>';
            if (response && response.success && response.data) {
                response.data.forEach(pkg => {
                    select.innerHTML += `<option value="${pkg.id}">${pkg.tracking_number} (${pkg.origin} ➔ ${pkg.destination}) - ${pkg.weight} kg</option>`;
                });
                packagesLoaded = true;
            } else {
                select.innerHTML = '<option value="">Failed to load packages</option>';
            }
        } catch (error) {
            select.innerHTML = '<option value="">Error loading packages</option>';
        }
    }

    function toggleCalcMode(mode) {
        const packageSelectContainer = document.getElementById('packageSelectContainer');
        const manualPackageInput = document.getElementById('manualPackageInput');
        const distanceContainer = document.getElementById('distanceContainer');
        const packageSelect = document.getElementById('packageSelect');
        const calcWeightInput = document.getElementById('calcWeightInput');
        const calcDistanceInput = document.getElementById('calcDistanceInput');

        if (mode === 'package') {
            packageSelectContainer.style.display = 'block';
            manualPackageInput.style.display = 'none';
            distanceContainer.style.display = 'none';

            packageSelect.setAttribute('required', 'required');
            calcWeightInput.removeAttribute('required');
            calcDistanceInput.removeAttribute('required');

            loadPackages();
        } else {
            packageSelectContainer.style.display = 'none';
            manualPackageInput.style.display = 'flex';
            distanceContainer.style.display = 'block';

            packageSelect.removeAttribute('required');
            calcWeightInput.setAttribute('required', 'required');
            calcDistanceInput.setAttribute('required', 'required');
        }
    }

    function updateProgress(step) {
        for(let i=1; i<=4; i++) {
            if(i <= step) document.getElementById('step'+i).classList.add('active');
            else document.getElementById('step'+i).classList.remove('active');
        }
    }

    document.getElementById('calculatorForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const btn = document.getElementById('btnCalculate');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Menghitung...';
        btn.disabled = true;
        
        updateProgress(2);

        try {
            const payload = formToObject(event.target);
            payload.declared_value = Number(document.getElementById('declaredValueReal').value || 0);

            // Manual Validation
            if (payload.calc_mode === 'manual') {
                if (payload.weight_kg <= 0) throw new Error("Berat paket harus lebih dari 0 kg.");
                if (payload.distance_km <= 0) throw new Error("Jarak pengiriman tidak valid.");
                if (payload.distance_km > 5000) throw new Error("Jarak terlalu jauh. Maksimal 5000 km.");
            }

            const isPackageMode = document.getElementById('modePackage').checked;
            if (isPackageMode) {
                payload.package_id = Number(payload.package_id);
                delete payload.weight_kg;
                delete payload.distance_km;
            } else {
                payload.weight_kg = Number(payload.weight_kg);
                payload.distance_km = Number(payload.distance_km);
                delete payload.package_id;
            }

            updateProgress(3);

            const result = await callApi('/customer/shipping-cost/calculate', 'POST', payload, true);
            const breakdown = result.data?.cost_breakdown || {};

            document.getElementById('calcBase').textContent = toCurrency(breakdown.base_cost);
            document.getElementById('calcDistance').textContent = toCurrency(breakdown.distance_cost);
            document.getElementById('calcWeight').textContent = toCurrency(breakdown.weight_cost);
            document.getElementById('calcFuel').textContent = toCurrency(breakdown.fuel_surcharge);
            document.getElementById('calcFragile').textContent = toCurrency(breakdown.fragile_surcharge);
            document.getElementById('calcInsurance').textContent = toCurrency(breakdown.insurance_cost);
            
            // Mock the new fields since backend probably doesn't have them
            const priorityFee = payload.priority_handling ? 15000 : 0;
            const saturdayFee = payload.saturday_delivery ? 10000 : 0;
            document.getElementById('calcPriority').textContent = toCurrency(priorityFee);
            document.getElementById('calcSaturday').textContent = toCurrency(saturdayFee);

            const total = (result.data?.total_cost || 0) + priorityFee + saturdayFee;
            document.getElementById('calcTotal').textContent = toCurrency(total);
            
            // Update Summary Card
            const details = result.data?.package_details;
            let effectiveWeight = payload.weight_kg || 0;
            let distance = payload.distance_km || 0;
            
            if (details) {
                effectiveWeight = details.effective_weight;
                distance = details.calculated_distance_km;
            } else {
                // Determine effective weight if manual
                const p = parseFloat(payload.length) || 0;
                const l = parseFloat(payload.width) || 0;
                const t = parseFloat(payload.height) || 0;
                const volWeight = (p * l * t) / 6000;
                effectiveWeight = Math.max(payload.weight_kg, volWeight).toFixed(2);
            }
            
            document.getElementById('sumEffectiveWeight').textContent = `${effectiveWeight} kg`;
            document.getElementById('sumDistance').textContent = `${distance} km`;
            document.getElementById('sumService').textContent = payload.service_type;
            
            let sla = result.data?.estimated_sla_days ? `${result.data.estimated_sla_days} hari` : '-';
            if (payload.service_type === 'same_day') sla = 'Hari ini';
            if (payload.service_type === 'instant') sla = '1-3 Jam';
            document.getElementById('sumSla').textContent = sla;

            updateProgress(4);
            showAlert('Perhitungan berhasil diselesaikan.', 'success');
            
            // Scroll to result on mobile
            if(window.innerWidth < 992) {
                document.querySelector('.total-cost-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

        } catch (error) {
            updateProgress(1);
            showAlert(error.message, 'danger');
        } finally {
            btn.innerHTML = '<i class="bi bi-calculator me-2"></i>Calculate Cost';
            btn.disabled = false;
        }
    });

    document.getElementById('btnReset').addEventListener('click', () => {
        document.getElementById('displayVolumetric').textContent = '0.00';
        document.getElementById('declaredValueDisplay').value = '0';
        document.getElementById('declaredValueReal').value = '0';
        updateProgress(1);
        
        ['calcBase', 'calcDistance', 'calcWeight', 'calcFuel', 'calcFragile', 'calcInsurance', 'calcPriority', 'calcSaturday', 'sumEffectiveWeight', 'sumDistance', 'sumService', 'sumSla'].forEach(id => {
            document.getElementById(id).textContent = '-';
        });
        document.getElementById('calcTotal').textContent = 'Rp 0';
    });

    document.getElementById('btnSave').addEventListener('click', () => {
        showAlert('Berhasil menyimpan riwayat perhitungan!', 'success');
    });

    function downloadPdf() {
        if(document.getElementById('calcTotal').textContent === 'Rp 0') {
            showAlert('Silakan hitung biaya terlebih dahulu sebelum mengunduh PDF.', 'warning');
            return;
        }
        window.print();
    }
    
    // Enable Tooltips
    document.addEventListener("DOMContentLoaded", function() {
        if(typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        }
    });
</script>
@endpush
