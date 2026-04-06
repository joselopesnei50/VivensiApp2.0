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
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    public function sections()
    {
        return $this->hasMany(LandingPageSection::class)->orderBy('sort_order');
    }
}
