<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Project extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'budget',
        'start_date',
        'end_date',
        'status',
        'ngo_grant_id',
        'address',
        'latitude',
        'longitude',
        'ai_summary',
        'ai_summary_at',
        'ai_summary_status',
        'archived_at',
        'archived_by',
        // ── Planejamento estrategico (Fase 1) ──
        'presentation',
        'justification',
        'context',
        'target_audience',
        'general_objective',
        'specific_objectives',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'end_date'            => 'date',
        'budget'              => 'decimal:2',
        'ai_summary_at'       => 'datetime',
        'archived_at'         => 'datetime',
        'specific_objectives' => 'array',
    ];

    // Desativa a coluna updated_at que não existe no banco legado
    const UPDATED_AT = null;

    // ── Arquivamento ─────────────────────────────────────────────────────────
    // archived_at preserva o histórico (sem delete). Listagens padrão usam
    // ->active(); a tela /projects/archived usa ->archived().

    public function scopeActive($q)   { return $q->whereNull('projects.archived_at'); }
    public function scopeArchived($q) { return $q->whereNotNull('projects.archived_at'); }

    public function isArchived(): bool { return ! is_null($this->archived_at); }

    public function archive(?int $userId = null): bool
    {
        $this->archived_at = now();
        $this->archived_by = $userId ?? auth()->id();
        return $this->save();
    }

    public function unarchive(): bool
    {
        $this->archived_at = null;
        $this->archived_by = null;
        return $this->save();
    }
    
    // Relacionamento com Logs (Opicional por enquanto, mas bom ter)
    // Relacionamento com Transações (Opicional)

    public function ngo_grant()
    {
        return $this->belongsTo(NgoGrant::class, 'ngo_grant_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function people()
    {
        return $this->hasMany(ProjectPerson::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timelineRecords()
    {
        return $this->hasMany(ProjectTimelineRecord::class)->orderBy('date', 'desc')->orderBy('created_at', 'desc');
    }

    public function logs()
    {
        return $this->hasMany(ProjectLog::class)->orderBy('created_at', 'desc');
    }

    public function goals()
    {
        return $this->hasMany(ProjectGoal::class)->orderBy('created_at', 'desc');
    }

    public function stages()
    {
        return $this->hasMany(ProjectStage::class)->orderBy('order')->orderBy('start_date');
    }

    // Alias BC — a UI de Planejamento (Fase 3 substitui) ainda referencia
    // `milestones`. Retorna as mesmas linhas ordenadas por target_date
    // (campo preservado no rename).
    public function milestones()
    {
        return $this->hasMany(ProjectStage::class)->orderBy('target_date');
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    public function hasStages(): bool
    {
        return $this->stages()->exists();
    }

    /**
     * Budget efetivo: soma de planned_value das stages ativas quando o projeto
     * usa stages; senao null (UI cai no `budget` cadastrado manualmente).
     */
    public function getCalculatedBudgetAttribute(): ?float
    {
        if (! $this->hasStages()) {
            return null;
        }
        return (float) $this->stages()
            ->where('status', '!=', 'cancelled')
            ->sum('planned_value');
    }

    /**
     * Etapa atual (regra determinista):
     * 1) status='in_progress' AND now BETWEEN start_date AND end_date
     * 2) end_date < now AND status != completed  -> atrasada
     * 3) primeira futura (start_date > now)      -> proxima
     * 4) tudo completed                          -> null
     * Empate: order ASC.
     */
    public function getCurrentStageAttribute(): ?ProjectStage
    {
        $stages = $this->stages()->get();
        if ($stages->isEmpty()) {
            return null;
        }
        $today = now()->startOfDay();

        $current = $stages->first(function (ProjectStage $s) use ($today) {
            if ($s->status !== 'in_progress') return false;
            if ($s->start_date && $today->lt($s->start_date)) return false;
            if ($s->end_date   && $today->gt($s->end_date))   return false;
            return true;
        });
        if ($current) return $current;

        $overdue = $stages->first(fn (ProjectStage $s) =>
            $s->end_date && $s->end_date->lt($today) && $s->status !== 'completed'
        );
        if ($overdue) return $overdue;

        $upcoming = $stages->first(fn (ProjectStage $s) =>
            $s->start_date && $s->start_date->gt($today)
        );
        return $upcoming;
    }
}
