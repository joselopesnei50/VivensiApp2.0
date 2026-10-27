<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DestineIrPartner extends Model
{
    protected $table = 'destine_ir_partners';

    protected $guarded = [];

    protected $casts = [
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
        'visits_count'  => 'integer',
        'shares_count'  => 'integer',
        'last_visit_at' => 'datetime',
    ];

    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'parceira';
        $slug = $base;
        $n = 1;
        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $n++;
            $slug = $base . '-' . $n;
        }
        return $slug;
    }
}
