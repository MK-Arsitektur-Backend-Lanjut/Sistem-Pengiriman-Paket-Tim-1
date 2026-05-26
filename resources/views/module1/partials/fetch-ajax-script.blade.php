<!-- ════════════════════════════════════════════════════════════════════════════════
     FETCH API AJAX SCRIPT - Warehouse & Package Management dengan Modal
     ═════════════════════════════════════════════════════════════════════════════════ -->
<script>
    // API_URL sudah dideklarasikan di scripts.blade.php, jangan duplikasi!
    
    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ NOTIFICATION SYSTEM - Global Alert
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    function showNotification(message, type = 'success') {
        const alertId = 'alert-' + Date.now();
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${alertId}">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                <strong>${type === 'success' ? 'Berhasil!' : 'Error!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insert di bagian atas section (setelah hero)
        const section = document.querySelector('section.py-4');
        const heroDiv = section.querySelector('.m1-hero');
        const alertContainer = heroDiv.nextElementSibling;

        if (alertContainer && alertContainer.classList.contains('alert')) {
            alertContainer.remove();
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = alertHTML;
        heroDiv.after(tempDiv.firstElementChild);

        // Auto-dismiss setelah 5 detik
        setTimeout(() => {
            const alertEl = document.getElementById(alertId);
            if (alertEl) {
                const bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }
        }, 5000);
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ ERROR DISPLAY IN MODAL - Tampilkan validation errors
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    function displayModalErrors(errors, formType = 'warehouse') {
        // Hapus error messages sebelumnya
        document.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Display setiap error
        Object.keys(errors).forEach(fieldName => {
            const fieldId = formType === 'warehouse' 
                ? `warehouse_${fieldName}`
                : fieldName;

            const fieldElement = document.getElementById(fieldId);
            if (fieldElement) {
                fieldElement.classList.add('is-invalid');
                const errorMsg = errors[fieldName][0]; // Get first error message
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback-custom d-block mt-1';
                errorDiv.style.color = '#dc3545';
                errorDiv.textContent = errorMsg;
                fieldElement.after(errorDiv);
            }
        });
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ REFRESH TABLE DATA - Reload tabel tanpa reload halaman
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    async function refreshTableData() {
        try {
            const response = await fetch(`${API_URL}/warehouse`);
            const warehouseData = await response.json();

            const response2 = await fetch(`${API_URL}/package`);
            const packageData = await response2.json();

            if (warehouseData.success && packageData.success) {
                // Update warehouse table
                updateWarehouseTable(warehouseData.data);
                // Update package table
                updatePackageTable(packageData.data);
            }
        } catch (error) {
            console.error('Error refreshing tables:', error);
        }
    }

    function updateWarehouseTable(warehouses) {
        const tbody = document.querySelector('table tbody');
        if (!tbody) return;

        // Jika tabel kosong, reload halaman untuk render data baru dengan helper functions
        // (Karena tabel kompleks dengan template Blade)
        setTimeout(() => window.location.reload(), 500);
    }

    function updatePackageTable(packages) {
        // Similar logic untuk package table
        setTimeout(() => window.location.reload(), 500);
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ MODAL MANAGEMENT - Open/Close Warehouse & Package Modals
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    let warehouseModal = new bootstrap.Modal(document.getElementById('warehouseModal'), { backdrop: 'static' });
    let packageModal = new bootstrap.Modal(document.getElementById('packageModal'), { backdrop: 'static' });

    function openWarehouseModal() {
        document.getElementById('warehouseId').value = '';
        document.getElementById('warehouseForm').reset();
        document.getElementById('warehouseModalTitle').textContent = 'Add Warehouse';
        document.getElementById('warehouseSubmitBtn').textContent = 'Save';
        document.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        warehouseModal.show();
    }

    function closeWarehouseModal() {
        warehouseModal.hide();
    }

    function openPackageModal() {
        document.getElementById('packageId').value = '';
        document.getElementById('packageForm').reset();
        document.getElementById('packageModalTitle').textContent = 'Register Package';
        document.getElementById('packageSubmitBtn').textContent = 'Register';
        document.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        const originDestRow = document.getElementById('origin_destination_row');
        if (originDestRow) {
            originDestRow.classList.remove('d-none');
        }
        
        packageModal.show();
    }

    function closePackageModal() {
        packageModal.hide();
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ WAREHOUSE FORM SUBMIT - AJAX dengan Fetch API
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    const warehouseForm = document.getElementById('warehouseForm');
    if (warehouseForm) {
        warehouseForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Disable submit button untuk prevent double-click
            const submitBtn = document.getElementById('warehouseSubmitBtn');
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';

            const warehouseId = document.getElementById('warehouseId').value;
            const formData = new FormData(warehouseForm);

            // Convert FormData to JSON
            const data = {
                warehouse_name: formData.get('warehouse_name') || '',
                location: formData.get('location') || '',
                hub_id: formData.get('hub_id') ? parseInt(formData.get('hub_id')) : null,
                capacity: parseInt(formData.get('capacity')) || 0,
                current_load: parseInt(formData.get('current_load')) || 0,
                status: formData.get('status') || 'active'
            };

            // Validasi client-side
            if (parseInt(data.current_load) > parseInt(data.capacity)) {
                showNotification('Beban saat ini tidak boleh melebihi kapasitas!', 'danger');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            try {
                const method = warehouseId ? 'PUT' : 'POST';
                const url = warehouseId 
                    ? `${API_URL}/warehouse/${warehouseId}`
                    : `${API_URL}/warehouse`;

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // ✅ SUCCESS
                    showNotification(result.message, 'success');
                    
                    // Tutup modal
                    warehouseModal.hide();

                    // Reset form
                    warehouseForm.reset();
                    document.getElementById('warehouseId').value = '';
                    
                    // Refresh data tabel
                    refreshTableData();
                } else {
                    // ❌ API returned error
                    showNotification(result.message || 'Terjadi kesalahan', 'danger');
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showNotification('Terjadi kesalahan jaringan. Coba lagi.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ EDIT WAREHOUSE - Fetch data dan populate form
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    async function editWarehouse(id) {
        try {
            const response = await fetch(`${API_URL}/warehouse/${id}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                document.getElementById('warehouseId').value = data.id || id;
                document.getElementById('warehouse_name').value = data.warehouse_name || '';
                document.getElementById('warehouse_location').value = data.location || '';
                document.getElementById('warehouse_hub_id').value = data.hub_id || '';
                document.getElementById('warehouse_capacity').value = data.capacity || '';
                document.getElementById('warehouse_current_load').value = data.current_load || 0;
                document.getElementById('warehouse_status').value = data.status || 'active';

                document.getElementById('warehouseModalTitle').textContent = 'Edit Warehouse';
                document.getElementById('warehouseSubmitBtn').textContent = 'Update';
                
                // Clear errors
                document.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                warehouseModal.show();
            } else {
                showNotification('Gagal memuat data warehouse', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat memuat data', 'danger');
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ DELETE WAREHOUSE - Konfirmasi dan hapus via AJAX
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    async function deleteWarehouse(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus warehouse ini?')) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}/warehouse/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message || 'Warehouse berhasil dihapus', 'success');
                refreshTableData();
            } else {
                showNotification(result.message || 'Gagal menghapus warehouse', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat menghapus warehouse', 'danger');
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ PACKAGE FORM SUBMIT - AJAX dengan Fetch API
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    const packageForm = document.getElementById('packageForm');
    if (packageForm) {
        packageForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Disable submit button untuk prevent double-click
            const submitBtn = document.getElementById('packageSubmitBtn');
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Saving...';

            const packageId = document.getElementById('packageId').value;
            const formData = new FormData(packageForm);

            // Calculate volume
            const length = parseFloat(formData.get('length')) || 0;
            const width = parseFloat(formData.get('width')) || 0;
            const height = parseFloat(formData.get('height')) || 0;
            const volume = length * width * height;

            // Convert FormData to JSON
            const data = {
                tracking_number: formData.get('tracking_number') || '',
                sender_name: formData.get('sender_name') || '',
                receiver_name: formData.get('receiver_name') || '',
                origin: formData.get('origin') || '',
                destination: formData.get('destination') || '',
                weight: parseFloat(formData.get('weight')) || 0,
                length: length,
                width: width,
                height: height,
                warehouse_id: parseInt(formData.get('warehouse_id')) || 0,
                package_status: formData.get('package_status') || 'registered'
            };

            try {
                const method = packageId ? 'PUT' : 'POST';
                const url = packageId 
                    ? `${API_URL}/package/${packageId}`
                    : `${API_URL}/package/register`;

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // ✅ SUCCESS
                    showNotification(result.message, 'success');
                    
                    // Tutup modal
                    packageModal.hide();

                    // Reset form
                    packageForm.reset();
                    document.getElementById('packageId').value = '';
                    
                    // Refresh data tabel
                    refreshTableData();
                } else {
                    // ❌ API returned error
                    showNotification(result.message || 'Terjadi kesalahan', 'danger');
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showNotification('Terjadi kesalahan jaringan. Coba lagi.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ EDIT PACKAGE - Fetch data dan populate form
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    async function editPackage(id) {
        try {
            const response = await fetch(`${API_URL}/package/${id}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                document.getElementById('packageId').value = data.id || id;
                document.getElementById('tracking_number').value = data.tracking_number || '';
                document.getElementById('warehouse_id').value = data.warehouse_id || '';
                document.getElementById('sender_name').value = data.sender_name || '';
                document.getElementById('receiver_name').value = data.receiver_name || '';
                document.getElementById('origin').value = data.origin || '';
                document.getElementById('destination').value = data.destination || '';
                document.getElementById('weight').value = data.weight || '';
                document.getElementById('length').value = data.length || '';
                document.getElementById('width').value = data.width || '';
                document.getElementById('height').value = data.height || '';
                document.getElementById('package_status').value = data.package_status || 'registered';

                document.getElementById('packageModalTitle').textContent = 'Edit Package';
                document.getElementById('packageSubmitBtn').textContent = 'Update';
                
                const originDestRow = document.getElementById('origin_destination_row');
                if (originDestRow) {
                    originDestRow.classList.add('d-none');
                }
                
                // Clear errors
                document.querySelectorAll('.invalid-feedback-custom').forEach(el => el.remove());
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                packageModal.show();
            } else {
                showNotification('Gagal memuat data package', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat memuat data', 'danger');
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════════════════╗
    // ║ DELETE PACKAGE - Konfirmasi dan hapus via AJAX
    // ╚═══════════════════════════════════════════════════════════════════════════════╝
    async function deletePackage(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus package ini?')) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}/package/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const result = await response.json();

            if (result.success) {
                showNotification(result.message || 'Package berhasil dihapus', 'success');
                refreshTableData();
            } else {
                showNotification(result.message || 'Gagal menghapus package', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat menghapus package', 'danger');
        }
    }


</script>

<!-- ════════════════════════════════════════════════════════════════════════════════
     CSS untuk Error Display di Modal
     ═════════════════════════════════════════════════════════════════════════════════ -->
<style>
    .is-invalid {
        border-color: #dc3545 !important;
    }

    .invalid-feedback-custom {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
    }

    .invalid-feedback-custom::before {
        content: '⚠ ';
    }
</style>
