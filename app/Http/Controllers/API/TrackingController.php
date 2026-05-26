<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\ShipmentLog;
use App\Repositories\Contracts\ShipmentLogRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    protected ShipmentLogRepositoryInterface $logRepo;

    public function __construct(ShipmentLogRepositoryInterface $logRepo)
    {
        $this->logRepo = $logRepo;
    }

    // ══════════════════════════════════════════════════════════════════
    // PUBLIC ENDPOINTS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Daftar semua paket (dengan log terbaru)
     * GET /api/v1/tracking
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        try {
            $packages = $this->logRepo->getAllPackages($search, $status);

            return response()->json([
                'status' => 'success',
                'total'  => $packages->total(),
                'data'   => $packages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data paket: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail paket + seluruh riwayat log
     * GET /api/v1/tracking/{tracking_number}
     */
    public function show(string $trackingNumber)
    {
        try {
            $package = $this->logRepo->findPackageByTrackingNumber($trackingNumber);

            return response()->json([
                'status' => 'success',
                'data'   => $package,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paket dengan nomor resi ' . $trackingNumber . ' tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * Riwayat status kronologis paket
     * GET /api/v1/tracking/{tracking_number}/history
     */
    public function showHistory(string $trackingNumber)
    {
        try {
            $package = $this->logRepo->findPackageByTrackingNumber($trackingNumber);
            $logs    = $this->logRepo->getLogsByPackage($package->id);

            return response()->json([
                'status'          => 'success',
                'tracking_number' => $package->tracking_number,
                'current_status'  => $package->package_status,
                'package'         => $package->only([
                    'id', 'tracking_number', 'sender_name', 'receiver_name',
                    'origin', 'destination', 'weight', 'package_status',
                ]),
                'history'         => $logs,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paket tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * Pencarian resi
     * GET /api/v1/tracking/search?q={keyword}
     */
    public function search(Request $request)
    {
        $keyword = $request->query('q');

        if (!$keyword) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Masukkan kata kunci pencarian.',
            ], 400);
        }

        try {
            $results = $this->logRepo->searchByTracking($keyword);

            return response()->json([
                'status'  => 'success',
                'keyword' => $keyword,
                'total'   => $results->total(),
                'data'    => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hub tujuan tersedia untuk suatu paket
     * GET /api/v1/package/{id}/available-destination-hubs
     */
    public function availableDestinationHubs(int $packageId)
    {
        try {
            $package = \App\Models\Package::with('warehouse.hub')->findOrFail($packageId);

            $originHubId = $package->warehouse?->hub_id;
            if (!$originHubId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Paket tidak ada di warehouse yang memiliki hub.',
                ], 422);
            }

            $originHub      = Hub::find($originHubId);
            $availableHubs  = Hub::where('id', '!=', $originHubId)
                ->orderBy('name')
                ->get(['id', 'name', 'status', 'capacity', 'current_load']);

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'origin_hub'               => $originHub,
                    'available_destination_hubs' => $availableHubs,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paket tidak ditemukan.',
            ], 404);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // PROTECTED ENDPOINTS (auth:api)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Update lokasi / status paket (INSERT log baru)
     * PATCH /api/v1/tracking/{tracking_number}/status
     *
     * Setiap pemanggilan = 1 baris baru di shipment_logs (append, bukan update)
     * M4 Integration: hub_id + fleet_id opsional
     */
    public function updateStatus(Request $request, string $trackingNumber)
    {
        $validated = $request->validate([
            'status'        => 'required|in:registered,picked_up,in_transit,arrived_at_hub,out_for_delivery,delivered,failed,returned',
            'hub_id'        => 'nullable|exists:hubs,id',
            'fleet_id'      => 'nullable|exists:fleets,id',
            'location_note' => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'recorded_at'   => 'nullable|date',
        ]);

        try {
            $package = $this->logRepo->findPackageByTrackingNumber($trackingNumber);

            // Cegah update setelah status final
            $latestLog = $this->logRepo->getLatestLog($package->id);
            if ($latestLog && in_array($latestLog->status, ShipmentLog::FINAL_STATUSES)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Paket sudah dalam status final: ' . $latestLog->status . '. Tidak bisa diupdate.',
                ], 422);
            }

            $log = $this->logRepo->recordLog($package->id, $validated);

            // Jika tiba di hub: update hub.current_load (M4 integration)
            if ($validated['status'] === 'arrived_at_hub' && !empty($validated['hub_id'])) {
                Hub::where('id', $validated['hub_id'])->increment('current_load');
            }

            // Invalidasi cache
            CacheService::forget(CacheService::keyShipmentByTracking($trackingNumber));
            CacheService::flushTag(CacheService::TAG_SHIPMENT, CacheService::TAG_TRACKING);

            return response()->json([
                'status'  => 'success',
                'message' => 'Status paket berhasil diperbarui!',
                'data'    => [
                    'package_id'     => $package->id,
                    'tracking_number' => $package->tracking_number,
                    'current_status' => $validated['status'],
                    'log'            => $log->load(['hub', 'fleet']),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paket tidak ditemukan.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Daftar semua log paket milik customer yang login
     * GET /api/v1/customer/shipments
     * M3 Integration
     */
    public function customerShipments(Request $request)
    {
        $customer = auth('api')->user();
        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        try {
            // Customer melihat paket yang dikirim ke/dari warehouse mereka
            // Sementara ini tampilkan semua paket (bisa difilter jika ada customer_id di packages)
            $status = $request->query('status');
            $packages = $this->logRepo->getAllPackages(null, $status);

            return response()->json([
                'status' => 'success',
                'data'   => $packages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data pengiriman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail paket untuk customer
     * GET /api/v1/customer/shipments/{tracking_number}
     * M3 Integration
     */
    public function customerShipmentDetail(Request $request, string $trackingNumber)
    {
        $customer = auth('api')->user();
        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        try {
            $package = $this->logRepo->findPackageByTrackingNumber($trackingNumber);

            return response()->json([
                'status' => 'success',
                'data'   => $package,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pengiriman tidak ditemukan.',
            ], 404);
        }
    }

    /**
     * Create shipment log from package (M1 + M3 Integration)
     * POST /api/v1/shipment/from-package/{package_id}
     */
    public function createFromPackage(Request $request, $packageId)
    {
        $customer = auth('api')->user();
        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
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
                    'status'  => 'error',
                    'message' => 'Paket tidak ada di warehouse yang memiliki hub.',
                    'code'    => 'NO_WAREHOUSE_HUB',
                ], 422);
            }

            $originHub = Hub::find($originHubId);
            $destinationHub = Hub::find($validated['destination_hub_id']);

            // Validate destination is different from origin
            if ($originHubId == $validated['destination_hub_id']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Hub tujuan '{$destinationHub->name}' tidak boleh sama dengan hub asal '{$originHub->name}'. Pilih hub tujuan yang berbeda.",
                    'code'    => 'SAME_HUB_ERROR',
                    'origin_hub' => [
                        'id'   => $originHubId,
                        'name' => $originHub->name,
                    ],
                    'available_destination_hubs' => Hub::where('id', '!=', $originHubId)->get(['id', 'name']),
                ], 422);
            }

            // Create initial shipment log (status: registered)
            $log = $this->logRepo->recordLog($package->id, [
                'status'        => 'registered',
                'hub_id'        => $originHubId,
                'location_note' => 'Gudang asal',
                'notes'         => 'Paket dari gudang telah terdaftar untuk pengiriman ke hub ' . $destinationHub->name,
                'recorded_by'   => $customer->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengiriman berhasil dibuat dari paket!',
                'data'    => [
                    'tracking_number' => $package->tracking_number,
                    'log'             => $log->load(['hub', 'fleet']),
                ],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paket tidak ditemukan.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat pengiriman: ' . $e->getMessage(),
            ], 500);
        }
    }
}
