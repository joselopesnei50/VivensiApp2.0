<?php

namespace App\Http\Controllers;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectPerson;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    private const ROLES_READ           = ['manager', 'employee', 'super_admin', 'ngo'];
    private const RISK_CONSECUTIVE     = 3;    // Faltas puras consecutivas (decisao 1c parte 1)
    private const RISK_PERCENTUAL_MIN  = 30.0; // % de faltas no periodo (decisao 1c parte 2)

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function findProject(int $projectId): Project
    {
        return Project::where('id', $projectId)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // Dashboard de frequencia por projeto.
    public function show(Request $request, int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);
        [$from, $to] = $this->parsePeriod($request);
        $data = $this->buildReport($project, $from, $to);

        return view('class-sessions.report', array_merge($data, [
            'project' => $project,
            'from'    => $from,
            'to'     => $to,
        ]));
    }

    // CSV consolidado do relatorio (respeitando filtro).
    public function exportReport(Request $request, int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);
        [$from, $to] = $this->parsePeriod($request);
        $data = $this->buildReport($project, $from, $to);

        $filename = 'frequencia-' . $project->id . '-' . $from->toDateString() . '-a-' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($data, $project, $from, $to) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF"); // BOM pra Excel-BR

            // Cabecalho de auditoria — uso interno, alinhado com politica LGPD do projeto.
            fputcsv($out, ['# Relatorio de frequencia — uso interno']);
            fputcsv($out, ['# Projeto', $project->name]);
            fputcsv($out, ['# Periodo', $from->format('d/m/Y') . ' a ' . $to->format('d/m/Y')]);
            fputcsv($out, ['# Gerado em', now()->format('d/m/Y H:i')]);
            fputcsv($out, []);

            fputcsv($out, [
                'Aluno',
                'Sessoes elegiveis',
                'Presencas',
                'Faltas',
                'Faltas justificadas',
                '% presenca',
                'Em risco',
            ]);

            foreach ($data['rows'] as $r) {
                fputcsv($out, [
                    $r['name'],
                    $r['eligible'],
                    $r['presences'],
                    $r['absences'],
                    $r['justified'],
                    $r['pct'] === null ? '' : number_format($r['pct'], 1, ',', '.'),
                    $r['at_risk'] ? 'SIM' : '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // CSV da chamada de uma sessao especifica.
    public function exportSession(int $projectId, int $sessionId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);

        $session = ClassSession::where('id', $sessionId)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
        abort_if($session->project_id !== $project->id, 404);

        $attendances = ClassAttendance::with('student:id,name,phone')
            ->where('class_session_id', $session->id)
            ->where('tenant_id', $this->tenantId())
            ->get();

        $filename = 'chamada-' . $session->id . '-' . $session->date->toDateString() . '.csv';

        return response()->streamDownload(function () use ($attendances, $project, $session) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['# Chamada — uso interno']);
            fputcsv($out, ['# Projeto', $project->name]);
            fputcsv($out, ['# Sessao', $session->title]);
            fputcsv($out, ['# Data', $session->date->format('d/m/Y')]);
            fputcsv($out, ['# Gerado em', now()->format('d/m/Y H:i')]);
            fputcsv($out, []);

            fputcsv($out, ['Aluno', 'Telefone', 'Status', 'Canal', 'Registrado em', 'Justificativa']);

            foreach ($attendances as $a) {
                fputcsv($out, [
                    $a->student->name ?? '(removido)',
                    $a->student->phone ?? '',
                    $this->statusLabel($a->status),
                    $this->channelLabel($a->checked_in_via),
                    $a->checked_in_at?->format('d/m/Y H:i') ?? '',
                    $a->justification ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function parsePeriod(Request $request): array
    {
        try {
            $from = $request->filled('from')
                ? Carbon::parse($request->query('from'))->startOfDay()
                : now()->subDays(90)->startOfDay();

            $to = $request->filled('to')
                ? Carbon::parse($request->query('to'))->endOfDay()
                : now()->endOfDay();
        } catch (\Exception) {
            $from = now()->subDays(90)->startOfDay();
            $to   = now()->endOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    // Agrega presencas do projeto no periodo e monta linhas por aluno + KPIs.
    private function buildReport(Project $project, Carbon $from, Carbon $to): array
    {
        $sessions = ClassSession::where('project_id', $project->id)
            ->where('tenant_id', $this->tenantId())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date', 'desc')
            ->get(['id', 'title', 'date']);

        $sessionIds = $sessions->pluck('id');
        $totalSessions = $sessions->count();

        $attendances = ClassAttendance::whereIn('class_session_id', $sessionIds)
            ->where('tenant_id', $this->tenantId())
            ->get(['project_person_id', 'class_session_id', 'status', 'checked_in_via']);

        // Alunos com registro no periodo (dashboard mostra quem participou).
        $personIds = $attendances->pluck('project_person_id')->unique()->values();
        $people = ProjectPerson::whereIn('id', $personIds)
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'enrollment_status']);

        // Indexa attendances por aluno pra agregar em PHP.
        $byPerson = $attendances->groupBy('project_person_id');
        $sessionDateById = $sessions->keyBy('id');

        $rows = [];
        $totalPresences = 0;
        $totalAbsences  = 0;
        $totalPublic    = 0;
        $totalRegistered = $attendances->count();
        $atRiskCount = 0;

        foreach ($people as $p) {
            $items = $byPerson->get($p->id, collect());
            $presences = $items->where('status', 'presente')->count();
            $absences  = $items->where('status', 'falta')->count();
            $justified = $items->where('status', 'falta_justificada')->count();
            $eligible  = $presences + $absences + $justified;

            // Decisao 2b: justificada NAO conta como falta pro %.
            $pctDenom = $presences + $absences;
            $pct = $pctDenom > 0 ? ($presences / $pctDenom) * 100 : null;

            $consecutive = $this->consecutiveAbsences($items, $sessionDateById);
            $isRiskConsec  = $consecutive >= self::RISK_CONSECUTIVE;
            $isRiskPercent = $pct !== null && (100 - $pct) >= self::RISK_PERCENTUAL_MIN;
            $atRisk = $isRiskConsec || $isRiskPercent;

            if ($atRisk) $atRiskCount++;
            $totalPresences += $presences;
            $totalAbsences  += $absences;
            $totalPublic    += $items->whereIn('checked_in_via', ['link_publico', 'auto_cadastro'])->count();

            $rows[] = [
                'id'              => $p->id,
                'name'            => $p->name,
                'inactive'        => $p->enrollment_status === 'inativo',
                'eligible'        => $eligible,
                'presences'       => $presences,
                'absences'        => $absences,
                'justified'       => $justified,
                'pct'             => $pct,
                'consecutive'     => $consecutive,
                'at_risk'         => $atRisk,
                'risk_consec'     => $isRiskConsec,
                'risk_percent'    => $isRiskPercent,
            ];
        }

        // Ordena: em risco primeiro, depois pior % presenca.
        usort($rows, function ($a, $b) {
            if ($a['at_risk'] !== $b['at_risk']) return $a['at_risk'] ? -1 : 1;
            return ($a['pct'] ?? 101) <=> ($b['pct'] ?? 101);
        });

        $avgPct = ($totalPresences + $totalAbsences) > 0
            ? ($totalPresences / ($totalPresences + $totalAbsences)) * 100
            : null;

        $publicPct = $totalRegistered > 0
            ? ($totalPublic / $totalRegistered) * 100
            : null;

        return [
            'rows'          => $rows,
            'sessions'      => $sessions,
            'totalSessions' => $totalSessions,
            'avgPct'        => $avgPct,
            'atRiskCount'   => $atRiskCount,
            'publicPct'     => $publicPct,
        ];
    }

    private function consecutiveAbsences($items, $sessionDateById): int
    {
        // Ordena items do mais recente pro mais antigo pela data da sessao.
        $sorted = $items->sortByDesc(fn ($a) => optional($sessionDateById->get($a->class_session_id))->date?->toDateString() ?? '')
            ->values();

        $count = 0;
        foreach ($sorted as $it) {
            if ($it->status === 'falta') {
                $count++;
                continue;
            }
            break; // presente ou justificada quebra a sequencia.
        }
        return $count;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'presente'          => 'Presente',
            'falta'             => 'Falta',
            'falta_justificada' => 'Falta justificada',
            default             => $status,
        };
    }

    private function channelLabel(string $via): string
    {
        return match ($via) {
            'professor'     => 'Professor',
            'link_publico'  => 'Link publico',
            'auto_cadastro' => 'Autocadastro',
            default         => $via,
        };
    }
}
