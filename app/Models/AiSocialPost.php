<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSocialPost extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'title_theme', 'body_text', 'image_path', 'image_url', 'status', 'scheduled_at', 'error_message'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
