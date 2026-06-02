<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Asset extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'acquisition_date',
        'value',
        'useful_life_years',
        'residual_value',
        'status',
        'location',
        'responsible',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'value'            => 'decimal:2',
        'residual_value'   => 'decimal:2',
    ];

    public function getDepreciationAttribute(): array
    {
        if (!$this->useful_life_years || !$this->value || !$this->acquisition_date) {
            return ['book_value' => null, 'accumulated' => null, 'annual' => null, 'percent' => 0];
        }
        $cost        = (float) $this->value;
        $residual    = (float) ($this->residual_value ?? 0);
        $depreciable = $cost - $residual;
        if ($depreciable <= 0 || $this->useful_life_years <= 0) {
            return ['book_value' => $cost, 'accumulated' => 0.0, 'annual' => 0.0, 'percent' => 0];
        }
        $annual      = $depreciable / $this->useful_life_years;
        $yearsElapsed = $this->acquisition_date->diffInDays(now()) / 365.25;
        $accumulated  = min($depreciable, $annual * $yearsElapsed);
        $bookValue    = max($residual, $cost - $accumulated);
        $percent      = min(100, round($accumulated / $depreciable * 100, 1));
        return [
            'book_value'  => $bookValue,
            'accumulated' => $accumulated,
            'annual'      => $annual,
            'percent'     => $percent,
        ];
    }
}
