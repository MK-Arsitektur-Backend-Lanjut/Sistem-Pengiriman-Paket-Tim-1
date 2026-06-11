<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'tracking_number',
        'sender_name',
        'receiver_name',
        'origin',
        'destination',
        'weight',
        'length',
        'width',
        'height',
        'volume',
        'warehouse_id',
        'hub_id',
        'fleet_id',
        'package_status'
    ];

    protected $casts = [
        'weight'       => 'float',
        'length'       => 'float',
        'width'        => 'float',
        'height'       => 'float',
        'volume'       => 'float',
        'warehouse_id' => 'integer',
        'hub_id'       => 'integer',
        'fleet_id'     => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    public function histories()
    {
        return $this->hasMany(PackageHistory::class, 'package_id')->orderBy('recorded_at');
    }

    public function latestLog()
    {
        return $this->hasOne(PackageHistory::class, 'package_id')->latestOfMany('recorded_at');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function getDimensionCategory(): string
    {
        if ($this->volume <= 1000) {
            return 'small';
        }

        if ($this->volume <= 5000) {
            return 'medium';
        }

        return 'large';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->package_status) {
            'registered'       => 'Paket Terdaftar',
            'picked_up'        => 'Dijemput Armada',
            'in_transit'       => 'Dalam Perjalanan',
            'arrived_at_hub'   => 'Tiba di Hub Transit',
            'out_for_delivery' => 'Sedang Diantar',
            'delivered'        => 'Terkirim',
            'failed'           => 'Gagal Kirim',
            'returned'         => 'Dikembalikan',
            default            => ucfirst($this->package_status),
        };
    }
}