<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class LandingPage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'status',
        'settings',
        'target_project_id',
        'target_creates_person',
        'target_link_beneficiary',
    ];

    protected $casts = [
        'settings'                => 'array',
        'target_creates_person'   => 'boolean',
        'target_link_beneficiary' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(LandingPageSection::class)->orderBy('sort_order');
    }

    public function targetProject()
    {
        return $this->belongsTo(Project::class, 'target_project_id');
    }
}
