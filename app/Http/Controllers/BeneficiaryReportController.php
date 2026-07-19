<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePdfJob;
use App\Models\GeneratedReport;
use App\Support\AuditDownload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Relatorio anual social do modulo /ngo/beneficiaries.
 *
 * Contem os 8 endpoints do painel anual: renderizacao HTML, geracao de
 * PDF (via GeneratePdfJob) e 5 variantes de export CSV (detalhado,
 * agrupado, agrupado simples, pivot por tipo, pivot por usuario).
 *
 * Extraido de BeneficiaryController em 2026-07-18 como parte da quebra do
 * controller monstro (1516 -> ~600 linhas no main). Zero mudanca de logica.
 */
class BeneficiaryReportController extends Controller
{
    public function annualReport(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type = trim((string) $request->get('type', ''));

        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        $base = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.tenant_id', $tenantId)
            ->where('b.tenant_id', $tenantId)
            ->whereBetween('a.date', [$from, $to]);

        if ($benefStatus !== '') $base->where('b.status', $benefStatus);
        if ($type !== '') $base->where('a.type', $type);

        $totalAttendances = (int) (clone $base)->count();
        $uniqueFamilies = (int) (clone $base)->distinct('a.beneficiary_id')->count('a.beneficiary_id');

        $monthlyRows = (clone $base)
            ->select(DB::raw('MONTH(a.date) as m'), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw('MONTH(a.date)'))
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
            ->where('a.tenant_id', $tenantId)
            ->where('b.tenant_id', $tenantId)
            ->whereBetween('a.date', [$from, $to])
            ->select('a.type')
            ->distinct()
            ->orderBy('a.type')
            ->pluck('type')
            ->all();

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');

        return view('ngo.beneficiaries.annual_report', compact(
            'year',
            'from',
            'to',
            'benefStatus',
            'type',
            'statuses',
            'types',
            'totalAttendances',
            'uniqueFamilies',
            'monthly',
            'byType',
            'byUser',
            'topFamilies',
            'orgName',
            'generatedAt'
        ));
    }

    public function annualReportPdf(Request $request)
    {
        $tenantId    = auth()->user()->tenant_id;
        $year        = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');
        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type        = trim((string) $request->get('type', ''));

        AuditDownload::log('Beneficiaries:AnnualReport', null, ['format' => 'pdf', 'year' => $year, 'benef_status' => $benefStatus, 'type' => $type]);

        $report = GeneratedReport::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => 'annual_report',
            'params'    => ['tenant_id' => $tenantId, 'year' => $year, 'benef_status' => $benefStatus, 'type' => $type, 'org_name' => ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL', 'emitter' => auth()->user()->name ?? '—'],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json(['report_id' => $report->id, 'status_url' => route('reports.status', $report->id)]);
    }

    public function annualReportPdfAppendix(Request $request)
    {
        $tenantId    = auth()->user()->tenant_id;
        $year        = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');
        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type        = trim((string) $request->get('type', ''));

        AuditDownload::log('Beneficiaries:AnnualReport', null, ['format' => 'pdf', 'variant' => 'appendix', 'year' => $year, 'benef_status' => $benefStatus, 'type' => $type]);

        $report = GeneratedReport::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => 'annual_report_appendix',
            'params'    => ['tenant_id' => $tenantId, 'year' => $year, 'benef_status' => $benefStatus, 'type' => $type, 'org_name' => ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL', 'emitter' => auth()->user()->name ?? '—'],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json(['report_id' => $report->id, 'status_url' => route('reports.status', $report->id)]);
    }

    public function annualReportExportCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        AuditDownload::log('Beneficiaries:AnnualReport', null, [
            'format' => 'csv',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $type,
            'q' => $q,
        ]);

        $filename = 'relatorio-social-detalhado-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $from, $to, $benefStatus, $type, $q) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Beneficiário', 'Status', 'NIS', 'CPF', 'Data', 'Tipo', 'Descrição', 'Registrado por']);

            $baseQ = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
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

            if ($benefStatus !== '') $baseQ->where('b.status', $benefStatus);
            if ($type !== '') $baseQ->where('a.type', $type);
            if ($q !== '') {
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
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function annualReportExportGroupedCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type = trim((string) $request->get('type', ''));

        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        AuditDownload::log('Beneficiaries:AnnualReport:Grouped', null, [
            'format' => 'csv',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $type,
            'group' => 'month_type_user',
        ]);

        $filename = 'relatorio-social-agrupado-mes-tipo-tecnico-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $from, $to, $benefStatus, $type, $year) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ano', 'Mês', 'Tipo', 'Técnico/Usuário', 'Atendimentos', 'Famílias únicas']);

            $q = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
                ->select([
                    DB::raw((int) $year . ' as year'),
                    DB::raw('MONTH(a.date) as month'),
                    'a.type',
                    DB::raw("COALESCE(u.name, 'Sistema') as user_name"),
                    DB::raw('COUNT(*) as attendances'),
                    DB::raw('COUNT(DISTINCT a.beneficiary_id) as unique_families'),
                ])
                ->groupBy(DB::raw('MONTH(a.date)'), 'a.type', DB::raw("COALESCE(u.name, 'Sistema')"))
                ->orderBy('month')
                ->orderBy('a.type')
                ->orderBy('user_name');

            if ($benefStatus !== '') $q->where('b.status', $benefStatus);
            if ($type !== '') $q->where('a.type', $type);

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
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function annualReportExportGroupedSimpleCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type = trim((string) $request->get('type', ''));

        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        AuditDownload::log('Beneficiaries:AnnualReport:Grouped', null, [
            'format' => 'csv',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $type,
            'group' => 'month_type',
        ]);

        $filename = 'relatorio-social-agrupado-mes-tipo-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $from, $to, $benefStatus, $type, $year) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ano', 'Mês', 'Tipo', 'Atendimentos', 'Famílias únicas']);

            $q = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
                ->select([
                    DB::raw((int) $year . ' as year'),
                    DB::raw('MONTH(a.date) as month'),
                    'a.type',
                    DB::raw('COUNT(*) as attendances'),
                    DB::raw('COUNT(DISTINCT a.beneficiary_id) as unique_families'),
                ])
                ->groupBy(DB::raw('MONTH(a.date)'), 'a.type')
                ->orderBy('month')
                ->orderBy('a.type');

            if ($benefStatus !== '') $q->where('b.status', $benefStatus);
            if ($type !== '') $q->where('a.type', $type);

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
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function annualReportExportPivotTypeCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $typeFilter = trim((string) $request->get('type', ''));

        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        AuditDownload::log('Beneficiaries:AnnualReport:Pivot', null, [
            'format' => 'csv',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $typeFilter,
            'pivot' => 'type',
        ]);

        $filename = 'relatorio-social-pivot-tipo-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $year, $from, $to, $benefStatus, $typeFilter) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            $header = ['Tipo'];
            for ($m = 1; $m <= 12; $m++) $header[] = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $header[] = 'Total';
            $header[] = 'Famílias únicas (ano)';
            fputcsv($out, $header);

            $q = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
                ->select('a.type', DB::raw('MONTH(a.date) as m'), DB::raw('COUNT(*) as c'))
                ->groupBy('a.type', DB::raw('MONTH(a.date)'))
                ->orderBy('a.type');
            $uq = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
                ->select('a.type', DB::raw('COUNT(DISTINCT a.beneficiary_id) as uf'))
                ->groupBy('a.type')
                ->orderBy('a.type');
            if ($benefStatus !== '') { $q->where('b.status', $benefStatus); $uq->where('b.status', $benefStatus); }
            if ($typeFilter !== '') { $q->where('a.type', $typeFilter); $uq->where('a.type', $typeFilter); }

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
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function annualReportExportPivotUserCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $typeFilter = trim((string) $request->get('type', ''));

        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        AuditDownload::log('Beneficiaries:AnnualReport:Pivot', null, [
            'format' => 'csv',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $typeFilter,
            'pivot' => 'user',
        ]);

        $filename = 'relatorio-social-pivot-equipe-' . $year . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $year, $from, $to, $benefStatus, $typeFilter) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            $header = ['Técnico/Usuário'];
            for ($m = 1; $m <= 12; $m++) $header[] = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $header[] = 'Total';
            $header[] = 'Famílias únicas (ano)';
            fputcsv($out, $header);

            $q = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
                ->select(DB::raw("COALESCE(u.name, 'Sistema') as user_name"), DB::raw('MONTH(a.date) as m'), DB::raw('COUNT(*) as c'))
                ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"), DB::raw('MONTH(a.date)'))
                ->orderBy('user_name');

            $uq = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->whereBetween('a.date', [$from, $to])
                ->select(DB::raw("COALESCE(u.name, 'Sistema') as user_name"), DB::raw('COUNT(DISTINCT a.beneficiary_id) as uf'))
                ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"))
                ->orderBy('user_name');

            if ($benefStatus !== '') { $q->where('b.status', $benefStatus); $uq->where('b.status', $benefStatus); }
            if ($typeFilter !== '') { $q->where('a.type', $typeFilter); $uq->where('a.type', $typeFilter); }

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
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
