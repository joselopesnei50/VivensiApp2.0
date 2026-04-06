<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class WhatsappAuditLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_audit_logs';

    protected $fillable = [
        'tenant_id',
        'chat_id',
        'actor_user_id',
        'actor_type',
        'event',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}

