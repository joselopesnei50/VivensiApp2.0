<?php

namespace App\Services;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\ProjectClass;
use App\Models\ProjectPerson;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Frequência de alunos (presenças/faltas) e risco de evasão.
 *
 * Regras (as mesmas do relatório de frequência por projeto):
 *  - falta justificada NÃO entra no % (nem como presença, nem como falta);
 *  - em risco = 3 faltas seguidas (as mais recentes) OU 30% ou mais de faltas.
 *
 * Usado pelo relatório do projeto (com filtro por turma), pela tela da turma
 * (% por aluno) e pela lista de turmas (média por turma).
 */
class AttendanceFrequencyService
{
    public const RISK_CONSECUTIVE    = 3;
    public const RISK_PERCENTUAL_MIN = 30.0;

    /**
     * Relatório por aluno no período (projeto inteiro ou uma turma).
     */
    public function build(int $tenantId, int $projectId, Carbon $from, Carbon $to, ?int $classId = null): array
    {
        $sessions = ClassSession::withoutGlobalScope('tenant')
            ->where('project_id', $projectId)
            ->where('tenant_id', $tenantId)
            ->when($classId, fn ($q) => $q->where('project_class_id', $classId))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date', 'desc')
            ->get(['id', 'title', 'date', 'project_class_id']);

        $attendances = ClassAttendance::withoutGlobalScope('tenant')
            ->whereIn('class_session_id', $sessions->pluck('id'))
            ->where('tenant_id', $tenantId)
            ->get(['project_person_id', 'class_session_id', 'status', 'checked_in_via']);

        // Alunos com registro no período.
        $people = ProjectPerson::withoutGlobalScope('tenant')
            ->whereIn('id', $attendances->pluck('project_person_id')->unique()->values())
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'enrollment_status']);

        $byPerson = $attendances->groupBy('project_person_id');
        $sessionDateById = $sessions->keyBy('id');

        $rows = [];
        $totalPresences = 0;
        $totalAbsences  = 0;
        $totalPublic    = 0;
        $atRiskCount    = 0;

        foreach ($people as $p) {
            $items = $byPerson->get($p->id, collect());
            $stats = $this->stats($items, $sessionDateById);

            if ($stats['at_risk']) {
                $atRiskCount++;
            }
            $totalPresences += $stats['presences'];
            $totalAbsences  += $stats['absences'];
            $totalPublic    += $items->whereIn('checked_in_via', ['link_publico', 'auto_cadastro'])->count();

            $rows[] = ['id' => $p->id, 'name' => $p->name, 'inactive' => $p->enrollment_status === 'inativo'] + $stats;
        }

        // Em risco primeiro, depois pior % de presença.
        usort($rows, function ($a, $b) {
            if ($a['at_risk'] !== $b['at_risk']) {
                return $a['at_risk'] ? -1 : 1;
            }
            return ($a['pct'] ?? 101) <=> ($b['pct'] ?? 101);
        });

        $totalRegistered = $attendances->count();

        return [
            'rows'          => $rows,
            'sessions'      => $sessions,
            'totalSessions' => $sessions->count(),
            'avgPct'        => ($totalPresences + $totalAbsences) > 0 ? $totalPresences / ($totalPresences + $totalAbsences) * 100 : null,
            'atRiskCount'   => $atRiskCount,
            'publicPct'     => $totalRegistered > 0 ? $totalPublic / $totalRegistered * 100 : null,
        ];
    }

    /**
     * Frequência de cada aluno numa turma, em todas as chamadas até hoje.
     *
     * @return Collection<int, array> indexado por project_person_id
     */
    public function forClass(ProjectClass $class): Collection
    {
        $sessions = ClassSession::withoutGlobalScope('tenant')
            ->where('tenant_id', $class->tenant_id)
            ->where('project_class_id', $class->id)
            ->whereDate('date', '<=', now()->toDateString())
            ->get(['id', 'date']);

        $sessionDateById = $sessions->keyBy('id');

        return ClassAttendance::withoutGlobalScope('tenant')
            ->where('tenant_id', $class->tenant_id)
            ->whereIn('class_session_id', $sessions->pluck('id'))
            ->get(['project_person_id', 'class_session_id', 'status'])
            ->groupBy('project_person_id')
            ->map(fn ($items) => $this->stats($items, $sessionDateById));
    }

    /**
     * Presença média de cada turma (presenças / (presenças + faltas)).
     *
     * @param  int[]  $classIds
     * @return array<int, float|null> project_class_id => %
     */
    public function averageByClass(int $tenantId, array $classIds): array
    {
        if (! $classIds) {
            return [];
        }

        $rows = DB::table('class_attendances as a')
            ->join('class_sessions as s', 's.id', '=', 'a.class_session_id')
            ->where('a.tenant_id', $tenantId)
            ->whereIn('s.project_class_id', $classIds)
            ->whereIn('a.status', ['presente', 'falta'])
            ->groupBy('s.project_class_id')
            ->selectRaw("s.project_class_id as class_id, SUM(CASE WHEN a.status = 'presente' THEN 1 ELSE 0 END) as presentes, COUNT(*) as total")
            ->get();

        $out = array_fill_keys($classIds, null);
        foreach ($rows as $r) {
            $out[(int) $r->class_id] = $r->total > 0 ? (float) ($r->presentes / $r->total * 100) : null;
        }
        return $out;
    }

    /** Agrega os registros de um aluno. */
    private function stats(Collection $items, Collection $sessionDateById): array
    {
        $presences = $items->where('status', 'presente')->count();
        $absences  = $items->where('status', 'falta')->count();
        $justified = $items->where('status', 'falta_justificada')->count();

        $denom = $presences + $absences;
        $pct = $denom > 0 ? (float) ($presences / $denom * 100) : null;

        $consecutive = $this->consecutiveAbsences($items, $sessionDateById);
        $riskConsec  = $consecutive >= self::RISK_CONSECUTIVE;
        $riskPercent = $pct !== null && (100 - $pct) >= self::RISK_PERCENTUAL_MIN;

        return [
            'eligible'     => $presences + $absences + $justified,
            'presences'    => $presences,
            'absences'     => $absences,
            'justified'    => $justified,
            'pct'          => $pct,
            'consecutive'  => $consecutive,
            'at_risk'      => $riskConsec || $riskPercent,
            'risk_consec'  => $riskConsec,
            'risk_percent' => $riskPercent,
        ];
    }

    /** Faltas seguidas, da chamada mais recente para trás. */
    private function consecutiveAbsences(Collection $items, Collection $sessionDateById): int
    {
        $sorted = $items->sortByDesc(fn ($a) => optional($sessionDateById->get($a->class_session_id))->date?->toDateString() ?? '')
            ->values();

        $count = 0;
        foreach ($sorted as $it) {
            if ($it->status !== 'falta') {
                break; // presença ou falta justificada quebra a sequência
            }
            $count++;
        }
        return $count;
    }
}
