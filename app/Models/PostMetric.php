<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PostMetric extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'scheduled_post_id', 'tenant_id',
        'likes', 'comments', 'shares', 'reach', 'impressions', 'fetched_at',
    ];

    protected $casts = ['fetched_at' => 'datetime'];

    public function post() { return $this->belongsTo(ScheduledPost::class, 'scheduled_post_id'); }
}
