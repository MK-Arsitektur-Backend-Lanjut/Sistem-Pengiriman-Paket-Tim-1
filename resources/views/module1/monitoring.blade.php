@extends('layouts.app')
@section('title', 'Modul 1 - Warehouse & Package Monitoring')
@section('meta_description', 'Monitoring kapasitas warehouse dan manajemen paket masuk/keluar.')
@section('active_nav', 'module1')
@section('requires_auth', '1')
@include('module1.partials.styles')
@section('content')
<section class="py-4">
<div class="container">
{{-- HERO --}}
@if(isset($error))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>{{ $error }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
<div class="m1-hero p-4 p-lg-5 mb-4">
    <div class="m1-hero-content d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <span class="badge text-bg-light text-primary mb-2">Modul 1 - Manajemen Gudang</span>
            <h1 class="h3 fw-bold mb-2">Warehouse &amp; Package Control Center</h1>
            <p class="mb-0 text-white-50">Pantau kapasitas gudang, daftarkan paket baru, dan cek kelayakan muat armada secara real-time.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url('/home') }}" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-house me-1"></i> Home</a>
            <button class="btn btn-outline-light btn-sm fw-semibold" onclick="openWarehouseModal()"><i class="bi bi-plus-circle me-1"></i> Tambah Gudang</button>
            <button class="btn btn-outline-light btn-sm fw-semibold" onclick="openPackageModal()"><i class="bi bi-box-seam me-1"></i> Daftarkan Paket</button>
        </div>
    </div>
</div>
{{-- KPI --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-icon kpi-icon-warehouse"><i class="bi bi-building"></i></span>
                <span class="pill-info">Total Gudang</span>
            </div>
            <div class="h4 fw-bold mb-0">{{ $total_warehouses }}</div>
            <small class="text-muted">{{ $active_warehouses }} aktif</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-icon kpi-icon-package"><i class="bi bi-boxes"></i></span>
                <span class="pill-info pill-green">Total Paket</span>
            </div>
            <div class="h4 fw-bold mb-0">{{ $total_packages }}</div>
            <small class="text-muted">terdaftar</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-icon kpi-icon-capacity"><i class="bi bi-layers"></i></span>
                <span class="pill-info pill-sky">Total Kapasitas</span>
            </div>
            <div class="h4 fw-bold mb-0">{{ number_format($total_capacity) }}</div>
            <small class="text-muted">kg</small>
        </div>
    </div>
</div>
{{-- CATEGORIES --}}
@if(count($packages_by_dimension) > 0)
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-icon kpi-icon-small"><i class="bi bi-box"></i></span>
                <span class="pill-info">Small</span>
            </div>
            <div class="h4 fw-bold mb-0 text-primary">{{ $packages_by_dimension['small'] ?? 0 }}</div>
            <small class="text-muted">Volume &le; 1000 cm&sup3;</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-icon kpi-icon-medium"><i class="bi bi-boxes"></i></span>
                <span class="pill-info pill-green">Medium</span>
            </div>
            <div class="h4 fw-bold mb-0 text-success">{{ $packages_by_dimension['medium'] ?? 0 }}</div>
            <small class="text-muted">Volume 1000 - 5000 cm&sup3;</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="kpi-icon kpi-icon-large"><i class="bi bi-archive"></i></span>
                <span class="pill-info pill-red">Large</span>
            </div>
            <div class="h4 fw-bold mb-0 text-danger">{{ $packages_by_dimension['large'] ?? 0 }}</div>
            <small class="text-muted">Volume &gt; 5000 cm&sup3;</small>
        </div>
    </div>
</div>
@endif
{{-- WAREHOUSE_TABLE --}}
<div class="soft-card mb-4">
    <div class="soft-card-header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Warehouse Management</h5>
        <button onclick="openWarehouseModal()" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-plus me-1"></i>Add Warehouse</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Name</th><th>Location</th><th>Linked Hub</th>
                    <th>Capacity</th><th>Current Load</th><th>Usage %</th><th>Status</th><th class="pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                <tr>
                    <td class="ps-4 fw-semibold text-primary">{{ $warehouse['warehouse_name'] }}</td>
                    <td class="text-muted">{{ $warehouse['location'] }}</td>
                    <td>@if($warehouse['hub_name'] ?? false)<span class="hub-chip"><i class="bi bi-geo-alt-fill"></i>{{ $warehouse['hub_name'] }}</span>@else<span class="text-muted">-</span>@endif</td>
                    <td>{{ number_format($warehouse['capacity']) }}</td>
                    <td>{{ number_format($warehouse['current_load']) }}</td>
                    <td>
                        @php $pct=$warehouse['usage_percentage']; $bc=$pct<50?'success':($pct<80?'warning':'danger'); @endphp
                        <div class="d-flex align-items-center gap-2">
                            <div class="hub-progress flex-grow-1" style="min-width:60px"><div class="progress-bar bg-{{ $bc }}" style="width:{{ $pct }}%"></div></div>
                            <span class="small fw-semibold">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($warehouse['status']==='active')
                        <span class="status-badge-idle">Active</span>
                        @else
                        <span class="status-badge-maintenance">Inactive</span>
                        @endif
                    </td>
                    <td class="pe-4">
                        <button onclick="editWarehouse({{ $warehouse['id'] }})" class="btn btn-warning btn-sm me-1"><i class="bi bi-pencil"></i></button>
                        <button onclick="deleteWarehouse({{ $warehouse['id'] }})" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No warehouses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{-- PACKAGE_TABLE --}}
<div class="soft-card mb-4">
    <div class="soft-card-header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-success"></i>Package Management</h5>
        <button onclick="openPackageModal()" class="btn btn-success btn-sm rounded-pill"><i class="bi bi-plus me-1"></i>Register Package</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Tracking #</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Actual Weight</th>
                    <th>Effective Weight</th>
                    <th>Dimensions (L&times;W&times;H)</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th class="pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $package)
                @php
                    $vol = $package['volume'];
                    $volW = round(($vol) / 5000, 2);
                    $effW = max($package['weight'], $volW);
                    $isVol = $effW > $package['weight'];
                    $cat = $package['dimension_category'];
                    $catColor = $cat==='small'?'info':($cat==='medium'?'success':'danger');
                    $hubId = $package['warehouse']['hub_id'] ?? null;
                    $hubName = $package['warehouse']['hub']['name'] ?? null;
                    
                    $st = strtolower($package['status'] ?? 'registered');
                    $bg = 'secondary';
                    if ($st === 'pending') $bg = 'danger';
                    elseif ($st === 'delivered') $bg = 'primary';
                    elseif ($st === 'shipped' || $st === 'in_transit') $bg = 'success';
                @endphp
                <tr>
                    <td class="ps-4 fw-bold text-primary">{{ $package['tracking_number'] }}</td>
                    <td>{{ $package['sender_name'] }}</td>
                    <td>{{ $package['receiver_name'] }}</td>
                    <td>{{ $package['weight'] }} kg</td>
                    <td>
                        <span class="fw-semibold">{{ $effW }} kg</span>
                        <br>
                        @if($isVol)
                        <span class="badge-volumetric">volumetric</span>
                        @else
                        <span class="badge-actual">actual</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-semibold">{{ $package['length'] }}&times;{{ $package['width'] }}&times;{{ $package['height'] }} cm</span><br>
                        <small class="text-muted">{{ number_format($vol, 2) }} cm&sup3;</small>
                    </td>
                    <td><span class="badge bg-{{ $catColor }}">{{ ucfirst($cat) }}</span></td>
                    <td>
                        <span class="badge bg-{{ $bg }}">{{ ucfirst(str_replace('_',' ',$st)) }}</span>
                    </td>
                    <td class="pe-4">
                        <button onclick="editPackage({{ $package['id'] }})" class="btn btn-warning btn-sm me-1"><i class="bi bi-pencil"></i></button>
                        <button onclick="deletePackage({{ $package['id'] }})" class="btn btn-danger btn-sm me-1"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No packages found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Pagination Links -->
    <div class="d-flex justify-content-center py-3 border-top">
        {{ $packages->links() }}
    </div>
</div>
</div>
</section>
{{-- WAREHOUSE_MODAL --}}
<div class="modal fade" id="warehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-blue">
                <h5 class="modal-title" id="warehouseModalTitle">Add Warehouse</h5>
                <button type="button" class="btn-close" onclick="closeWarehouseModal()"></button>
            </div>
            <form id="warehouseForm" onsubmit="saveWarehouse(event)">
                <div class="modal-body">
                    <input type="hidden" id="warehouseId">
                    <div class="mb-3"><label class="form-label">Warehouse Name</label><input type="text" class="form-control" id="warehouse_name" required></div>
                    <div class="mb-3"><label class="form-label">Location</label><input type="text" class="form-control" id="warehouse_location" required></div>
                    <div class="mb-3">
                        <label class="form-label">Linked Hub</label>
                        <select class="form-select" id="warehouse_hub_id">
                            <option value="">- No Hub -</option>
                            @foreach($all_hubs as $hub)
                            <option value="{{ $hub->id }}">{{ $hub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="mb-3"><label class="form-label">Capacity (Unit)</label><input type="number" class="form-control" id="warehouse_capacity" required></div></div>
                        <div class="col-md-6"><div class="mb-3"><label class="form-label">Current Load</label><input type="number" class="form-control" id="warehouse_current_load" required min="0"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label d-flex justify-content-between"><span>Usage %</span><span id="warehouse_usage_text" class="fw-bold text-primary">0%</span></label>
                                <div class="hub-progress mt-2" style="height: 12px; background: #e2e8f0;"><div id="warehouse_usage_bar" class="progress-bar bg-success" style="width: 0%; border-radius: 999px;"></div></div>
                                <input type="hidden" id="warehouse_usage" value="0">
                            </div>
                        </div>
                        <div class="col-md-6"><div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="warehouse_status"><option value="available">Available</option><option value="full">Full</option><option value="overload">Overload</option></select></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeWarehouseModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="warehouseSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- PACKAGE_MODAL --}}
<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-blue">
                <h5 class="modal-title" id="packageModalTitle">Register Package</h5>
                <button type="button" class="btn-close" onclick="closePackageModal()"></button>
            </div>
            <form id="packageForm" onsubmit="savePackage(event)">
                <div class="modal-body">
                    <input type="hidden" id="packageId">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Tracking Number</label><input type="text" class="form-control" id="tracking_number" required></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Warehouse</label>
                            <select class="form-select" id="warehouse_id" required onchange="updateLocation(); loadFleetOptions();">
                                <option value="">-- Select Warehouse --</option>
                                @foreach($warehouses as $wh)
                                <option value="{{ $wh['id'] }}" data-hub-id="{{ $wh['hub_id'] }}">{{ $wh['warehouse_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Sender Name</label><input type="text" class="form-control" id="sender_name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Receiver Name</label><input type="text" class="form-control" id="receiver_name" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Origin</label><input type="text" class="form-control" id="origin" required oninput="updateLocation()"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Destination</label><input type="text" class="form-control" id="destination" required oninput="updateLocation()"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Weight (kg)</label><input type="number" class="form-control" id="weight" step="0.01" required oninput="updateVolumetric()"></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="package_status" onchange="updateLocation()">
                                <option value="registered">Registered</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                    </div>
                    <h6 class="border-top pt-3 mt-2 mb-3 fw-semibold">Package Dimensions</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Length (cm)</label><input type="number" class="form-control" id="length" step="0.01" required oninput="updateVolumetric()"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Width (cm)</label><input type="number" class="form-control" id="width" step="0.01" required oninput="updateVolumetric()"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Height (cm)</label><input type="number" class="form-control" id="height" step="0.01" required oninput="updateVolumetric()"></div>
                    </div>
                    <div class="vol-preview" id="volPreview" style="display:none">
                        <div class="fw-semibold mb-2" style="font-size:.85rem;color:#1e3a8a;"><i class="bi bi-calculator me-1"></i>Volumetric Weight Calculation</div>
                        <div class="vol-preview-row"><span class="vol-preview-label">Volume (L&times;W&times;H)</span><span class="vol-preview-value" id="prev_volume">-</span></div>
                        <div class="vol-preview-row"><span class="vol-preview-label">Volumetric Weight (&divide;5000)</span><span class="vol-preview-value" id="prev_vol_weight">-</span></div>
                        <div class="vol-preview-row"><span class="vol-preview-label">Actual Weight</span><span class="vol-preview-value" id="prev_actual">-</span></div>
                        <hr class="vol-preview-divider">
                        <div class="vol-preview-row"><span class="vol-preview-label fw-bold" style="color:#1e3a8a">Effective Weight (applied)</span><span class="vol-preview-value text-primary" id="prev_effective">-</span></div>
                        <div class="vol-preview-row"><span class="vol-preview-label">Calculation basis</span><span id="prev_basis" class="badge-actual">actual</span></div>
                        <hr class="vol-preview-divider">
                        <div class="vol-preview-row align-items-center"><span class="vol-preview-label fw-bold">Package Category</span><span id="prev_category" class="badge bg-secondary">-</span></div>
                        <div class="vol-preview-row align-items-center mt-2"><span class="vol-preview-label fw-bold">Last Location Tracker</span><span id="prev_location" class="text-muted">-</span></div>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <label class="form-label fw-bold text-dark"><i class="bi bi-truck me-2 text-primary"></i>Check Fleet Capacity</label>
                        <p class="small text-muted mb-2">Select a fleet to simulate if the package's effective weight fits its maximum capacity.</p>
                        <select class="form-select border-primary" id="fleet_check_select" onchange="checkFleetCapacity()">
                            <option value="">-- Select Fleet --</option>
                        </select>
                        <div id="fleet_fit_result" class="alert mt-3 mb-0" style="display:none; padding: 0.75rem 1rem;">
                            <div class="d-flex align-items-center">
                                <div class="fs-1 me-3 lh-1" id="fleet_fit_icon"></div>
                                <div>
                                    <div class="fw-bold fs-6" id="fleet_fit_title">...</div>
                                    <div class="small" id="fleet_fit_desc">...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePackageModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" id="packageSubmitBtn">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- FLEET_MODAL --}}
<div class="modal fade" id="fleetTrackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header modal-header-blue">
                <h5 class="modal-title"><i class="bi bi-radar me-2"></i>Live Fleet Tracking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="fleetModalHubLabel">Menampilkan armada di hub paket ini.</p>
                <div id="fleetModalContent">
                    <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted small">Memuat data armada...</p></div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Buka Fleet & Hub</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
{{-- CREATE_SHIPMENT_MODAL --}}
<div class="modal fade" id="createShipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-blue">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Create Shipment from Package</h5>
                <button type="button" class="btn-close" onclick="closeCreateShipmentModal()"></button>
            </div>
            <form id="createShipmentForm" onsubmit="submitCreateShipment(event)">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Info:</strong> Create a new shipment from this package. The destination hub must be different from the origin hub.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Package Tracking #</label>
                        <input type="text" class="form-control" id="shipment_package_tracking" disabled>
                        <input type="hidden" id="shipment_package_id">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Origin Hub (Auto-detected)</label>
                            <input type="text" class="form-control" id="shipment_origin_hub" disabled>
                            <small class="text-muted">From warehouse location</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Destination Hub <span class="text-danger">*</span></label>
                            <select class="form-select" id="shipment_destination_hub" required onchange="validateDestinationHub()">
                                <option value="">-- Select Destination Hub --</option>
                            </select>
                            <small class="text-muted">Must be different from origin</small>
                            <div id="hub_error_msg" class="invalid-feedback d-block mt-2" style="display:none; color:#dc3545;"></div>
                        </div>
                    </div>
                    
                    <div class="progress-box p-3 bg-light rounded mb-3" id="shipment_info_box" style="display:none;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                            <span class="fw-semibold">Ready to Create Shipment</span>
                        </div>
                        <small class="text-muted">All validations passed. Click "Create Shipment" to proceed.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateShipmentModal()">Cancel</button>
                    <button type="submit" class="btn btn-success" id="shipment_submit_btn">
                        <i class="bi bi-plus-circle me-1"></i>Create Shipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@include('module1.partials.scripts')
@include('module1.partials.shipment-script')
