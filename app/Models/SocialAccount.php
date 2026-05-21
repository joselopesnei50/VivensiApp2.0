<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class SocialAccount extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'platform', 'page_id', 'page_name', 'page_picture',
        'access_token', 'token_expires_at', 'instagram_business_id',
        'instagram_username', 'is_active',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function tenant()      { return $this->belongsTo(Tenant::class); }
    public function posts()       { return $this->hasMany(ScheduledPost::class); }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon(): bool
    {
        return $this->token_expires_at
            && $this->token_expires_at->diffInDays(now()) <= 7
            && !$this->isTokenExpired();
    }
}
