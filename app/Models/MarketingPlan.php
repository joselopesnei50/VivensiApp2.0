<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class MarketingPlan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'title', 'objective', 'target_audience',
        'scope', 'competitor_links', 'budget_range', 'tone',
        'has_whatsapp_groups', 'extra_info', 'mindmap_data',
        'ai_provider', 'status',
    ];

    protected $casts = [
        'mindmap_data'       => 'array',
        'has_whatsapp_groups' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
