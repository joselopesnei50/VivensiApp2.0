<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadConsent extends Model
{
    use HasFactory, BelongsToTenant;

    public const TYPE_OPT_IN        = 'opt_in';
    public const TYPE_DOUBLE_OPT_IN = 'double_opt_in';
    public const TYPE_OPT_OUT       = 'opt_out';
    public const TYPE_PREFERENCE    = 'preference_update';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'type',
        'origin',
        'ip_address',
        'user_agent',
        'payload',
        'recorded_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'recorded_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
