<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turma persistente dentro de um Projeto NGO.
 *
 * Agrupa N ClassSessions (chamadas dos dias efetivos) + alunos matriculados
 * fixos. Padrao ERP escolar: cada projeto tem varias turmas ("Aula de Violao
 * Iniciante Segunda 19h"), cada turma tem sua grade de chamadas.
 *
 * Nome ProjectClass (nao Course) para nao colidir com Academy\Course (LMS).
 */
class ProjectClass extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_ATIVO     = 'ativo';
    public const STATUS_ENCERRADO = 'encerrado';

    public const MODE_FECHADA = 'fechada';
    public const MODE_ABERTA  = 'aberta';

    // ISO weekday map (1 = segunda, 7 = domingo)
    public const WEEKDAY_LABELS = [
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'name',
        'description',
        'default_teacher_user_id',
        'default_mode',
        'default_start_time',
        'default_end_time',
        'weekdays',
        'start_date',
        'end_date',
        'max_students',
        'status',
        'notes',
    ];

    protected $casts = [
        'weekdays'     => 'array',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'max_students' => 'integer',
    ];

    // ── Relacionamentos ─────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_teacher_user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProjectClassEnrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', ProjectClassEnrollment::STATUS_ATIVO);
    }

    /**
     * Alunos matriculados como collection de ProjectPerson via pivot.
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(ProjectPerson::class, 'project_class_enrollments')
            ->using(ProjectClassEnrollment::class)
            ->withPivot(['status', 'enrolled_at', 'unenrolled_at'])
            ->withTimestamps();
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeAtivo($query)
    {
        return $query->where('status', self::STATUS_ATIVO);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isAtivo(): bool
    {
        return $this->status === self::STATUS_ATIVO;
    }

    public function activeEnrollmentsCount(): int
    {
        return $this->enrollments()->where('status', ProjectClassEnrollment::STATUS_ATIVO)->count();
    }

    public function hasVacancy(): bool
    {
        if ($this->max_students === null) {
            return true;
        }
        return $this->activeEnrollmentsCount() < $this->max_students;
    }

    /**
     * Retorna datas efetivas de aula dentro do periodo baseado em weekdays +
     * start_date/end_date. Usada para gerar sessoes automaticamente.
     *
     * @return \Illuminate\Support\Collection<\Carbon\Carbon>
     */
    public function computeSessionDates(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): \Illuminate\Support\Collection
    {
        $weekdays = collect($this->weekdays ?? [])->map(fn ($d) => (int) $d);
        if ($weekdays->isEmpty()) {
            return collect();
        }

        $start = $from ?? ($this->start_date ? $this->start_date->copy() : now());
        $end   = $to   ?? ($this->end_date ? $this->end_date->copy() : now()->addMonth());

        $dates = collect();
        $cursor = $start->copy()->startOfDay();
        $stopAt = $end->copy()->startOfDay();

        while ($cursor->lte($stopAt)) {
            // Carbon::dayOfWeek: 0=Sunday..6=Saturday. Converte p/ ISO (1=Mon..7=Sun).
            $isoDay = $cursor->dayOfWeek === 0 ? 7 : $cursor->dayOfWeek;
            if ($weekdays->contains($isoDay)) {
                $dates->push($cursor->copy());
            }
            $cursor->addDay();
        }

        return $dates;
    }

    public function weekdayLabels(): string
    {
        return collect($this->weekdays ?? [])
            ->map(fn ($d) => self::WEEKDAY_LABELS[(int) $d] ?? '')
            ->filter()
            ->implode(', ');
    }
}
