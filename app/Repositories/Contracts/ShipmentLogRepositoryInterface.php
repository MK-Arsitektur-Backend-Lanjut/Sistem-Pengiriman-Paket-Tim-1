<?php

namespace App\Repositories\Contracts;

interface ShipmentLogRepositoryInterface
{
    /**
     * Ambil semua log untuk satu paket (kronologis ASC)
     */
    public function getLogsByPackage(int $packageId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Ambil log terbaru (status terakhir) suatu paket
     */
    public function getLatestLog(int $packageId): ?\App\Models\ShipmentLog;

    /**
     * Catat kejadian baru dalam perjalanan paket
     */
    public function recordLog(int $packageId, array $data): \App\Models\ShipmentLog;

    /**
     * Cari paket berdasarkan tracking number (via packages table)
     */
    public function findPackageByTrackingNumber(string $trackingNumber): \App\Models\Package;

    /**
     * Daftar semua paket + log terbaru, dengan filter opsional
     */
    public function getAllPackages(?string $search = null, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Pencarian paket berdasarkan nomor resi atau nama
     */
    public function searchByTracking(string $keyword): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
