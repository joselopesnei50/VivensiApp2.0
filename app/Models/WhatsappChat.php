<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class WhatsappChat extends Model
{
    use HasFactory, BelongsToTenant;

    protected $casts = [
        'last_message_at'  => 'datetime',
        'last_inbound_at'  => 'datetime',
        'last_outbound_at' => 'datetime',
        'last_read_at'     => 'datetime',
        'opt_in_at'        => 'datetime',
        'opt_out_at'       => 'datetime',
        'blocked_at'       => 'datetime',
        'labels'           => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'wa_id',
        'contact_name',
        'contact_phone',
        'status',
        'labels',
        'assigned_to',
        'last_message_at',
        'last_inbound_at',
        'last_outbound_at',
        'last_read_at',
        'opt_in_at',
        'opt_out_at',
        'blocked_at',
        'blocked_reason',
        'is_bot_active',
    ];

    public function messages() {
        return $this->hasMany(WhatsappMessage::class, 'chat_id');
    }

    public function agent() {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
