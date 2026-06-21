<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadDoubleOptInToken extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'token',
        'sent_at',
        'expires_at',
        'confirmed_at',
        'opted_out_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'expires_at'   => 'datetime',
        'confirmed_at' => 'datetime',
        'opted_out_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->confirmed_at !== null || $this->opted_out_at !== null;
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->isConsumed();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('confirmed_at')
            ->whereNull('opted_out_at')
            ->where('expires_at', '>', now());
    }
}
