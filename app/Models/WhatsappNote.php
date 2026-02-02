<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNote extends Model
{
    use HasFactory;

    protected $fillable = ['chat_id', 'user_id', 'content', 'type'];

    public function chat()
    {
        return $this->belongsTo(WhatsappChat::class, 'chat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
