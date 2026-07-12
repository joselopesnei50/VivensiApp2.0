<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class WhatsappMessage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'chat_id',
        'whatsapp_conversation_id',
        'message_id',
        'content',
        'direction',
        'type',
        'status',
        'meta_pricing_category',
        'media_path',
        'media_caption',
        'transcription',
    ];

    public function chat() {
        return $this->belongsTo(WhatsappChat::class);
    }

    public function conversation() {
        return $this->belongsTo(WhatsappConversation::class, 'whatsapp_conversation_id');
    }
}
