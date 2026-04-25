<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastCampaign extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'message', 'has_image',
        'audience_type', 'total_sent', 'total_failed',
    ];

    protected $casts = [
        'has_image'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
