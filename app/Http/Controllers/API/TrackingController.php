<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use App\Repositories\Contracts\TrackingRepositoryInterface;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    protected $shipmentRepo;
    protected $trackingRepo;

    public function __construct(
        ShipmentRepositoryInterface $shipmentRepo,
        TrackingRepositoryInterface $trackingRepo
    ) {
        $this->shipmentRepo = $shipmentRepo;
        $this->trackingRepo = $trackingRepo;
    }

    /**
     * Register shipment dengan tracking number
     * POST /api/v1/tracking
     * 
     * PROTECTED: Requires auth:sanctum
     * M3 Integration: Customer ownership
     * M1 Integration: Shipment MUST have a package from warehouse
     * 
     * Note: sender_name, receiver_name, weight, dimensions fetched from package relationship
     */
    public function store(Request $request)
    {
        $customer = auth('api')->user();
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - please login first'
            ], 401);
        }
        
        // Option B: Every shipment MUST come from package (M1 integration)
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'destination_hub_id' => 'required|exists:hubs,id',
        ]);

        try {
            $package = \App\Models\Package::with('warehouse.hub')->findOrFail($validated['package_id']);
            
            // Get origin hub from warehouse
            $originHubId = $package->warehouse?->hub_id;
            if (!$originHubId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paket tidak ada di warehouse yang valid (M1 integration failed)'
                ], 422);
            }
            
            // Validate destination is different from origin
            if ($originHubId == $validated['destination_hub_id']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hub tujuan harus berbeda dari hub asal'
                ], 422);
            }

            $shipment = \App\Models\Shipment::create([
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'tracking_number' => $this->generateTrackingNumber(),
                'origin_hub_id' => $originHubId,
                'destination_hub_id' => $validated['destination_hub_id'],
                'current_hub_id' => $originHubId,
                'status' => 'pending'
            ]);

            // Record initial history
            $this->trackingRepo->recordHistory($shipment->id, [
                'status' => 'pending',
                'from_hub_id' => $originHubId,
                'notes' => 'Paket dari gudang telah terdaftar untuk pengiriman (M1→M2 integration)'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Paket berhasil didaftarkan!',
                'data' => $shipment->load(['customer', 'package', 'originHub', 'destinationHub', 'trackingHistories'])
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendaftarkan paket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cari paket berdasarkan tracking number
     * GET /api/tracking/{tracking_number}
     */
    public function show($trackingNumber)
    {
        try {
            $shipment = $this->shipmentRepo->getShipmentByTrackingNumber($trackingNumber);

            return response()->json([
                'status' => 'success',
                'data' => $shipment
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket dengan nomor resi ' . $trackingNumber . ' tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Dapatkan riwayat status paket (kronologis)
     * GET /api/tracking/{tracking_number}/history
     */
    public function showHistory($trackingNumber)
    {
        try {
            $shipment = $this->shipmentRepo->getShipmentByTrackingNumber($trackingNumber);
            $histories = $this->trackingRepo->getHistoryByShipment($shipment->id);

            return response()->json([
                'status' => 'success',
                'tracking_number' => $shipment->tracking_number,
                'current_status' => $shipment->status,
                'shipment' => $shipment,
                'history' => $histories
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update status paket (hub transition)
     * PATCH /api/v1/tracking/{tracking_number}/status
     * 
     * M4 Integration: Update via fleet movement + hub transit
     */
    public function updateStatus(Request $request, $trackingNumber)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_transit,in_hub,on_delivery,delivered,failed',
            'current_hub_id' => 'nullable|exists:hubs,id',
            'fleet_id' => 'nullable|exists:fleets,id',
            'notes' => 'nullable|string'
        ]);

        try {
            $shipment = $this->shipmentRepo->getShipmentByTrackingNumber($trackingNumber);

            // Update shipment status
            $this->shipmentRepo->updateShipmentStatus($shipment->id, $validated['status']);
            
            // Update current hub and fleet if provided (M4 integration)
            if (isset($validated['current_hub_id'])) {
                $shipment->update(['current_hub_id' => $validated['current_hub_id']]);
            }
            
            if (isset($validated['fleet_id'])) {
                $shipment->update(['fleet_id' => $validated['fleet_id']]);
            }

            // Record history
            $fromHubId = $shipment->current_hub_id ?? $shipment->origin_hub_id;
            $historyData = [
                'status' => $validated['status'],
                'from_hub_id' => $fromHubId,
                'to_hub_id' => $validated['current_hub_id'] ?? null,
                'notes' => $validated['notes'] ?? null
            ];

            $this->trackingRepo->recordHistory($shipment->id, $historyData);

            $shipment = $shipment->fresh()->load(['package', 'fleet', 'originHub', 'destinationHub', 'currentHub', 'trackingHistories']);

            return response()->json([
                'status' => 'success',
                'message' => 'Status paket berhasil diperbarui!',
                'data' => $shipment
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cari paket dengan berbagai kriteria
     * GET /api/tracking/search?q=...
     */
    public function search(Request $request)
    {
        $keyword = $request->query('q');

        if (!$keyword) {
            return response()->json([
                'status' => 'error',
                'message' => 'Masukkan kata kunci pencarian'
            ], 400);
        }

        try {
            $results = $this->shipmentRepo->searchShipment($keyword);

            return response()->json([
                'status' => 'success',
                'keyword' => $keyword,
                'total' => $results->total(),
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Daftar semua paket dengan filter
     * GET /api/tracking?status=pending
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        try {
            $shipments = $this->shipmentRepo->getAllShipments($search, $status);

            return response()->json([
                'status' => 'success',
                'total' => $shipments->total(),
                'data' => $shipments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data paket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * NEW ENDPOINTS: M1, M2, M3, M4 INTEGRATION
     * ══════════════════════════════════════════════════════════════
     */

    /**
     * Get available destination hubs for a package
     * GET /api/v1/package/{package_id}/available-destination-hubs
     * 
     * Returns all hubs except the origin hub of the package
     */
    public function availableDestinationHubs($packageId)
    {
        try {
            $package = \App\Models\Package::with('warehouse.hub')->findOrFail($packageId);
            
            $originHubId = $package->warehouse?->hub_id;
            if (!$originHubId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paket tidak ada di warehouse yang valid'
                ], 422);
            }
            
            $originHub = \App\Models\Hub::find($originHubId);
            $availableHubs = \App\Models\Hub::where('id', '!=', $originHubId)
                ->orderBy('name')
                ->get(['id', 'name']);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'origin_hub' => $originHub,
                    'available_destination_hubs' => $availableHubs
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Create shipment from package (M1 integration)
     * POST /api/v1/shipment/from-package/{package_id}
     * 
     * PROTECTED: Requires auth:sanctum
     * Creates Shipment from Package, eliminating data duplication
     * Data (sender_name, receiver_name, weight, etc.) fetched via package relationship
     */
    public function createFromPackage(Request $request, $packageId)
    {
        $customer = auth('api')->user();
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - please login first'
            ], 401);
        }
        
        try {
            $package = \App\Models\Package::with('warehouse.hub')->findOrFail($packageId);
            
            $validated = $request->validate([
                'destination_hub_id' => 'required|exists:hubs,id',
            ]);
            
            // Get origin hub from warehouse
            $originHubId = $package->warehouse?->hub_id;
            if (!$originHubId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paket tidak ada di warehouse yang valid. Mohon pastikan paket sudah diassign ke warehouse dengan hub yang aktif.',
                    'code' => 'NO_WAREHOUSE_HUB'
                ], 422);
            }
            
            $originHub = \App\Models\Hub::find($originHubId);
            $destinationHub = \App\Models\Hub::find($validated['destination_hub_id']);
            
            // Validate destination is different from origin
            if ($originHubId == $validated['destination_hub_id']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Hub tujuan '{$destinationHub->name}' tidak boleh sama dengan hub asal '{$originHub->name}'. Pilih hub tujuan yang berbeda.",
                    'code' => 'SAME_HUB_ERROR',
                    'origin_hub' => [
                        'id' => $originHubId,
                        'name' => $originHub->name
                    ],
                    'available_destination_hubs' => \App\Models\Hub::where('id', '!=', $originHubId)->get(['id', 'name'])
                ], 422);
            }
            
            // Create shipment - DO NOT duplicate package columns
            // Data will be fetched via: $shipment->package->sender_name, etc.
            $shipment = \App\Models\Shipment::create([
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'tracking_number' => $this->generateTrackingNumber(),
                'origin_hub_id' => $originHubId,
                'destination_hub_id' => $validated['destination_hub_id'],
                'current_hub_id' => $originHubId,
                'status' => 'pending'
            ]);
            
            // Record initial tracking history
            $this->trackingRepo->recordHistory($shipment->id, [
                'status' => 'pending',
                'from_hub_id' => $originHubId,
                'notes' => 'Paket dari gudang telah terdaftar untuk pengiriman'
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Pengiriman berhasil dibuat dari paket!',
                'data' => $shipment->load(['package', 'originHub', 'destinationHub', 'trackingHistories'])
            ], 201);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pengiriman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all shipments for authenticated customer (M3 integration)
     * GET /api/v1/customer/shipments
     * 
     * PROTECTED: Only customer can see their own shipments
     */
    public function customerShipments(Request $request)
    {
        $customer = auth('api')->user();
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            $status = $request->query('status');
            
            $query = \App\Models\Shipment::where('customer_id', $customer->id)
                ->with(['package', 'fleet', 'originHub', 'destinationHub', 'currentHub', 'trackingHistories']);
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $shipments = $query->orderByDesc('created_at')->paginate(15);
            
            return response()->json([
                'status' => 'success',
                'data' => $shipments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pengiriman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific shipment for authenticated customer (M3 integration)
     * GET /api/v1/customer/shipments/{tracking_number}
     * 
     * PROTECTED: Only customer can see their own shipment
     */
    public function customerShipmentDetail(Request $request, $trackingNumber)
    {
        $customer = auth('api')->user();
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            $shipment = \App\Models\Shipment::where('tracking_number', $trackingNumber)
                ->where('customer_id', $customer->id)
                ->with(['package', 'fleet', 'originHub', 'destinationHub', 'currentHub', 'trackingHistories'])
                ->firstOrFail();
            
            return response()->json([
                'status' => 'success',
                'data' => $shipment
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengiriman tidak ditemukan atau bukan milik Anda'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail pengiriman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Generate unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        $prefix = 'TRK';
        $timestamp = microtime(true) * 10000;
        $random = rand(1000, 9999);
        
        return $prefix . substr($timestamp, -10) . $random;
    }
}
