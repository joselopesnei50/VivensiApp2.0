<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStage extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'project_stages';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'planned_value',
        'order',
        'target_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'target_date'   => 'date',
        'completed_at'  => 'datetime',
        'planned_value' => 'decimal:2',
        'order'         => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'stage_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'stage_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getIsCurrentAttribute(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }
        $today = now()->startOfDay();
        $start = $this->start_date?->startOfDay();
        $end   = $this->end_date?->endOfDay();
        if ($start && $today->lt($start)) return false;
        if ($end && $today->gt($end))     return false;
        return true;
    }

    public function getProgressPercentAttribute(): ?int
    {
        $total = $this->tasks()->count();
        if ($total === 0) {
            return null;
        }
        $done = $this->tasks()->where('status', 'completed')->count();
        return (int) round(($done / $total) * 100);
    }

    /**
     * @return array{planned: float, income: float, expense: float, balance: float, variance: float}
     */
    public function getFinancialSummaryAttribute(): array
    {
        $income  = (float) $this->transactions()->where('type', 'income')->sum('amount');
        $expense = (float) $this->transactions()->where('type', 'expense')->sum('amount');
        $planned = (float) $this->planned_value;
        return [
            'planned'  => $planned,
            'income'   => $income,
            'expense'  => $expense,
            'balance'  => round($income - $expense, 2),
            'variance' => round($planned - $expense, 2),
        ];
    }
}
