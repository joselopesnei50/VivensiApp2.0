<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_id', 'event', 'payload', 'http_status',
        'response_body', 'attempts', 'success', 'fired_at',
    ];

    protected $casts = [
        'payload'   => 'array',
        'success'   => 'boolean',
        'fired_at'  => 'datetime',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
