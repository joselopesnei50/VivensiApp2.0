<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class WhatsappChat extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'wa_id', // ex: 558199999999@c.us
        'contact_name',
        'contact_phone',
        'status',
        'assigned_to',
        'last_message_at'
    ];

    public function messages() {
        return $this->hasMany(WhatsappMessage::class, 'chat_id');
    }

    public function agent() {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
