<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService - Centralized Redis caching utility.
 *
 * Semua interaksi dengan Redis cache dilakukan melalui service ini
 * agar mudah di-monitor, di-invalidate, dan di-debug.
 */
class CacheService
{
    // ── TTL Constants (seconds) ──────────────────────────────────────
    public const TTL_SHORT   = 60;        // 1 menit  – data yang sering berubah
    public const TTL_MEDIUM  = 300;       // 5 menit  – data semi-statis
    public const TTL_LONG    = 1800;      // 30 menit – data statis
    public const TTL_FOREVER = 86400;     // 1 hari   – master data

    // ── Tag Groups ───────────────────────────────────────────────────
    public const TAG_SHIPMENT  = 'shipments';
    public const TAG_PACKAGE   = 'packages';
    public const TAG_FLEET     = 'fleets';
    public const TAG_HUB       = 'hubs';
    public const TAG_WAREHOUSE = 'warehouses';
    public const TAG_TRACKING  = 'tracking';
    public const TAG_STATS     = 'stats';

    /**
     * Get atau simpan data ke cache dengan tag.
     *
     * @param  string    $key
     * @param  callable  $callback
     * @param  int       $ttl
     * @param  array     $tags
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = self::TTL_MEDIUM, array $tags = []): mixed
    {
        try {
            if (!empty($tags) && self::supportsTagging()) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            }

            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache read failed, falling back to DB: ' . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Hapus cache berdasarkan tag.
     */
    public static function flushTag(string ...$tags): void
    {
        if (!self::supportsTagging()) {
            return;
        }

        try {
            Cache::tags($tags)->flush();
        } catch (\Exception $e) {
            Log::warning('Cache flush failed: ' . $e->getMessage());
        }
    }

    /**
     * Hapus cache berdasarkan key spesifik.
     */
    public static function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed: ' . $e->getMessage());
        }
    }

    /**
     * Cek apakah driver cache mendukung tagging (Redis / Memcached).
     */
    public static function supportsTagging(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached']);
    }

    // ── Key Builders ─────────────────────────────────────────────────

    public static function keyShipmentList(string $search = '', string $status = ''): string
    {
        return 'shipments:list:' . md5($search . '|' . $status);
    }

    public static function keyShipmentByTracking(string $trackingNumber): string
    {
        return 'shipments:tracking:' . $trackingNumber;
    }

    public static function keyShipmentById(int $id): string
    {
        return 'shipments:id:' . $id;
    }

    public static function keyPackageList(): string
    {
        return 'packages:list:all';
    }

    public static function keyPackageById(int $id): string
    {
        return 'packages:id:' . $id;
    }

    public static function keyHubList(string $search = ''): string
    {
        return 'hubs:list:' . md5($search);
    }

    public static function keyWarehouseStats(): string
    {
        return 'warehouses:stats';
    }

    public static function keyWarehouseList(): string
    {
        return 'warehouses:list:all';
    }

    public static function keyFleetList(string $search = '', string $status = '', string $hubId = ''): string
    {
        return 'fleets:list:' . md5($search . '|' . $status . '|' . $hubId);
    }

    public static function keyDashboardStats(): string
    {
        return 'stats:dashboard';
    }

    public static function keyCustomerShipments(int $customerId, string $status = ''): string
    {
        return 'shipments:customer:' . $customerId . ':' . $status;
    }
}
