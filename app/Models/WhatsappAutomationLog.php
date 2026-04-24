<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAutomationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'automation_id', 'tenant_id', 'contact_phone',
        'contact_name', 'message_sent', 'status', 'error_message', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function automation()
    {
        return $this->belongsTo(WhatsappAutomation::class, 'automation_id');
    }
}
