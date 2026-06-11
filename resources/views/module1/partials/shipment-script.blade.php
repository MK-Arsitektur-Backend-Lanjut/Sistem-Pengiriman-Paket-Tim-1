@push('scripts')
<script>
    // ═══════════════════════════════════════════════════════════════
    // CREATE SHIPMENT FROM PACKAGE - Functions
    // ═══════════════════════════════════════════════════════════════
    
    let currentShipmentOriginHubId = null;
    let availableDestinationHubs = [];
    
    function openCreateShipmentModal(packageId, trackingNumber) {
        document.getElementById('shipment_package_id').value = packageId;
        document.getElementById('shipment_package_tracking').value = trackingNumber;
        document.getElementById('shipment_origin_hub').value = '';
        document.getElementById('shipment_destination_hub').value = '';
        document.getElementById('hub_error_msg').style.display = 'none';
        document.getElementById('shipment_info_box').style.display = 'none';
        
        // Load available destination hubs
        axios.get(`${API_URL}/package/${packageId}/available-destination-hubs`)
            .then(response => {
                if (response.data && response.data.data) {
                    const data = response.data.data;
                    currentShipmentOriginHubId = data.origin_hub.id;
                    availableDestinationHubs = data.available_destination_hubs;
                    
                    // Set origin hub
                    document.getElementById('shipment_origin_hub').value = data.origin_hub.name;
                    
                    // Populate destination hub select
                    const select = document.getElementById('shipment_destination_hub');
                    select.innerHTML = '<option value="">-- Select Destination Hub --</option>';
                    
                    availableDestinationHubs.forEach(hub => {
                        const option = document.createElement('option');
                        option.value = hub.id;
                        option.textContent = hub.name;
                        select.appendChild(option);
                    });
                    
                    createShipmentModal.show();
                }
            })
            .catch(error => {
                alert('Error loading hub data: ' + (error.response?.data?.message || 'Unknown error'));
                console.error(error);
            });
    }
    
    function closeCreateShipmentModal() {
        createShipmentModal.hide();
        document.getElementById('createShipmentForm').reset();
        currentShipmentOriginHubId = null;
        availableDestinationHubs = [];
    }
    
    function validateDestinationHub() {
        const select = document.getElementById('shipment_destination_hub');
        const errorMsg = document.getElementById('hub_error_msg');
        const infoBox = document.getElementById('shipment_info_box');
        
        if (!select.value) {
            errorMsg.style.display = 'none';
            infoBox.style.display = 'none';
            return true;
        }
        
        const selectedHubId = parseInt(select.value);
        
        if (selectedHubId === currentShipmentOriginHubId) {
            errorMsg.style.display = 'block';
            errorMsg.textContent = '❌ Destination hub cannot be the same as origin hub! Please select a different hub.';
            infoBox.style.display = 'none';
            select.classList.add('is-invalid');
            return false;
        } else {
            errorMsg.style.display = 'none';
            infoBox.style.display = 'block';
            select.classList.remove('is-invalid');
            return true;
        }
    }
    
    function submitCreateShipment(e) {
        e.preventDefault();
        
        if (!validateDestinationHub()) {
            return;
        }
        
        const packageId = document.getElementById('shipment_package_id').value;
        const destinationHubId = document.getElementById('shipment_destination_hub').value;
        const submitBtn = document.getElementById('shipment_submit_btn');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
        
        axios.post(`${API_URL}/shipment/from-package/${packageId}`, {
            destination_hub_id: destinationHubId
        }, {
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem('module3_jwt_token') || localStorage.getItem('token') || ''),
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                if (response.data.status === 'success') {
                    alert('✅ Shipment created successfully!\nTracking: ' + response.data.data.tracking_number);
                    closeCreateShipmentModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            })
            .catch(error => {
                const errorData = error.response?.data;
                let errorMsg = 'Failed to create shipment';
                
                if (errorData?.code === 'SAME_HUB_ERROR') {
                    errorMsg = '❌ ' + errorData.message;
                } else if (errorData?.message) {
                    errorMsg = errorData.message;
                }
                
                alert(errorMsg);
                console.error(error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
    }
</script>
@endpush
