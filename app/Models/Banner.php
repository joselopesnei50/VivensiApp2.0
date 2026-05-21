<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Banner extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'title', 'format',
        'width', 'height', 'settings', 'thumbnail', 'scheduled_post_id',
        'fabric_json', 'png_path',
    ];

    protected $casts = [
        'settings' => 'array',
        'width'    => 'integer',
        'height'   => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sections()
    {
        return $this->hasMany(BannerSection::class)->orderBy('sort_order');
    }

    public function scheduledPost()
    {
        return $this->belongsTo(ScheduledPost::class);
    }

    /** Formatos disponíveis com dimensões */
    public static function formats(): array
    {
        return [
            'facebook_post'    => ['label' => 'Facebook Post',       'icon' => 'fab fa-facebook',  'w' => 1200, 'h' => 628],
            'instagram_square' => ['label' => 'Instagram Quadrado',  'icon' => 'fab fa-instagram', 'w' => 1080, 'h' => 1080],
            'instagram_story'  => ['label' => 'Story / Reels',       'icon' => 'fab fa-instagram', 'w' => 1080, 'h' => 1920],
            'linkedin_post'    => ['label' => 'LinkedIn Post',       'icon' => 'fab fa-linkedin',  'w' => 1200, 'h' => 627],
            'twitter_post'     => ['label' => 'Twitter / X',         'icon' => 'fab fa-x-twitter', 'w' => 1200, 'h' => 675],
            'youtube_cover'    => ['label' => 'Capa YouTube/Evento', 'icon' => 'fab fa-youtube',   'w' => 1920, 'h' => 1080],
            'email_header'     => ['label' => 'E-mail Header',       'icon' => 'fas fa-envelope',  'w' => 600,  'h' => 200],
            'custom'           => ['label' => 'Personalizado',       'icon' => 'fas fa-crop-alt',  'w' => 1200, 'h' => 628],
        ];
    }
}
