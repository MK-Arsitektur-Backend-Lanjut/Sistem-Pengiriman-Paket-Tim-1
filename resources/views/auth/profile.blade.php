@extends('layouts.app')

@section('title', 'Profil Pengguna - SiPaket Tim 1')
@section('active_nav', 'profile')
@section('requires_auth', '1')

@section('content')
<div class="container py-5">
    <div id="globalAlert"></div>
    <div class="row justify-content-center g-4">
        <!-- Account Info -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 20px;">
                <div class="card-body p-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="ms-4">
                            <h2 class="fw-bold mb-0" id="profileName">Loading...</h2>
                            <p class="text-muted mb-0" id="profileEmail">Loading...</p>
                        </div>
                    </div>

                    <hr class="my-4" style="opacity: 0.1;">

                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="form-label text-muted small text-uppercase fw-bold">Nomor Telepon</label>
                            <div class="p-3 bg-light rounded-3" id="profilePhone">
                                -
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted small text-uppercase fw-bold">Status Akun</label>
                            <div class="p-3 bg-light rounded-3">
                                <span class="badge bg-success">Pelanggan Terverifikasi</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small text-uppercase fw-bold">Alamat Utama</label>
                            <div class="p-3 bg-light rounded-3" id="profileAddress">
                                Belum ada alamat yang tersimpan.
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small text-uppercase fw-bold">Kota/Kecamatan</label>
                            <div class="p-3 bg-light rounded-3" id="profileCity">
                                -
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small text-uppercase fw-bold">Bergabung Sejak</label>
                            <div class="p-3 bg-light rounded-3" id="profileJoined">
                                -
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small text-uppercase fw-bold">Terakhir Login</label>
                            <div class="p-3 bg-light rounded-3" id="profileLastLogin">
                                -
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 d-grid gap-2">
                        <button class="btn btn-outline-primary px-4 fw-bold" id="editProfileBtn" style="border-radius: 12px;">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profil
                        </button>
                        <button class="btn btn-outline-secondary px-4 fw-bold" id="changePasswordBtn" style="border-radius: 12px;">
                            <i class="bi bi-key me-2"></i>Ubah Password
                        </button>
                        <button class="btn btn-outline-danger px-4 fw-bold" id="logoutBtn" style="border-radius: 12px;">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Profile Form (Moved from Module 3) -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 20px;">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0">Shipping Profile</h3>
                        <button id="btnLoadProfile" type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reload
                        </button>
                    </div>
                    <p class="text-muted small mb-4">Informasi ini digunakan otomatis saat Anda menghitung ongkir atau membuat pengiriman baru.</p>

                    <form id="profileForm">
                        <!-- Informasi Pengirim -->
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-lines-fill me-2"></i>Informasi Pengirim</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary">Sender Name</label>
                                <input name="sender_name" class="form-control" required placeholder="Nama pengirim">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary">Sender Phone</label>
                                <input name="sender_phone" class="form-control" required placeholder="0812xxxx">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary">Sender Email</label>
                                <input name="sender_email" type="email" class="form-control" placeholder="Email pengirim">
                            </div>
                        </div>

                        <!-- Alamat Penjemputan -->
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Alamat Penjemputan</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">Pickup Address</label>
                                <input name="default_pickup_address" class="form-control" required placeholder="Alamat lengkap penjemputan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Province</label>
                                <input name="province" class="form-control" placeholder="Provinsi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Origin City</label>
                                <input name="default_origin_city" class="form-control" required placeholder="Contoh: Jakarta Selatan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">District (Kecamatan)</label>
                                <input name="district" class="form-control" placeholder="Kecamatan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Postal Code</label>
                                <input name="default_origin_postal_code" class="form-control" required placeholder="12345">
                            </div>
                        </div>

                        <!-- Preferensi Pengiriman -->
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-truck me-2"></i>Preferensi Pengiriman</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Preferred Service</label>
                                <select name="preferred_service_type" class="form-select" required>
                                    <option value="regular">Regular</option>
                                    <option value="express">Express</option>
                                    <option value="same_day">Same Day</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Pickup Time</label>
                                <input name="pickup_time" type="time" class="form-control">
                            </div>
                            <div class="col-12 mt-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="insurance" id="insurance" value="1">
                                    <label class="form-check-label fw-semibold text-secondary" for="insurance">Use Insurance</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="isDefault" value="1">
                                    <label class="form-check-label fw-semibold text-secondary" for="isDefault">Set as Default Shipping Profile</label>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Paket -->
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-box-seam me-2"></i>Informasi Paket</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Package Type</label>
                                <input name="preferred_package_type" class="form-control" placeholder="box / envelope / pallet">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Package Weight (kg)</label>
                                <input name="package_weight" type="number" step="0.1" class="form-control" placeholder="0.0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary">Length (cm)</label>
                                <input name="package_length" type="number" step="0.1" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary">Width (cm)</label>
                                <input name="package_width" type="number" step="0.1" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-secondary">Height (cm)</label>
                                <input name="package_height" type="number" step="0.1" class="form-control" placeholder="0">
                            </div>
                            <div class="col-12 mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_fragile" id="isFragile" value="1">
                                    <label class="form-check-label fw-semibold text-secondary" for="isFragile">Fragile Package (Barang Pecah Belah)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-journal-text me-2"></i>Catatan Tambahan</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold text-secondary">Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-bold" style="border-radius: 12px;">
                                <i class="bi bi-save me-2"></i>Save Shipping Profile
                            </button>
                        </div>
                    </form>
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
    const globalAlert = document.getElementById('globalAlert');

    function showAlert(message, type = 'info') {
        globalAlert.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-${type === 'danger' ? 'exclamation-circle' : 'check-circle'}-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY) || '';
    }

    async function callApi(path, method = 'GET', body = null, withAuth = false) {
        const headers = { 'Accept': 'application/json' };
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

    function formToObject(form) {
        const data = Object.fromEntries(new FormData(form).entries());
        // Handle unchecked checkboxes
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!cb.checked) data[cb.name] = 0;
            else data[cb.name] = cb.value;
        });
        Object.keys(data).forEach((key) => { if (data[key] === '') data[key] = null; });
        return data;
    }

    async function loadShippingProfile() {
        try {
            const result = await callApi('/customer/shipping-profile', 'GET', null, true);
            const data = result.data || {};
            const form = document.getElementById('profileForm');
            Object.keys(data).forEach((key) => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = !!data[key];
                    } else {
                        field.value = data[key] ?? '';
                    }
                }
            });
        } catch (error) {
            console.error('Failed to load shipping profile', error);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Load Basic Auth Data
        const userStr = localStorage.getItem('module3_user');
        if (userStr) {
            try {
                const user = JSON.parse(userStr);
                document.getElementById('profileName').textContent = user.name || 'User';
                document.getElementById('profileEmail').textContent = user.email || '-';
                document.getElementById('profilePhone').textContent = user.phone || '-';
                document.getElementById('profileAddress').textContent = user.address || 'Belum ada alamat yang tersimpan.';
                document.getElementById('profileCity').textContent = user.city || '-';
                const joinDate = user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : 'Baru bergabung';
                document.getElementById('profileJoined').textContent = joinDate;
                document.getElementById('profileLastLogin').textContent = 'Hari ini';
            } catch (e) {}
        }

        // Load Extended Shipping Profile
        loadShippingProfile();

        // Save Profile Logic
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

        document.getElementById('btnLoadProfile').addEventListener('click', loadShippingProfile);

        // Logout Logic
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            if (confirm('Apakah Anda yakin ingin keluar?')) {
                try {
                    await callApi('/auth/logout', 'POST', {}, true);
                } catch (_) {}
                localStorage.removeItem(TOKEN_KEY);
                localStorage.removeItem('module3_user');
                window.location.href = '/';
            }
        });
    });
</script>
@endpush
