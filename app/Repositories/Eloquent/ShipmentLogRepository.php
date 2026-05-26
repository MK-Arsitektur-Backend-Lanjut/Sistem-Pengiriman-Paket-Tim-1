<?php

namespace App\Repositories\Eloquent;

use App\Models\Package;
use App\Models\ShipmentLog;
use App\Repositories\Contracts\ShipmentLogRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShipmentLogRepository implements ShipmentLogRepositoryInterface
{
    protected Package $packageModel;
    protected ShipmentLog $logModel;

    public function __construct(Package $packageModel, ShipmentLog $logModel)
    {
        $this->packageModel = $packageModel;
        $this->logModel     = $logModel;
    }

    /**
     * Ambil semua log untuk satu paket (kronologis ASC)
     */
    public function getLogsByPackage(int $packageId): Collection
    {
        $cacheKey = CacheService::keyShipmentById($packageId) . ':logs';

        return CacheService::remember(
            $cacheKey,
            fn () => $this->logModel
                ->where('package_id', $packageId)
                ->with(['hub', 'fleet', 'recorder'])
                ->orderBy('recorded_at')
                ->get(),
            CacheService::TTL_SHORT,
            [CacheService::TAG_TRACKING]
        );
    }

    /**
     * Ambil log terbaru (status terakhir) suatu paket
     */
    public function getLatestLog(int $packageId): ?ShipmentLog
    {
        return $this->logModel
            ->where('package_id', $packageId)
            ->with(['hub', 'fleet'])
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Catat kejadian baru dalam perjalanan paket (INSERT log)
     * Sekaligus update packages.package_status
     */
    public function recordLog(int $packageId, array $data): ShipmentLog
    {
        $data['package_id']  = $packageId;
        $data['recorded_at'] = $data['recorded_at'] ?? now();

        $log = $this->logModel->create($data);

        // Sync package_status ke status log terbaru
        $this->packageModel->where('id', $packageId)
            ->update(['package_status' => $data['status']]);

        // Invalidasi cache terkait paket ini
        CacheService::forget(CacheService::keyShipmentById($packageId) . ':logs');
        CacheService::flushTag(CacheService::TAG_TRACKING, CacheService::TAG_SHIPMENT);

        return $log;
    }

    /**
     * Cari paket berdasarkan tracking number
     */
    public function findPackageByTrackingNumber(string $trackingNumber): Package
    {
        $cacheKey = CacheService::keyShipmentByTracking($trackingNumber);

        return CacheService::remember(
            $cacheKey,
            fn () => $this->packageModel
                ->with(['warehouse.hub', 'shipmentLogs.hub', 'shipmentLogs.fleet', 'latestLog'])
                ->where('tracking_number', $trackingNumber)
                ->firstOrFail(),
            CacheService::TTL_SHORT,
            [CacheService::TAG_SHIPMENT, CacheService::TAG_TRACKING]
        );
    }

    /**
     * Daftar semua paket dengan filter status & search
     */
    public function getAllPackages(?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = $this->packageModel
            ->with(['warehouse', 'latestLog'])
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%$search%")
                  ->orWhere('sender_name', 'like', "%$search%")
                  ->orWhere('receiver_name', 'like', "%$search%")
                  ->orWhere('origin', 'like', "%$search%")
                  ->orWhere('destination', 'like', "%$search%");
            });
        }

        if ($status) {
            $query->where('package_status', $status);
        }

        return $query->paginate(15);
    }

    /**
     * Pencarian paket berdasarkan nomor resi atau nama
     */
    public function searchByTracking(string $keyword): LengthAwarePaginator
    {
        return $this->packageModel
            ->with(['warehouse', 'latestLog'])
            ->where('tracking_number', 'like', "%$keyword%")
            ->orWhere('sender_name', 'like', "%$keyword%")
            ->orWhere('receiver_name', 'like', "%$keyword%")
            ->orWhere('origin', 'like', "%$keyword%")
            ->orWhere('destination', 'like', "%$keyword%")
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
