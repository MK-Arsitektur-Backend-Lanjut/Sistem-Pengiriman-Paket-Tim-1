<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    /** @use HasFactory<\Database\Factories\FleetFactory> */
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'type',
        'capacity',
        'status',
        'current_hub_id'
    ];

    public function currentHub()
    {
        return $this->belongsTo(Hub::class, 'current_hub_id');
    }

    public function logs()
    {
        return $this->hasMany(FleetLog::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class, 'fleet_id');
    }
}
