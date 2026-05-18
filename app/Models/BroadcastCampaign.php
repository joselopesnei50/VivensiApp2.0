<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class BroadcastCampaign extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'name', 'message', 'has_image', 'image_path',
        'audience_type', 'status', 'scheduled_at', 'cadence',
        'group_ids', 'group_send_mode', 'phones', 'total_sent', 'total_failed',
    ];

    protected $casts = [
        'has_image'    => 'boolean',
        'group_ids'    => 'array',
        'scheduled_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];
}
