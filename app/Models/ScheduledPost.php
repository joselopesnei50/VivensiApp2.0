<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ScheduledPost extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'social_account_id', 'user_id', 'platform',
        'caption', 'media_url', 'media_type', 'scheduled_at',
        'status', 'facebook_post_id', 'instagram_post_id', 'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function account()  { return $this->belongsTo(SocialAccount::class, 'social_account_id'); }
    public function author()   { return $this->belongsTo(User::class, 'user_id'); }
    public function tenant()   { return $this->belongsTo(Tenant::class); }
    public function metrics()  { return $this->hasOne(PostMetric::class); }
}
