<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class WhatsappConfig extends Model
{
    use HasFactory, BelongsToTenant;

    protected $casts = [
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
        'outbound_enabled' => 'boolean',
        'require_opt_in' => 'boolean',
        'enforce_24h_window' => 'boolean',
        'allow_templates_outside_window' => 'boolean',
        'max_outbound_per_minute' => 'integer',
        'min_outbound_delay_seconds' => 'integer',
    ];

    protected $fillable = [
        'tenant_id',
        'instance_id',
        'token',
        'client_token',
        'client_token_hash',
        'ai_training',
        'is_active',
        'ai_enabled',
        'outbound_enabled',
        'require_opt_in',
        'max_outbound_per_minute',
        'min_outbound_delay_seconds',
        'enforce_24h_window',
        'allow_templates_outside_window',
    ];

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
