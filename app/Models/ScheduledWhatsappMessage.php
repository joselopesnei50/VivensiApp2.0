<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ScheduledWhatsappMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'chat_id',
        'content',
        'scheduled_at',
        'sent_at',
        'status',
        'created_by',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function chat()
    {
        return $this->belongsTo(WhatsappChat::class, 'chat_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
