@push('scripts')
<script src="/vendor/sweetalert2.all.min.js"></script>
<script>
    (() => {
        const fleetApiBase = '/api/v1/fleet';

        const showLoading = (title) => {
            Swal.fire({
                title,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });
        };

        const escapeHtml = (value) => {
            if (value === null || value === undefined) {
                return '-';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const formatHours = (value) => {
            const number = Number(value);
            return Number.isFinite(number) ? number.toFixed(2) : '0.00';
        };

        const formatDateTime = (dateValue) => {
            if (!dateValue) {
                return '-';
            }

            const parsed = new Date(String(dateValue).replace(' ', 'T'));

            if (Number.isNaN(parsed.getTime())) {
                return dateValue;
            }

            return parsed.toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const requestJson = async (url, options = {}) => {
            const response = await fetch(url, {
                ...options,
                headers: {
                    Accept: 'application/json',
                    ...(options.headers || {}),
                },
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Terjadi kesalahan saat memproses permintaan.');
            }

            return payload;
        };

        const buildStatusBadges = (statusBreakdown) => {
            return Object.entries(statusBreakdown || {})
                .map(([status, total]) => `<span class="badge bg-secondary me-1 mb-1">${escapeHtml(status)}: ${escapeHtml(total)}</span>`)
                .join('');
        };

        const buildRouteRows = (routeStats) => {
            if (!Array.isArray(routeStats) || routeStats.length === 0) {
                return '<tr><td colspan="4" class="text-center text-muted">Belum ada data rute selesai.</td></tr>';
            }

            return routeStats.slice(0, 5).map((route, idx) => `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${escapeHtml(route.origin_hub_name)} <i class="bi bi-arrow-right-short"></i> ${escapeHtml(route.destination_hub_name)}</td>
                    <td>${escapeHtml(route.movement_count)}</td>
                    <td>${formatHours(route.average_duration_hours)} jam</td>
                </tr>
            `).join('');
        };

        const buildHistoryRows = (history) => {
            if (!Array.isArray(history) || history.length === 0) {
                return '<tr><td colspan="5" class="text-center text-muted">Belum ada riwayat transit selesai.</td></tr>';
            }

            return history.slice(0, 10).map((item, idx) => `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${escapeHtml(item.origin_hub_name)} <i class="bi bi-arrow-right-short"></i> ${escapeHtml(item.destination_hub_name)}</td>
                    <td>${formatDateTime(item.departed_at)}</td>
                    <td>${formatDateTime(item.arrived_at)}</td>
                    <td><span class="badge bg-primary">${formatHours(item.duration_hours)} jam</span></td>
                </tr>
            `).join('');
        };

        const showTransitReport = (data = {}) => {
            const fleet = data.fleet || {};
            const summary = data.summary || {};
            const history = Array.isArray(data.history) ? data.history : [];
            const routeStats = Array.isArray(data.route_stats) ? data.route_stats : [];

            Swal.fire({
                icon: 'success',
                title: 'Laporan API Diterima',
                width: 1050,
                html: `
                    <div class="text-start mt-3">
                        <div class="alert alert-light border mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="mb-0">Informasi Armada</strong>
                                <span class="badge bg-dark">Plat: ${escapeHtml(fleet.plate_number || '-')}</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6"><small class="text-muted">Fleet ID:</small><div><strong>${escapeHtml(fleet.id || data.fleet_id || '-')}</strong></div></div>
                                <div class="col-md-6"><small class="text-muted">Status Armada:</small><div><strong>${escapeHtml(fleet.status || '-')}</strong></div></div>
                                <div class="col-md-6"><small class="text-muted">Tipe:</small><div><strong>${escapeHtml(fleet.type || '-')}</strong></div></div>
                                <div class="col-md-6"><small class="text-muted">Hub Saat Ini:</small><div><strong>${escapeHtml(fleet.current_hub?.name || '-')}</strong></div></div>
                            </div>
                        </div>

                        <div class="alert alert-primary border mb-3">
                            <div class="row g-2">
                                <div class="col-md-4"><small class="text-muted">Total Pergerakan</small><div><strong>${escapeHtml(summary.total_movements ?? history.length)} x</strong></div></div>
                                <div class="col-md-4"><small class="text-muted">Pergerakan Selesai</small><div><strong>${escapeHtml(summary.completed_movements ?? history.length)} x</strong></div></div>
                                <div class="col-md-4"><small class="text-muted">Pergerakan Berjalan</small><div><strong>${escapeHtml(summary.ongoing_movements ?? 0)} x</strong></div></div>
                                <div class="col-md-4"><small class="text-muted">Rata-rata Transit</small><div><strong>${formatHours(summary.average_duration_hours ?? data.average_duration_hours)} jam</strong></div></div>
                                <div class="col-md-4"><small class="text-muted">Transit Tercepat</small><div><strong>${summary.fastest_duration_hours !== null && summary.fastest_duration_hours !== undefined ? `${formatHours(summary.fastest_duration_hours)} jam` : '-'}</strong></div></div>
                                <div class="col-md-4"><small class="text-muted">Transit Terlama</small><div><strong>${summary.slowest_duration_hours !== null && summary.slowest_duration_hours !== undefined ? `${formatHours(summary.slowest_duration_hours)} jam` : '-'}</strong></div></div>
                                <div class="col-12 mt-2"><small class="text-muted d-block mb-1">Distribusi Status Log</small>${buildStatusBadges(summary.status_breakdown) || '<span class="text-muted">-</span>'}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold mb-2">Ringkasan Rute (Top 5)</h6>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Rute Hub</th>
                                            <th>Pergerakan</th>
                                            <th>Rata-rata Transit</th>
                                        </tr>
                                    </thead>
                                    <tbody>${buildRouteRows(routeStats)}</tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mb-2">
                            <h6 class="fw-bold mb-2">Riwayat Transit Hub (10 Terbaru)</h6>
                            <div class="table-responsive border rounded" style="max-height: 280px; overflow: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Dari <i class="bi bi-arrow-right"></i> Ke</th>
                                            <th>Berangkat</th>
                                            <th>Tiba</th>
                                            <th>Durasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>${buildHistoryRows(history)}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#0d6efd',
            });
        };

        window.viewTransitReport = async (fleetId) => {
            showLoading('Fetching Data Backend...');

            try {
                const response = await requestJson(`${fleetApiBase}/${fleetId}/duration`);
                showTransitReport(response.data || {});
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        };

        window.submitFleet = async () => {
            const form = document.getElementById('addFleetForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const payload = Object.fromEntries(new FormData(form).entries());
            showLoading('Menyimpan Armada Baru...');

            try {
                const response = await requestJson(fleetApiBase, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                await Swal.fire('Sukses', response.message, 'success');
                location.reload();
            } catch (error) {
                Swal.fire('Gagal', error.message, 'error');
            }
        };

        window.updateStatus = async (fleetId, selectElement) => {
            const previousStatus = selectElement.dataset.current || selectElement.value;
            const nextStatus = selectElement.value;

            if (previousStatus === nextStatus) {
                return;
            }

            showLoading('Memperbarui Status...');

            try {
                const response = await requestJson(`${fleetApiBase}/${fleetId}/status`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: nextStatus }),
                });

                selectElement.dataset.current = nextStatus;
                await Swal.fire('Berhasil', response.message, 'success');
                location.reload();
            } catch (error) {
                selectElement.value = previousStatus;
                Swal.fire('Gagal', error.message, 'error');
            }
        };

        window.relocateFleet = (fleetId, selectElement) => {
            const previousHubId = selectElement.dataset.current || '';
            const nextHubId = selectElement.value;

            if (previousHubId === nextHubId) {
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Relokasi',
                text: 'Armada akan dipindahkan ke hub ini. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Pindahkan',
                cancelButtonText: 'Batal',
            }).then(async (result) => {
                if (!result.isConfirmed) {
                    selectElement.value = previousHubId;
                    return;
                }

                showLoading('Memindahkan Armada...');

                try {
                    const response = await requestJson(`${fleetApiBase}/${fleetId}/relocate`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ new_hub_id: nextHubId }),
                    });

                    selectElement.dataset.current = nextHubId;
                    await Swal.fire('Relokasi Berhasil', response.message, 'success');
                    location.reload();
                } catch (error) {
                    selectElement.value = previousHubId;
                    Swal.fire('Gagal Relokasi', error.message, 'error');
                }
            });
        };
    })();
</script>
@endpush
