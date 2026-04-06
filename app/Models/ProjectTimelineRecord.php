<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class ProjectTimelineRecord extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'project_id',
        'tenant_id',
        'title',
        'content',
        'type',
        'media_path',
        'external_url',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
