<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'warehouse_name',
        'location',
        'capacity',
        'current_load',
        'status',
        'hub_id',          // FK ke Modul 4 (Hub) — integrasi antar modul
    ];

    protected $casts = [
        'capacity'     => 'integer',
        'current_load' => 'integer',
        'hub_id'       => 'integer',
    ];

    /**
     * Paket-paket yang disimpan di gudang ini (Modul 1).
     */
    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Hub logistik yang menaungi gudang ini (Modul 4).
     * Ketika paket masuk/keluar gudang, hub juga ikut ter-update.
     */
    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    /**
     * Recalculate current_load and update status based on package count.
     * Called after bulk package inserts (seeder).
     */
    public function recalculateLoad(): void
    {
        $count      = $this->packages()->count();
        $percentage = $this->capacity > 0 ? ($count / $this->capacity) * 100 : 0;

        $status = 'available';
        if ($percentage >= 100) {
            $status = 'overload';
        } elseif ($percentage >= 90) {
            $status = 'full';
        }

        $this->update([
            'current_load' => $count,
            'status'       => $status,
        ]);
    }
}