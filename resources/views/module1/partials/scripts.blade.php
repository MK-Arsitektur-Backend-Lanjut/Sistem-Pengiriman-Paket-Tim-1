@push('scripts')
<script src="/vendor/axios.min.js"></script>
<script>
    const API_URL = '/api/v1';
    const warehouseModal = new bootstrap.Modal(document.getElementById('warehouseModal'));
    const packageModal = new bootstrap.Modal(document.getElementById('packageModal'));
    const fleetTrackModal = new bootstrap.Modal(document.getElementById('fleetTrackModal'));
    const createShipmentModal = new bootstrap.Modal(document.getElementById('createShipmentModal'));

    // --- Warehouse Functions ---
    function openWarehouseModal() {
        document.getElementById('warehouseId').value = '';
        document.getElementById('warehouseForm').reset();
        document.getElementById('warehouse_usage').value = 0;
        document.getElementById('warehouse_usage_text').textContent = '0%';
        document.getElementById('warehouse_usage_bar').style.width = '0%';
        document.getElementById('warehouse_usage_bar').className = 'progress-bar bg-success';
        document.getElementById('warehouseModalTitle').textContent = 'Add Warehouse';
        document.getElementById('warehouseSubmitBtn').textContent = 'Save';
        warehouseModal.show();
    }

    function calculateUsagePercentage() {
        const capacity = parseFloat(document.getElementById('warehouse_capacity').value) || 0;
        const current_load = parseFloat(document.getElementById('warehouse_current_load').value) || 0;
        let usagePercentage = capacity > 0 ? ((current_load / capacity) * 100).toFixed(2) : 0;
        if(usagePercentage > 100) usagePercentage = 100;
        
        document.getElementById('warehouse_usage').value = usagePercentage;
        document.getElementById('warehouse_usage_text').textContent = usagePercentage + '%';
        
        const bar = document.getElementById('warehouse_usage_bar');
        bar.style.width = usagePercentage + '%';
        if (usagePercentage < 50) bar.className = 'progress-bar bg-success';
        else if (usagePercentage < 80) bar.className = 'progress-bar bg-warning';
        else bar.className = 'progress-bar bg-danger';
    }

    document.getElementById('warehouse_capacity').addEventListener('input', calculateUsagePercentage);
    document.getElementById('warehouse_current_load').addEventListener('input', calculateUsagePercentage);

    function closeWarehouseModal() {
        warehouseModal.hide();
    }

    function editWarehouse(id) {
        axios.get(`${API_URL}/warehouse/${id}`)
            .then(response => {
                const data = response.data.data;
                document.getElementById('warehouseId').value = data.id || id;
                document.getElementById('warehouse_name').value = data.warehouse_name || '';
                document.getElementById('warehouse_location').value = data.location || '';
                document.getElementById('warehouse_hub_id').value = data.hub_id || '';
                document.getElementById('warehouse_capacity').value = data.capacity || '';
                document.getElementById('warehouse_current_load').value = data.current_load || 0;
                document.getElementById('warehouse_status').value = data.status || 'active';
                
                calculateUsagePercentage();
                
                document.getElementById('warehouseModalTitle').textContent = 'Edit Warehouse';
                document.getElementById('warehouseSubmitBtn').textContent = 'Update';
                warehouseModal.show();
            })
            .catch(error => alert('Error loading warehouse data'));
    }

    function saveWarehouse(e) {
        e.preventDefault();
        const id = document.getElementById('warehouseId').value;
        const current_load = document.getElementById('warehouse_current_load').value;
        const capacity = document.getElementById('warehouse_capacity').value;

        if (parseInt(current_load) > parseInt(capacity)) {
            alert('Current Load cannot exceed Capacity');
            return;
        }

        const data = {
            warehouse_name: document.getElementById('warehouse_name').value.trim(),
            location: document.getElementById('warehouse_location').value.trim(),
            hub_id: document.getElementById('warehouse_hub_id').value ? parseInt(document.getElementById('warehouse_hub_id').value) : null,
            capacity: parseInt(capacity),
            current_load: parseInt(current_load),
            status: document.getElementById('warehouse_status').value
        };

        const method = id ? 'put' : 'post';
        const url = id ? `${API_URL}/warehouse/${id}` : `${API_URL}/warehouse`;

        axios[method](url, data)
            .then(response => {
                if (response.data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            })
            .catch(error => alert('Error saving warehouse'));
    }

    function deleteWarehouse(id) {
        if (confirm('Are you sure you want to delete this warehouse?')) {
            axios.delete(`${API_URL}/warehouse/${id}`)
                .then(response => {
                    if (response.data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => alert('Failed to delete warehouse'));
        }
    }

    // --- Package Functions ---
    function openPackageModal() {
        document.getElementById('packageId').value = '';
        document.getElementById('packageForm').reset();
        
        document.getElementById('packageModalTitle').textContent = 'Register Package';
        document.getElementById('packageSubmitBtn').textContent = 'Register';
        document.getElementById('volPreview').style.display = 'none';
        
        const resultBox = document.getElementById('fleet_fit_result');
        if (resultBox) resultBox.style.display = 'none';
        
        const catEl = document.getElementById('prev_category');
        if (catEl) {
            catEl.className = 'badge bg-secondary';
            catEl.textContent = '-';
        }
        
        updateLocation();
        
        loadFleetOptions(); // Load fleets for dropdown
        packageModal.show();
    }

    function closePackageModal() {
        packageModal.hide();
    }

    function editPackage(id) {
        axios.get(`${API_URL}/package/${id}`)
            .then(response => {
                const data = response.data.data;
                document.getElementById('packageId').value = data.id || id;
                document.getElementById('tracking_number').value = data.tracking_number || '';
                document.getElementById('sender_name').value = data.sender_name || '';
                document.getElementById('receiver_name').value = data.receiver_name || '';
                document.getElementById('origin').value = data.origin || '';
                document.getElementById('destination').value = data.destination || '';
                document.getElementById('weight').value = data.weight || '';
                document.getElementById('length').value = data.length || '';
                document.getElementById('width').value = data.width || '';
                document.getElementById('height').value = data.height || '';
                document.getElementById('warehouse_id').value = data.warehouse_id || '';
                document.getElementById('package_status').value = data.package_status || 'registered';
                
                document.getElementById('packageModalTitle').textContent = 'Edit Package';
                document.getElementById('packageSubmitBtn').textContent = 'Update';
                
                updateVolumetric();
                loadFleetOptions();
                
                packageModal.show();
            })
            .catch(error => alert('Error loading package'));
    }

    function savePackage(e) {
        e.preventDefault();
        const id = document.getElementById('packageId').value;
        const data = {
            tracking_number: document.getElementById('tracking_number').value.trim(),
            sender_name: document.getElementById('sender_name').value.trim(),
            receiver_name: document.getElementById('receiver_name').value.trim(),
            origin: document.getElementById('origin').value.trim(),
            destination: document.getElementById('destination').value.trim(),
            weight: parseFloat(document.getElementById('weight').value),
            length: parseFloat(document.getElementById('length').value),
            width: parseFloat(document.getElementById('width').value),
            height: parseFloat(document.getElementById('height').value),
            warehouse_id: parseInt(document.getElementById('warehouse_id').value),
            package_status: document.getElementById('package_status').value
        };

        const method = id ? 'put' : 'post';
        const url = id ? `${API_URL}/package/${id}` : `${API_URL}/package/register`;

        axios[method](url, data)
            .then(response => {
                if (response.data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            })
            .catch(error => {
                if (error.response && error.response.data) {
                    let msg = error.response.data.message;
                    if (error.response.data.errors) {
                        const errs = Object.values(error.response.data.errors).flat();
                        msg += '\n- ' + errs.join('\n- ');
                    }
                    alert('Error: ' + msg);
                } else {
                    alert('Error saving package');
                }
            });
    }

    function deletePackage(id) {
        if (confirm('Are you sure you want to delete this package?')) {
            axios.delete(`${API_URL}/package/${id}`)
                .then(response => {
                    if (response.data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => alert('Failed to delete package'));
        }
    }

    // --- Volumetric Calculation & Fleet Capacity Check ---
    let currentEffectiveWeight = 0;

    function updateVolumetric() {
        const l = parseFloat(document.getElementById('length').value) || 0;
        const w = parseFloat(document.getElementById('width').value) || 0;
        const h = parseFloat(document.getElementById('height').value) || 0;
        const act = parseFloat(document.getElementById('weight').value) || 0;

        if(l>0 && w>0 && h>0) {
            document.getElementById('volPreview').style.display = 'block';
            
            const vol = l * w * h;
            const volWeight = vol / 5000;
            currentEffectiveWeight = Math.max(act, volWeight);
            const isVolumetric = volWeight > act;

            document.getElementById('prev_volume').innerHTML = vol.toFixed(2) + ' cm&sup3;';
            document.getElementById('prev_vol_weight').innerHTML = volWeight.toFixed(2) + ' kg';
            document.getElementById('prev_actual').innerHTML = act.toFixed(2) + ' kg';
            document.getElementById('prev_effective').innerHTML = currentEffectiveWeight.toFixed(2) + ' kg';
            
            const basisEl = document.getElementById('prev_basis');
            if(isVolumetric) {
                basisEl.className = 'badge-volumetric';
                basisEl.textContent = 'volumetric';
            } else {
                basisEl.className = 'badge-actual';
                basisEl.textContent = 'actual';
            }

            let cat = 'large';
            let catColor = 'danger';
            if (vol < 10000 && act < 5) { cat = 'small'; catColor = 'info'; }
            else if (vol < 50000 && act < 20) { cat = 'medium'; catColor = 'success'; }
            const catEl = document.getElementById('prev_category');
            if (catEl) {
                catEl.className = `badge bg-${catColor}`;
                catEl.textContent = cat.charAt(0).toUpperCase() + cat.slice(1);
            }

            updateLocation();
            checkFleetCapacity();
        } else {
            document.getElementById('volPreview').style.display = 'none';
        }
    }

    let fleetsCache = [];
    function loadFleetOptions() {
        const warehouseSelect = document.getElementById('warehouse_id');
        let hubId = '';
        if (warehouseSelect && warehouseSelect.selectedIndex >= 0 && warehouseSelect.value) {
            hubId = warehouseSelect.options[warehouseSelect.selectedIndex].getAttribute('data-hub-id') || '';
        }

        let url = `${API_URL}/fleet?status=idle`;
        if (hubId) {
            url += `&hub_id=${hubId}`;
        }

        axios.get(url)
            .then(response => {
                if (response.data && response.data.data) {
                    if (Array.isArray(response.data.data)) {
                        fleetsCache = response.data.data;
                    } else if (Array.isArray(response.data.data.data)) {
                        fleetsCache = response.data.data.data;
                    } else {
                        fleetsCache = [];
                    }
                    
                    const select = document.getElementById('fleet_check_select');
                    select.innerHTML = '<option value="">-- Select Fleet --</option>';
                    
                    fleetsCache.forEach(fleet => {
                        select.innerHTML += `<option value="${fleet.id}" data-cap="${fleet.capacity}">${fleet.plate_number} (${fleet.type}) - Capacity: ${fleet.capacity} kg</option>`;
                    });
                }
            })
            .catch(error => console.error('Failed to load fleet', error));
    }

    function updateLocation() {
        const locEl = document.getElementById('prev_location');
        if (!locEl) return;
        
        const origin = document.getElementById('origin').value.trim() || '-';
        const destination = document.getElementById('destination').value.trim() || '-';
        const status = document.getElementById('package_status').value;
        const warehouseSelect = document.getElementById('warehouse_id');
        let hubName = '';
        if (warehouseSelect.selectedIndex >= 0 && warehouseSelect.value) {
            const text = warehouseSelect.options[warehouseSelect.selectedIndex].text;
            hubName = text.includes(' - ') ? text.split(' - ')[1] : text;
        }
        
        let locHtml = '-';
        if (status === 'delivered') {
            locHtml = `<span class="text-primary fw-semibold"><i class="bi bi-house-door-fill me-1"></i>${destination}</span>`;
        } else if (status === 'shipped' || status === 'in_transit') {
            locHtml = `<span class="text-success"><i class="bi bi-truck me-1"></i>To ${destination}</span>`;
        } else if (hubName && hubName !== '- No Hub -') {
            locHtml = `<span class="hub-chip"><i class="bi bi-geo-alt-fill"></i>${hubName}</span>`;
        } else {
            locHtml = `<span class="text-muted"><i class="bi bi-box me-1"></i>Origin: ${origin}</span>`;
        }
        locEl.innerHTML = locHtml;
    }

    function checkFleetCapacity() {
        const select = document.getElementById('fleet_check_select');
        const resultBox = document.getElementById('fleet_fit_result');
        const icon = document.getElementById('fleet_fit_icon');
        const title = document.getElementById('fleet_fit_title');
        const desc = document.getElementById('fleet_fit_desc');
        
        if (!select || !resultBox) return;
        
        if (!select.value || currentEffectiveWeight <= 0) {
            resultBox.style.display = 'none';
            return;
        }

        const selectedOption = select.options[select.selectedIndex];
        const capacity = parseFloat(selectedOption.getAttribute('data-cap'));
        const plate = selectedOption.text.split(' (')[0];

        resultBox.style.display = 'block';
        if (currentEffectiveWeight <= capacity) {
            resultBox.className = 'alert mt-3 mb-0 alert-success border-success bg-success bg-opacity-10';
            icon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            title.innerHTML = 'Capacity FITS';
            title.className = 'fw-bold fs-6 text-success mb-1';
            desc.innerHTML = `The effective weight (<strong>${currentEffectiveWeight} kg</strong>) fits comfortably within ${plate}'s capacity (${capacity} kg).`;
        } else {
            resultBox.className = 'alert mt-3 mb-0 alert-danger border-danger bg-danger bg-opacity-10';
            icon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
            title.innerHTML = 'DOES NOT FIT';
            title.className = 'fw-bold fs-6 text-danger mb-1';
            desc.innerHTML = `The effective weight (<strong>${currentEffectiveWeight} kg</strong>) exceeds ${plate}'s maximum capacity (${capacity} kg)!`;
        }
    }

    // --- Live Fleet Tracking Integration ---
    function openFleetModal(hubId, hubName) {
        document.getElementById('fleetModalHubLabel').textContent = `Showing fleets at ${hubName} (Hub ID: ${hubId}).`;
        document.getElementById('fleetModalContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted small">Loading fleet data...</p></div>';
        fleetTrackModal.show();

        axios.get(`${API_URL}/fleet`)
            .then(response => {
                let allFleets = [];
                if (response.data && response.data.data) {
                    if (Array.isArray(response.data.data)) {
                        allFleets = response.data.data;
                    } else if (Array.isArray(response.data.data.data)) {
                        allFleets = response.data.data.data;
                    }
                }
                const hubFleets = allFleets.filter(f => f.current_hub_id == hubId);
                
                let html = '';
                if (hubFleets.length === 0) {
                    html = '<div class="alert alert-light text-center border text-muted"><i class="bi bi-truck text-muted opacity-50 d-block fs-3 mb-2"></i>No fleets at this hub currently.</div>';
                } else {
                    hubFleets.forEach(f => {
                        let statusHtml = '';
                        if(f.status === 'idle') statusHtml = '<span class="status-badge-idle">Idle</span>';
                        else if(f.status === 'in_transit') statusHtml = '<span class="status-badge-in_transit">In Transit</span>';
                        else statusHtml = '<span class="status-badge-maintenance">Maintenance</span>';
                        
                        html += `
                        <div class="fleet-info-row">
                            <div class="fleet-info-icon"><i class="bi bi-truck"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark">${f.plate_number}</strong>
                                    ${statusHtml}
                                </div>
                                <div class="text-muted small">
                                    Type: <span class="text-capitalize">${f.type}</span> &bull; Capacity: ${f.capacity} kg
                                </div>
                            </div>
                        </div>`;
                    });
                }
                
                document.getElementById('fleetModalContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('fleetModalContent').innerHTML = '<div class="alert alert-danger">Failed to load fleet data. Try again.</div>';
            });
    }
</script>
@endpush
