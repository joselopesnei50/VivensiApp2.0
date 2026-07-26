<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiSocialPost extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'user_id', 'title_theme', 'user_context', 'body_text',
        'image_path', 'image_url', 'status', 'scheduled_at', 'error_message',
        // Rota 2 (2026-07-26):
        'reference_image_path',     // imagem enviada pelo user
        'use_reference_as_final',   // true = referência vira imagem final do post
        'format',                   // 'square' | 'story'
        'visual_style',             // photorealistic | illustration | cartoon | corporate | minimalist | watercolor
        'caption_variations',       // JSON: até 3 opções de legenda geradas
    ];

    protected $casts = [
        'scheduled_at'           => 'datetime',
        'use_reference_as_final' => 'boolean',
        'caption_variations'     => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
