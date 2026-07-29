<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ScheduledPost extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'social_account_id', 'user_id', 'platform', 'format',
        'caption', 'media_url', 'media_items', 'media_type', 'scheduled_at',
        'status', 'facebook_post_id', 'instagram_post_id', 'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'media_items'  => 'array',
    ];

    public function account()  { return $this->belongsTo(SocialAccount::class, 'social_account_id'); }
    public function author()   { return $this->belongsTo(User::class, 'user_id'); }
    public function tenant()   { return $this->belongsTo(Tenant::class); }
    // metrics agora é hasMany porque um post pode ter métricas de FB E IG separadas
    // (uma linha por 'source'). Métrica antiga hasOne fica compatível via metric()
    // que retorna a mais recente independente da fonte.
    public function metrics()  { return $this->hasMany(PostMetric::class, 'scheduled_post_id'); }
    public function metric()   { return $this->hasOne(PostMetric::class, 'scheduled_post_id')->latestOfMany('fetched_at'); }

    /**
     * Métricas agregadas de todas as fontes (FB + IG somadas). Útil pra
     * mostrar um único total no card do post publicado em ambos.
     */
    public function metricsTotal(): array
    {
        $rows = $this->metrics;
        return [
            'impressions' => (int) $rows->sum('impressions'),
            'reach'       => (int) $rows->sum('reach'),
            'likes'       => (int) $rows->sum('likes'),
            'comments'    => (int) $rows->sum('comments'),
            'shares'      => (int) $rows->sum('shares'),
            'clicks'      => (int) $rows->sum('clicks'),
            'saved'       => (int) $rows->sum('saved'),
            'engagement'  => (int) $rows->sum('engagement'),
            'sources'     => $rows->pluck('source')->toArray(),
            'fetched_at'  => optional($rows->max('fetched_at'))->toIso8601String(),
        ];
    }

    /**
     * Normaliza mídias em um array de {url, type}. Fonte de verdade única
     * para o publisher: se media_items existe, usa; senão cai em media_url
     * legado. Sempre retorna array (vazio se sem mídia).
     */
    public function mediaList(): array
    {
        $items = $this->media_items;
        if (is_array($items) && !empty($items)) {
            return array_values(array_filter($items, fn ($it) => !empty($it['url'])));
        }
        if ($this->media_url) {
            return [[
                'url'  => $this->media_url,
                'type' => $this->media_type ?: 'image',
            ]];
        }
        return [];
    }

    /**
     * Post publicará como carrossel? (2+ mídias no media_items)
     */
    public function isCarousel(): bool
    {
        return count($this->mediaList()) >= 2;
    }
}
