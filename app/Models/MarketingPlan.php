<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class MarketingPlan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'project_id', 'title', 'objective', 'target_audience',
        'scope', 'competitor_links', 'budget_range', 'tone',
        'has_whatsapp_groups', 'extra_info', 'mindmap_data',
        'ai_provider', 'status',
        // Guia do Bruce (segunda chamada IA)
        'execution_guide', 'guide_status', 'guide_generated_at',
    ];

    protected $casts = [
        'mindmap_data'        => 'array',
        'execution_guide'     => 'array',
        'has_whatsapp_groups' => 'boolean',
        'guide_generated_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
