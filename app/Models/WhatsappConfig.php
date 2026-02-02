<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class WhatsappConfig extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'instance_id',
        'token',
        'client_token',
        'ai_training',
        'is_active',
        'ai_enabled'
    ];

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
