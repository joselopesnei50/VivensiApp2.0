<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappCampaignMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_campaign_id',
        'contact_name',
        'contact_phone',
        'rendered_message',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsappCampaign::class, 'whatsapp_campaign_id');
    }
}
