<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ProjectHealthHistory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'project_id',
        'tenant_id',
        'financial_score',
        'execution_score',
        'team_score',
        'compliance_score',
        'overall_score',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
