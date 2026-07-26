<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Prospect extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_name',
        'category',
        'address',
        'website',
        'phone',
        'email',           // e-mail encontrado (scraping ou manual)
        'email_opt_in',    // consentimento LGPD para envio (default false)
        'email_source',    // 'scraped' | 'manual' | 'hunter'
        'email_found_at',  // timestamp da última descoberta/edição
        'google_rating',
        'total_reviews',
        'lead_score',
        'ai_analysis',
        'personalized_pitch',
        'status',
        'source',   // 'maps' | 'web'
        'snippet',  // trecho descritivo da busca web
    ];

    protected $casts = [
        'email_opt_in'   => 'boolean',
        'email_found_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
