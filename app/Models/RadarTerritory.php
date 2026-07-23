<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadarTerritory extends Model
{
    protected $fillable = [
        'ibge_code',
        'name',
        'uf',
        'active',
        'last_collected_at',
    ];

    protected $casts = [
        'active'            => 'boolean',
        'last_collected_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
