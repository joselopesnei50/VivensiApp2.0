<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePdfJob;
use App\Models\GeneratedReport;
use App\Support\AuditDownload;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatorio anual social do modulo /ngo/beneficiaries.
 *
 * Contem: renderizacao HTML (annualReport), geracao de PDF (annualReportPdf +
 * annualReportPdfAppendix) e 5 variantes de export CSV — todos os exports
 * passam pelo dispatcher unico annualReportExport(?format=...). Os 5
 * endpoints antigos permanecem como wrappers finos pra nao quebrar
 * bookmarks/links externos.
 *
 * Formatos suportados: `detailed` (default), `grouped`, `grouped-simple`,
 * `pivot-type`, `pivot-user`.
 *
 * Historico:
 * - 2026-07-18: extraido de BeneficiaryController (quebra do controller
 *   monstro 1516 -> 707 no main).
 * - 2026-07-18: unificacao dos 5 exports em 1 dispatcher.
 */
class BeneficiaryReportController extends Controller
{
    private const EXPORT_FORMATS = ['detailed', 'grouped', 'grouped-simple', 'pivot-type', 'pivot-user'];

    public function annualReport(Request $request)
    {
        $ctx = $this->buildExportContext($request);

        $base = $this->baseAttendanceQuery($ctx);

        $totalAttendances = (int) (clone $base)->count();
        $uniqueFamilies = (int) (clone $base)->distinct('a.beneficiary_id')->count('a.beneficiary_id');

        $monthlyRows = (clone $base)
            ->select(DB::raw($this->monthExpr() . ' as m'), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw($this->monthExpr()))
            ->orderBy('m')
            ->get();
        $monthly = array_fill(1, 12, 0);
        foreach ($monthlyRows as $r) $monthly[(int) $r->m] = (int) $r->c;

        $byType = (clone $base)
            ->select('a.type', DB::raw('COUNT(*) as c'))
            ->groupBy('a.type')
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        $byUser = (clone $base)
            ->select(DB::raw("COALESCE(u.name, 'Sistema') as name"), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"))
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        $topFamilies = (clone $base)
            ->select('b.id', 'b.name', 'b.status', DB::raw('COUNT(*) as c'))
            ->groupBy('b.id', 'b.name', 'b.status')
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        $statuses = ['active' => 'Ativo', 'inactive' => 'Inativo', 'graduated' => 'Graduado'];
        $types = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->where('a.tenant_id', $ctx['tenantId'])
            ->where('b.tenant_id', $ctx['tenantId'])
            ->whereBetween('a.date', [$ctx['from'], $ctx['to']])
            ->select('a.type')
            ->distinct()
            ->orderBy('a.type')
            ->pluck('type')
            ->all();

        $orgName = $this->orgName($ctx['tenantId']);
        $generatedAt = now()->format('d/m/Y H:i');

        return view('ngo.beneficiaries.annual_report', [
            'year'             => $ctx['year'],
            'from'             => $ctx['from'],
            'to'               => $ctx['to'],
            'benefStatus'      => $ctx['benefStatus'],
            'type'             => $ctx['type'],
            'statuses'         => $statuses,
            'types'            => $types,
            'totalAttendances' => $totalAttendances,
            'uniqueFamilies'   => $uniqueFamilies,
            'monthly'          => $monthly,
            'byType'           => $byType,
            'byUser'           => $byUser,
            'topFamilies'      => $topFamilies,
            'orgName'          => $orgName,
            'generatedAt'      => $generatedAt,
        ]);
    }

    public function annualReportPdf(Request $request)
    {
        return $this->dispatchPdfReport($request, 'annual_report', false);
    }

    public function annualReportPdfAppendix(Request $request)
    {
        return $this->dispatchPdfReport($request, 'annual_report_appendix', true);
    }

    /**
     * Dispatcher unico dos 5 exports CSV. Aceita `?format=` com valores
     * detailed | grouped | grouped-simple | pivot-type | pivot-user.
     * Formato invalido cai em `detailed`.
     */
    public function annualReportExport(Request $request): StreamedResponse
    {
        $format = (string) $request->get('format', 'detailed');
        if (!in_array($format, self::EXPORT_FORMATS, true)) {
            $format = 'detailed';
        }

        $ctx = $this->buildExportContext($request);
        $ctx['format'] = $format;

        AuditDownload::log($this->auditKeyFor($format), null, [
            'format'       => 'csv',
            'variant'      => $format,
            'year'         => $ctx['year'],
            'benef_status' => $ctx['benefStatus'],
            'type'         => $ctx['type'],
            'q'            => $ctx['q'],
        ]);

        $filename = $this->filenameFor($format, $ctx['year']);
        $stream   = $this->streamFor($format, $ctx);

        return response()->streamDownload($stream, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ── Wrappers finos preservando os 5 endpoints antigos ─────────────────

    public function annualReportExportCsv(Request $request): StreamedResponse
    {
        return $this->annualReportExport($request->merge(['format' => 'detailed']));
    }

    public function annualReportExportGroupedCsv(Request $request): StreamedResponse
    {
        return $this->annualReportExport($request->merge(['format' => 'grouped']));
    }

    public function annualReportExportGroupedSimpleCsv(Request $request): StreamedResponse
    {
        return $this->annualReportExport($request->merge(['format' => 'grouped-simple']));
    }

    public function annualReportExportPivotTypeCsv(Request $request): StreamedResponse
    {
        return $this->annualReportExport($request->merge(['format' => 'pivot-type']));
    }

    public function annualReportExportPivotUserCsv(Request $request): StreamedResponse
    {
        return $this->annualReportExport($request->merge(['format' => 'pivot-user']));
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    /**
     * Reune filtros comuns aos exports + validacao do ano (2000..currYear+2).
     * @return array{tenantId:int, year:int, benefStatus:string, type:string, q:string, from:string, to:string}
     */
    private function buildExportContext(Request $request): array
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) {
            $year = (int) date('Y');
        }

        return [
            'tenantId'    => $tenantId,
            'year'        => $year,
            'benefStatus' => trim((string) $request->get('benef_status', '')),
            'type'        => trim((string) $request->get('type', '')),
            'q'           => trim((string) $request->get('q', '')),
            'from'        => Carbon::create($year, 1, 1)->toDateString(),
            'to'          => Carbon::create($year, 12, 31)->toDateString(),
        ];
    }

    private function baseAttendanceQuery(array $ctx)
    {
        $q = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.tenant_id', $ctx['tenantId'])
            ->where('b.tenant_id', $ctx['tenantId'])
            ->whereBetween('a.date', [$ctx['from'], $ctx['to']]);

        if ($ctx['benefStatus'] !== '') $q->where('b.status', $ctx['benefStatus']);
        if ($ctx['type'] !== '')        $q->where('a.type', $ctx['type']);

        return $q;
    }

    /**
     * Extracao de mes cross-DB (MySQL usa MONTH(), SQLite dos testes usa
     * strftime). Devolve string SQL pra compor em `select`/`groupBy`.
     */
    private function monthExpr(string $column = 'a.date'): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    }

    private function auditKeyFor(string $format): string
    {
        return match ($format) {
            'grouped', 'grouped-simple' => 'Beneficiaries:AnnualReport:Grouped',
            'pivot-type', 'pivot-user'  => 'Beneficiaries:AnnualReport:Pivot',
            default                     => 'Beneficiaries:AnnualReport',
        };
    }

    private function filenameFor(string $format, int $year): string
    {
        $suffix = date('Y-m-d_His');
        $prefix = match ($format) {
            'grouped'        => 'relatorio-social-agrupado-mes-tipo-tecnico',
            'grouped-simple' => 'relatorio-social-agrupado-mes-tipo',
            'pivot-type'     => 'relatorio-social-pivot-tipo',
            'pivot-user'     => 'relatorio-social-pivot-equipe',
            default          => 'relatorio-social-detalhado',
        };
        return "{$prefix}-{$year}-{$suffix}.csv";
    }

    private function streamFor(string $format, array $ctx): Closure
    {
        return match ($format) {
            'grouped'        => $this->streamGrouped($ctx),
            'grouped-simple' => $this->streamGroupedSimple($ctx),
            'pivot-type'     => $this->streamPivotType($ctx),
            'pivot-user'     => $this->streamPivotUser($ctx),
            default          => $this->streamDetailed($ctx),
        };
    }

    private function streamDetailed(array $ctx): Closure
    {
        return function () use ($ctx) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Beneficiário', 'Status', 'NIS', 'CPF', 'Data', 'Tipo', 'Descrição', 'Registrado por']);

            $baseQ = $this->baseAttendanceQuery($ctx)
                ->select([
                    'b.name as beneficiary_name',
                    'b.status as beneficiary_status',
                    'b.nis as beneficiary_nis',
                    'b.cpf as beneficiary_cpf',
                    'a.date',
                    'a.type',
                    'a.description',
                    DB::raw("COALESCE(u.name, 'Sistema') as user_name"),
                ])
                ->orderByDesc('a.date')
                ->orderByDesc('a.id');

            if ($ctx['q'] !== '') {
                $q = $ctx['q'];
                $baseQ->where(function ($w) use ($q) {
                    $w->where('a.description', 'like', '%' . $q . '%')
                      ->orWhere('a.type', 'like', '%' . $q . '%')
                      ->orWhere('b.name', 'like', '%' . $q . '%');
                });
            }

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->beneficiary_name,
                        $r->beneficiary_status,
                        $r->beneficiary_nis,
                        $r->beneficiary_cpf,
                        $r->date,
                        $r->type,
                        $r->description,
                        $r->user_name,
                    ]);
                }
            });

            fclose($out);
        };
    }

    private function streamGrouped(array $ctx): Closure
    {
        return function () use ($ctx) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ano', 'Mês', 'Tipo', 'Técnico/Usuário', 'Atendimentos', 'Famílias únicas']);

            $q = $this->baseAttendanceQuery($ctx)
                ->select([
                    DB::raw($ctx['year'] . ' as year'),
                    DB::raw($this->monthExpr() . ' as month'),
                    'a.type',
                    DB::raw("COALESCE(u.name, 'Sistema') as user_name"),
                    DB::raw('COUNT(*) as attendances'),
                    DB::raw('COUNT(DISTINCT a.beneficiary_id) as unique_families'),
                ])
                ->groupBy(DB::raw($this->monthExpr()), 'a.type', DB::raw("COALESCE(u.name, 'Sistema')"))
                ->orderBy('month')
                ->orderBy('a.type')
                ->orderBy('user_name');

            $year = $ctx['year'];
            $q->chunk(500, function ($rows) use ($out, $year) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        (int) $year,
                        str_pad((string) ((int) $r->month), 2, '0', STR_PAD_LEFT),
                        $r->type,
                        $r->user_name,
                        (int) $r->attendances,
                        (int) $r->unique_families,
                    ]);
                }
            });

            fclose($out);
        };
    }

    private function streamGroupedSimple(array $ctx): Closure
    {
        return function () use ($ctx) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ano', 'Mês', 'Tipo', 'Atendimentos', 'Famílias únicas']);

            // Nao inclui usuarios — remove o left join no user (evita GROUP BY em coluna nula)
            $q = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->where('a.tenant_id', $ctx['tenantId'])
                ->where('b.tenant_id', $ctx['tenantId'])
                ->whereBetween('a.date', [$ctx['from'], $ctx['to']])
                ->select([
                    DB::raw($ctx['year'] . ' as year'),
                    DB::raw($this->monthExpr() . ' as month'),
                    'a.type',
                    DB::raw('COUNT(*) as attendances'),
                    DB::raw('COUNT(DISTINCT a.beneficiary_id) as unique_families'),
                ])
                ->groupBy(DB::raw($this->monthExpr()), 'a.type')
                ->orderBy('month')
                ->orderBy('a.type');

            if ($ctx['benefStatus'] !== '') $q->where('b.status', $ctx['benefStatus']);
            if ($ctx['type'] !== '')        $q->where('a.type', $ctx['type']);

            $year = $ctx['year'];
            $q->chunk(500, function ($rows) use ($out, $year) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        (int) $year,
                        str_pad((string) ((int) $r->month), 2, '0', STR_PAD_LEFT),
                        $r->type,
                        (int) $r->attendances,
                        (int) $r->unique_families,
                    ]);
                }
            });

            fclose($out);
        };
    }

    private function streamPivotType(array $ctx): Closure
    {
        return function () use ($ctx) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            $header = ['Tipo'];
            for ($m = 1; $m <= 12; $m++) $header[] = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $header[] = 'Total';
            $header[] = 'Famílias únicas (ano)';
            fputcsv($out, $header);

            // Base sem left join no user (nao precisamos aqui)
            $baseNoUser = fn () => DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->where('a.tenant_id', $ctx['tenantId'])
                ->where('b.tenant_id', $ctx['tenantId'])
                ->whereBetween('a.date', [$ctx['from'], $ctx['to']]);

            $q = $baseNoUser()
                ->select('a.type', DB::raw($this->monthExpr() . ' as m'), DB::raw('COUNT(*) as c'))
                ->groupBy('a.type', DB::raw($this->monthExpr()))
                ->orderBy('a.type');
            $uq = $baseNoUser()
                ->select('a.type', DB::raw('COUNT(DISTINCT a.beneficiary_id) as uf'))
                ->groupBy('a.type')
                ->orderBy('a.type');

            if ($ctx['benefStatus'] !== '') { $q->where('b.status', $ctx['benefStatus']); $uq->where('b.status', $ctx['benefStatus']); }
            if ($ctx['type'] !== '')        { $q->where('a.type', $ctx['type']); $uq->where('a.type', $ctx['type']); }

            $rows = $q->get();
            $uniqueRows = $uq->get()->keyBy('type');

            $matrix = [];
            foreach ($rows as $r) {
                $t = (string) $r->type;
                $m = (int) $r->m;
                if (!isset($matrix[$t])) $matrix[$t] = array_fill(1, 12, 0);
                $matrix[$t][$m] = (int) $r->c;
            }

            foreach ($matrix as $t => $months) {
                $total = array_sum($months);
                $uf = (int) ($uniqueRows[$t]->uf ?? 0);
                $line = [$t];
                for ($m = 1; $m <= 12; $m++) $line[] = (int) ($months[$m] ?? 0);
                $line[] = (int) $total;
                $line[] = $uf;
                fputcsv($out, $line);
            }

            fclose($out);
        };
    }

    private function streamPivotUser(array $ctx): Closure
    {
        return function () use ($ctx) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            $header = ['Técnico/Usuário'];
            for ($m = 1; $m <= 12; $m++) $header[] = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $header[] = 'Total';
            $header[] = 'Famílias únicas (ano)';
            fputcsv($out, $header);

            $q = $this->baseAttendanceQuery($ctx)
                ->select(DB::raw("COALESCE(u.name, 'Sistema') as user_name"), DB::raw($this->monthExpr() . ' as m'), DB::raw('COUNT(*) as c'))
                ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"), DB::raw($this->monthExpr()))
                ->orderBy('user_name');

            $uq = $this->baseAttendanceQuery($ctx)
                ->select(DB::raw("COALESCE(u.name, 'Sistema') as user_name"), DB::raw('COUNT(DISTINCT a.beneficiary_id) as uf'))
                ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"))
                ->orderBy('user_name');

            $rows = $q->get();
            $uniqueRows = $uq->get()->keyBy('user_name');

            $matrix = [];
            foreach ($rows as $r) {
                $u = (string) $r->user_name;
                $m = (int) $r->m;
                if (!isset($matrix[$u])) $matrix[$u] = array_fill(1, 12, 0);
                $matrix[$u][$m] = (int) $r->c;
            }

            foreach ($matrix as $u => $months) {
                $total = array_sum($months);
                $uf = (int) ($uniqueRows[$u]->uf ?? 0);
                $line = [$u];
                for ($m = 1; $m <= 12; $m++) $line[] = (int) ($months[$m] ?? 0);
                $line[] = (int) $total;
                $line[] = $uf;
                fputcsv($out, $line);
            }

            fclose($out);
        };
    }

    private function dispatchPdfReport(Request $request, string $type, bool $isAppendix)
    {
        $ctx = $this->buildExportContext($request);

        AuditDownload::log('Beneficiaries:AnnualReport', null, array_filter([
            'format'       => 'pdf',
            'variant'      => $isAppendix ? 'appendix' : null,
            'year'         => $ctx['year'],
            'benef_status' => $ctx['benefStatus'],
            'type'         => $ctx['type'],
        ]));

        $report = GeneratedReport::create([
            'tenant_id' => $ctx['tenantId'],
            'user_id'   => auth()->id(),
            'type'      => $type,
            'params'    => [
                'tenant_id'    => $ctx['tenantId'],
                'year'         => $ctx['year'],
                'benef_status' => $ctx['benefStatus'],
                'type'         => $ctx['type'],
                'org_name'     => $this->orgName($ctx['tenantId']),
                'emitter'      => auth()->user()->name ?? '—',
            ],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json([
            'report_id'  => $report->id,
            'status_url' => route('reports.status', $report->id),
        ]);
    }

    private function orgName(int $tenantId): string
    {
        return ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
    }
}
