<?php

namespace App\Http\Controllers;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Project;
use App\Models\ProjectClass;
use App\Models\ProjectPerson;
use App\Services\AttendanceFrequencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    // 'common' (professores) também — igual às Turmas e à Chamada.
    private const ROLES_READ = ['manager', 'employee', 'super_admin', 'ngo', 'common'];

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

    /**
     * Sanitiza célula CSV contra injection de fórmula do Excel.
     * Se começar com =, +, @, -, |, %, tab ou CR — prefixa apóstrofo pra
     * Excel/LibreOffice/Sheets tratarem como texto literal e não executar.
     * https://owasp.org/www-community/attacks/CSV_Injection
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') return '';
        $first = mb_substr($value, 0, 1);
        if (in_array($first, ['=', '+', '@', '-', '|', '%'], true) || $first === "\t" || $first === "\r") {
            return "'" . $value;
        }
        return $value;
    }

    // Dashboard de frequencia por projeto.
    public function show(Request $request, int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);
        [$from, $to] = $this->parsePeriod($request);
        $class = $this->findClass($project, $request);
        $data = app(AttendanceFrequencyService::class)->build($this->tenantId(), $project->id, $from, $to, $class?->id);

        $classes = ProjectClass::where('project_id', $project->id)
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('class-sessions.report', array_merge($data, [
            'project' => $project,
            'from'    => $from,
            'to'      => $to,
            'class'   => $class,
            'classes' => $classes,
        ]));
    }

    // CSV consolidado do relatorio (respeitando filtro).
    public function exportReport(Request $request, int $projectId)
    {
        abort_unless(in_array(auth()->user()->role, self::ROLES_READ, true), 403);

        $project = $this->findProject($projectId);
        [$from, $to] = $this->parsePeriod($request);
        $class = $this->findClass($project, $request);
        $data = app(AttendanceFrequencyService::class)->build($this->tenantId(), $project->id, $from, $to, $class?->id);

        $filename = 'frequencia-' . $project->id . ($class ? '-turma-' . $class->id : '') . '-' . $from->toDateString() . '-a-' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($data, $project, $from, $to, $class) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF"); // BOM pra Excel-BR

            // Cabecalho de auditoria — uso interno, alinhado com politica LGPD do projeto.
            fputcsv($out, ['# Relatorio de frequencia — uso interno']);
            fputcsv($out, ['# Projeto', $this->csvSafe($project->name)]);
            if ($class) {
                fputcsv($out, ['# Turma', $this->csvSafe($class->name)]);
            }
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
                    $this->csvSafe($r['name']),
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
            fputcsv($out, ['# Projeto', $this->csvSafe($project->name)]);
            fputcsv($out, ['# Sessao', $this->csvSafe($session->title)]);
            fputcsv($out, ['# Data', $session->date->format('d/m/Y')]);
            fputcsv($out, ['# Gerado em', now()->format('d/m/Y H:i')]);
            fputcsv($out, []);

            fputcsv($out, ['Aluno', 'Telefone', 'Status', 'Canal', 'Registrado em', 'Justificativa']);

            foreach ($attendances as $a) {
                fputcsv($out, [
                    $this->csvSafe($a->student->name ?? '(removido)'),
                    $this->csvSafe($a->student->phone ?? ''),
                    $this->statusLabel($a->status),
                    $this->channelLabel($a->checked_in_via),
                    $a->checked_in_at?->format('d/m/Y H:i') ?? '',
                    $this->csvSafe($a->justification ?? ''),
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

    /** Turma do filtro (?turma=), só deste projeto e organização. */
    private function findClass(Project $project, Request $request): ?ProjectClass
    {
        $id = (int) $request->query('turma', 0);
        if ($id <= 0) {
            return null;
        }
        return ProjectClass::where('id', $id)
            ->where('project_id', $project->id)
            ->where('tenant_id', $this->tenantId())
            ->first();
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
