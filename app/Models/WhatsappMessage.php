<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'message_id',
        'content',
        'direction',
        'type'
    ];

    public function chat() {
        return $this->belongsTo(WhatsappChat::class);
    }
}
