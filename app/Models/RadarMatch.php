<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadarMatch extends Model
{
    protected $fillable = [
        'tenant_id',
        'radar_finding_id',
        'score',
        'score_reasons',
    ];

    protected $casts = [
        'score'         => 'integer',
        'score_reasons' => 'array',
    ];

    public function finding(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RadarFinding::class, 'radar_finding_id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
