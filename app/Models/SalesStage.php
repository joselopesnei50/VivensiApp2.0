<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesStage extends Model
{
    protected $fillable = ['name', 'color', 'position', 'is_won', 'is_lost'];

    protected $casts = [
        'is_won'  => 'boolean',
        'is_lost' => 'boolean',
    ];

    public function leads()
    {
        return $this->hasMany(SalesLead::class, 'stage_id')->orderBy('position');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
