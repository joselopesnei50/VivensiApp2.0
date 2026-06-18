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
        'message_id',
        'content',
        'direction',
        'type',
        'status',
        'media_path',
        'media_caption',
        'transcription',
    ];

    public function chat() {
        return $this->belongsTo(WhatsappChat::class);
    }
}
