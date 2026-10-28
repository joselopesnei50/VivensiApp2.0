<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestineIrMeetingSignup extends Model
{
    protected $table = 'destine_ir_meeting_signups';

    protected $guarded = [];

    protected $casts = [
        'partner_id'   => 'integer',
        'lgpd_consent' => 'boolean',
        'notified_at'  => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(DestineIrPartner::class, 'partner_id');
    }
}
