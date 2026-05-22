<?php

namespace App\Repositories\Eloquent;

use App\Models\Shipment;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use App\Services\CacheService;

class ShipmentRepository implements ShipmentRepositoryInterface
{
    protected $model;

    public function __construct(Shipment $model)
    {
        $this->model = $model;
    }

    public function getAllShipments($search = null, $status = null)
    {
        // Untuk paginated data + search, cache per kombinasi parameter
        $cacheKey = CacheService::keyShipmentList((string) $search, (string) $status);

        // Catatan: paginator tidak bisa di-cache langsung jika request page berubah.
        // Gunakan caching hanya saat tidak ada search & status (list utama).
        if (!$search && !$status) {
            return CacheService::remember(
                $cacheKey,
                fn () => $this->buildShipmentQuery(null, null)->orderByDesc('created_at')->paginate(15),
                CacheService::TTL_SHORT,
                [CacheService::TAG_SHIPMENT]
            );
        }

        return $this->buildShipmentQuery($search, $status)->orderByDesc('created_at')->paginate(15);
    }

    public function getShipmentById($id)
    {
        $cacheKey = CacheService::keyShipmentById((int) $id);

        return CacheService::remember(
            $cacheKey,
            fn () => $this->model
                ->with(['customer', 'package', 'fleet', 'originHub', 'destinationHub', 'currentHub', 'trackingHistories'])
                ->findOrFail($id),
            CacheService::TTL_SHORT,
            [CacheService::TAG_SHIPMENT]
        );
    }

    public function getShipmentByTrackingNumber($trackingNumber)
    {
        $cacheKey = CacheService::keyShipmentByTracking($trackingNumber);

        return CacheService::remember(
            $cacheKey,
            fn () => $this->model
                ->with(['customer', 'package', 'fleet', 'originHub', 'destinationHub', 'currentHub', 'trackingHistories'])
                ->where('tracking_number', $trackingNumber)
                ->firstOrFail(),
            CacheService::TTL_SHORT,
            [CacheService::TAG_SHIPMENT, CacheService::TAG_TRACKING]
        );
    }

    public function searchShipment($keyword)
    {
        // Search selalu query langsung, tidak di-cache
        return $this->model
            ->with(['customer', 'package', 'fleet', 'originHub', 'destinationHub', 'currentHub', 'trackingHistories'])
            ->where('tracking_number', 'like', "%$keyword%")
            ->orWhereHas('package', function ($q) use ($keyword) {
                $q->where('sender_name', 'like', "%$keyword%")
                  ->orWhere('receiver_name', 'like', "%$keyword%")
                  ->orWhere('origin', 'like', "%$keyword%")
                  ->orWhere('destination', 'like', "%$keyword%");
            })
            ->orWhereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                  ->orWhere('email', 'like', "%$keyword%");
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function createShipment(array $data)
    {
        $data['tracking_number'] = $this->generateTrackingNumber();
        $data['status'] = 'pending';

        $shipment = $this->model->create($data);

        // Invalidasi cache list setelah insert
        CacheService::flushTag(CacheService::TAG_SHIPMENT);

        return $shipment;
    }

    public function updateShipmentStatus($id, $status)
    {
        $shipment = $this->model->findOrFail($id);
        $shipment->update(['status' => $status]);

        if ($status === 'delivered') {
            $shipment->update(['delivered_at' => now()]);
        }

        if ($status === 'in_transit') {
            $shipment->update(['sent_at' => now()]);
        }

        // Invalidasi cache shipment ini + list
        CacheService::forget(CacheService::keyShipmentById($id));
        CacheService::forget(CacheService::keyShipmentByTracking($shipment->tracking_number));
        CacheService::flushTag(CacheService::TAG_SHIPMENT, CacheService::TAG_TRACKING);

        return $shipment;
    }

    public function getShipmentHistory($shipmentId)
    {
        return $this->model->findOrFail($shipmentId)
            ->trackingHistories()
            ->orderByDesc('recorded_at')
            ->get();
    }

    // ── Private Helpers ───────────────────────────────────────────────

    private function buildShipmentQuery($search, $status)
    {
        $query = $this->model->with([
            'customer',
            'package',
            'fleet',
            'originHub',
            'destinationHub',
            'currentHub',
            'trackingHistories',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%$search%")
                  ->orWhereHas('package', function ($q2) use ($search) {
                      $q2->where('sender_name', 'like', "%$search%")
                         ->orWhere('receiver_name', 'like', "%$search%")
                         ->orWhere('origin', 'like', "%$search%")
                         ->orWhere('destination', 'like', "%$search%");
                  })
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%")
                         ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    private function generateTrackingNumber(): string
    {
        $prefix    = 'TRK';
        $timestamp = microtime(true) * 10000;
        $random    = rand(1000, 9999);

        return $prefix . substr((string) $timestamp, -10) . $random;
    }
}
