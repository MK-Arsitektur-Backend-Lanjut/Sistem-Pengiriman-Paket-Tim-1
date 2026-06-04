<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageHistory extends Model
{
    use HasFactory;

    protected $table = 'package_histories';

    protected $fillable = [
        'package_id',
        'status',
        'hub_id',
        'fleet_id',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

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
