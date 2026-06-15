<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hub extends Model
{
    /** @use HasFactory<\Database\Factories\HubFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'current_load',
        'status'
    ];

    /**
     * Armada yang sedang berada di hub ini (Modul 4).
     */
    public function fleets()
    {
        return $this->hasMany(Fleet::class, 'current_hub_id');
    }

    /**
     * Paket-paket yang ditujukan ke hub ini (Modul 2).
     */
    public function packages()
    {
        return $this->hasMany(Package::class, 'hub_id');
    }

    /**
     * Gudang-gudang fisik yang berada di bawah hub ini (Modul 1 ↔ Modul 4).
     * Integrasi: paket masuk gudang → current_load hub bertambah.
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
