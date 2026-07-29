<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PostMetric extends Model
{
    use BelongsToTenant;

    public const SOURCE_FACEBOOK  = 'facebook';
    public const SOURCE_INSTAGRAM = 'instagram';

    protected $fillable = [
        'scheduled_post_id', 'tenant_id', 'source',
        'likes', 'comments', 'shares', 'reach', 'impressions',
        'clicks', 'saved', 'engagement',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at'  => 'datetime',
        'likes'       => 'integer',
        'comments'    => 'integer',
        'shares'      => 'integer',
        'reach'       => 'integer',
        'impressions' => 'integer',
        'clicks'      => 'integer',
        'saved'       => 'integer',
        'engagement'  => 'integer',
    ];

    public function post() { return $this->belongsTo(ScheduledPost::class, 'scheduled_post_id'); }
}
