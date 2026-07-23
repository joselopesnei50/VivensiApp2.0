<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadarFinding extends Model
{
    protected $fillable = [
        'source',
        'territory_ibge',
        'dedupe_hash',
        'title',
        'excerpt',
        'source_url',
        'published_at',
        'keyword_matched',
        'raw_payload',
        'status',
        'curated_by',
        'curated_at',
        'areas',
        'object_summary',
        'deadline',
        'value_total',
        'ai_processed_at',
        'is_relevant',
        'auto_approved',
    ];

    protected $casts = [
        'published_at'    => 'date',
        'raw_payload'     => 'array',
        'areas'           => 'array',
        'curated_at'      => 'datetime',
        'ai_processed_at' => 'datetime',
        'deadline'        => 'date',
        'value_total'     => 'decimal:2',
        'is_relevant'     => 'boolean',
        'auto_approved'   => 'boolean',
    ];

    public function curator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'curated_by');
    }

    public function matches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RadarMatch::class);
    }

    public function isNovo(): bool     { return $this->status === 'novo'; }
    public function isAprovado(): bool { return $this->status === 'aprovado'; }
    public function isRejeitado(): bool{ return $this->status === 'rejeitado'; }

    public static function buildDedupeHash(string $source, string $sourceUrl, string $publishedAt, string $excerpt): string
    {
        return hash('sha256', implode('|', [$source, $sourceUrl, $publishedAt, trim($excerpt)]));
    }
}
