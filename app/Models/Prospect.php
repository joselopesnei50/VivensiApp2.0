<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'company_name',
        'category',
        'address',
        'website',
        'phone',
        'google_rating',
        'total_reviews',
        'lead_score',
        'ai_analysis',
        'personalized_pitch',
        'status',
        'source',   // 'maps' | 'web'
        'snippet',  // trecho descritivo da busca web
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
