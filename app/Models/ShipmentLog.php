<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentLog extends Model
{
    use HasFactory;

    protected $table = 'shipment_logs';

    protected $fillable = [
        'package_id',       // M1: paket yang dilacak (WAJIB)
        'status',           // status kejadian
        'hub_id',           // M4: hub tempat kejadian (opsional)
        'fleet_id',         // M4: armada yang mengantar (opsional)
        'location_note',    // deskripsi lokasi bebas
        'notes',            // catatan tambahan
        'recorded_at',      // waktu kejadian (bisa berbeda dari created_at)
        'recorded_by',      // M3: user/petugas yang mencatat
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ── Status Constants ──────────────────────────────────────────────

    const STATUS_REGISTERED       = 'registered';
    const STATUS_PICKED_UP        = 'picked_up';
    const STATUS_IN_TRANSIT       = 'in_transit';
    const STATUS_ARRIVED_AT_HUB   = 'arrived_at_hub';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED        = 'delivered';
    const STATUS_FAILED           = 'failed';
    const STATUS_RETURNED         = 'returned';

    const FINAL_STATUSES = ['delivered', 'failed', 'returned'];

    // ── Relationships ─────────────────────────────────────────────────

    /**
     * M1 Integration: Paket yang dilog
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * M4 Integration: Hub tempat kejadian terjadi
     */
    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    /**
     * M4 Integration: Armada yang mengantar
     */
    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    /**
     * M3 Integration: Petugas yang mencatat log ini
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isFinalStatus(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'registered'       => 'Paket Terdaftar',
            'picked_up'        => 'Dijemput Armada',
            'in_transit'       => 'Dalam Perjalanan',
            'arrived_at_hub'   => 'Tiba di Hub Transit',
            'out_for_delivery' => 'Sedang Diantar',
            'delivered'        => 'Terkirim',
            'failed'           => 'Gagal Kirim',
            'returned'         => 'Dikembalikan',
            default            => ucfirst($this->status),
        };
    }
}
