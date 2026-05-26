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
        'package_status'
    ];

    protected $casts = [
        'weight'       => 'float',
        'length'       => 'float',
        'width'        => 'float',
        'height'       => 'float',
        'volume'       => 'float',
        'warehouse_id' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * M2 Integration: Semua log perjalanan paket (kronologis)
     */
    public function shipmentLogs()
    {
        return $this->hasMany(ShipmentLog::class)->orderBy('recorded_at');
    }

    /**
     * M2 Integration: Log terbaru (status terakhir paket)
     */
    public function latestLog()
    {
        return $this->hasOne(ShipmentLog::class)->latestOfMany('recorded_at');
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
}