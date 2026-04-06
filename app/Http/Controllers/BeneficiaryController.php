<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Attendance;
use App\Models\FamilyMember;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AuditDownload;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BeneficiaryController extends Controller
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
        $tenantId = auth()->user()->tenant_id;
        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type = trim((string) $request->get('type', ''));

        AuditDownload::log('Beneficiaries:AnnualReport', null, [
            'format' => 'pdf',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $type,
        ]);

        // Reuse annualReport calculations by calling method logic (inline for simplicity)
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

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');
        $emitter = auth()->user()->name ?? '—';

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.annual_report_pdf', compact(
            'year',
            'from',
            'to',
            'benefStatus',
            'type',
            'totalAttendances',
            'uniqueFamilies',
            'monthly',
            'byType',
            'byUser',
            'topFamilies',
            'orgName',
            'generatedAt',
            'emitter'
        ));

        $filename = 'relatorio-social-' . $year . '-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function annualReportPdfAppendix(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $year = (int) $request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > ((int) date('Y') + 2)) $year = (int) date('Y');

        $benefStatus = trim((string) $request->get('benef_status', ''));
        $type = trim((string) $request->get('type', ''));

        AuditDownload::log('Beneficiaries:AnnualReport', null, [
            'format' => 'pdf',
            'variant' => 'appendix',
            'year' => $year,
            'benef_status' => $benefStatus,
            'type' => $type,
        ]);

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

        $details = (clone $base)
            ->select([
                'a.date',
                'a.type',
                'a.description',
                'b.name as beneficiary_name',
                'b.status as beneficiary_status',
                DB::raw("COALESCE(u.name, 'Sistema') as user_name"),
            ])
            ->orderByDesc('a.date')
            ->orderByDesc('a.id')
            ->limit(200)
            ->get();

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');
        $emitter = auth()->user()->name ?? '—';

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.annual_report_pdf_appendix', compact(
            'year',
            'from',
            'to',
            'benefStatus',
            'type',
            'totalAttendances',
            'uniqueFamilies',
            'monthly',
            'byType',
            'byUser',
            'topFamilies',
            'details',
            'orgName',
            'generatedAt',
            'emitter'
        ));

        $filename = 'relatorio-social-' . $year . '-com-anexos-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
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
                      ->orWhere('b.name', 'like', '%' . $q . '%')
                      ->orWhere('b.nis', 'like', '%' . $q . '%')
                      ->orWhere('b.cpf', 'like', '%' . $q . '%');
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

    public function insights(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $from = $request->get('from');
        $to = $request->get('to');

        $fromDt = !empty($from) ? Carbon::parse($from)->startOfDay() : now()->subDays(90)->startOfDay();
        $toDt = !empty($to) ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        // KPI: beneficiaries
        $statsRows = DB::table('beneficiaries')
            ->where('tenant_id', $tenantId)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get();
        $totalBenef = (int) $statsRows->sum('c');
        $activeBenef = (int) ($statsRows->firstWhere('status', 'active')->c ?? 0);
        $inactiveBenef = (int) ($statsRows->firstWhere('status', 'inactive')->c ?? 0);
        $graduatedBenef = (int) ($statsRows->firstWhere('status', 'graduated')->c ?? 0);

        // KPI: attendances in range
        $attBase = DB::table('attendances')->where('tenant_id', $tenantId)->whereBetween('date', [$fromDt->toDateString(), $toDt->toDateString()]);
        $totalAttendances = (int) (clone $attBase)->count();
        $uniqueBeneficiariesAttended = (int) (clone $attBase)->distinct('beneficiary_id')->count('beneficiary_id');

        // Monthly series (last 12 months)
        $startMonth = now()->startOfMonth()->subMonths(11);
        $endMonth = now()->endOfMonth();
        $monthlyRows = DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->select(DB::raw('YEAR(date) as y'), DB::raw('MONTH(date) as m'), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTH(date)'))
            ->orderBy('y')->orderBy('m')
            ->get();

        $monthly = [];
        $cursor = $startMonth->copy();
        while ($cursor <= $endMonth) {
            $key = $cursor->format('Y-m');
            $monthly[$key] = 0;
            $cursor->addMonth();
        }
        foreach ($monthlyRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (array_key_exists($key, $monthly)) $monthly[$key] = (int) $r->c;
        }

        $topTypes = DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$fromDt->toDateString(), $toDt->toDateString()])
            ->select('type', DB::raw('COUNT(*) as c'))
            ->groupBy('type')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topUsers = DB::table('attendances as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.tenant_id', $tenantId)
            ->whereBetween('a.date', [$fromDt->toDateString(), $toDt->toDateString()])
            ->select(DB::raw("COALESCE(u.name, 'Sistema') as name"), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"))
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topFamilies = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->where('a.tenant_id', $tenantId)
            ->where('b.tenant_id', $tenantId)
            ->whereBetween('a.date', [$fromDt->toDateString(), $toDt->toDateString()])
            ->select('b.id', 'b.name', 'b.status', DB::raw('COUNT(*) as c'))
            ->groupBy('b.id', 'b.name', 'b.status')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $kpis = compact(
            'totalBenef',
            'activeBenef',
            'inactiveBenef',
            'graduatedBenef',
            'totalAttendances',
            'uniqueBeneficiariesAttended'
        );

        return view('ngo.beneficiaries.insights', compact(
            'from',
            'to',
            'fromDt',
            'toDt',
            'kpis',
            'monthly',
            'topTypes',
            'topUsers',
            'topFamilies'
        ));
    }

    public function exportAllAttendancesCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));
        $benefStatus = trim((string) $request->get('benef_status', ''));

        AuditDownload::log('Beneficiaries:Attendances:All', null, [
            'format' => 'csv',
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'q' => $q,
            'benef_status' => $benefStatus,
        ]);

        $filename = 'atendimentos-ong-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $from, $to, $type, $q, $benefStatus) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Beneficiário', 'Status', 'NIS', 'CPF', 'Data', 'Tipo', 'Descrição', 'Registrado por']);

            $baseQ = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
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

            if (!empty($from)) $baseQ->whereDate('a.date', '>=', $from);
            if (!empty($to)) $baseQ->whereDate('a.date', '<=', $to);
            if ($type !== '') $baseQ->where('a.type', $type);
            if ($benefStatus !== '') $baseQ->where('b.status', $benefStatus);
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('a.description', 'like', '%' . $q . '%')
                      ->orWhere('a.type', 'like', '%' . $q . '%')
                      ->orWhere('b.name', 'like', '%' . $q . '%')
                      ->orWhere('b.nis', 'like', '%' . $q . '%')
                      ->orWhere('b.cpf', 'like', '%' . $q . '%');
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

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $beneficiariesQ = Beneficiary::where('tenant_id', $tenantId)
            ->withCount('attendances')
            ->orderBy('name');

        if ($q !== '') {
            $beneficiariesQ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('cpf', 'like', '%' . $q . '%')
                  ->orWhere('nis', 'like', '%' . $q . '%')
                  ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }
        if ($status !== '') {
            $beneficiariesQ->where('status', $status);
        }

        $beneficiaries = $beneficiariesQ->paginate(15)->appends($request->query());

        $statsRows = DB::table('beneficiaries')
            ->where('tenant_id', $tenantId)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get();

        $total = (int) $statsRows->sum('c');
        $active = (int) ($statsRows->firstWhere('status', 'active')->c ?? 0);
        $inactive = (int) ($statsRows->firstWhere('status', 'inactive')->c ?? 0);
        $graduated = (int) ($statsRows->firstWhere('status', 'graduated')->c ?? 0);

        $monthAttendances = (int) DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereYear('date', date('Y'))
            ->whereMonth('date', date('m'))
            ->count();

        $stats = compact('total', 'active', 'inactive', 'graduated', 'monthAttendances');
                                    
        return view('ngo.beneficiaries.index', compact('beneficiaries', 'q', 'status', 'stats'));
    }

    public function create()
    {
        return view('ngo.beneficiaries.create');
    }

    public function show(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $beneficiary = Beneficiary::where('tenant_id', $tenantId)
                                  ->where('id', $id)
                                  ->with(['familyMembers'])
                                  ->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        $attendancesQ = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->with('user');

        if (!empty($from)) $attendancesQ->whereDate('date', '>=', $from);
        if (!empty($to)) $attendancesQ->whereDate('date', '<=', $to);
        if ($type !== '') $attendancesQ->where('type', $type);
        if ($q !== '') {
            $attendancesQ->where(function ($w) use ($q) {
                $w->where('description', 'like', '%' . $q . '%')
                  ->orWhere('type', 'like', '%' . $q . '%');
            });
        }

        $attendances = $attendancesQ
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $stats = [
            'attendances_total' => (int) Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->count(),
            'last_attendance_at' => Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->max('date'),
        ];

        $types = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();

        return view('ngo.beneficiaries.show', compact('beneficiary', 'attendances', 'stats', 'from', 'to', 'type', 'q', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if (isset($data['cpf'])) $data['cpf'] = preg_replace('/\D+/', '', (string) $data['cpf']);
        if (isset($data['nis'])) $data['nis'] = preg_replace('/\D+/', '', (string) $data['nis']);
        if (isset($data['phone'])) $data['phone'] = trim((string) $data['phone']);

        $tenantId = auth()->user()->tenant_id;
        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => 'required|string|max:255',
            'cpf' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('beneficiaries', 'cpf')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'nis' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('beneficiaries', 'nis')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,graduated',
        ])->validate();

        $beneficiary = new Beneficiary($validated);
        $beneficiary->tenant_id = $tenantId;
        $beneficiary->save();

        return redirect('/ngo/beneficiaries')->with('success', 'Beneficiário cadastrado com sucesso!');
    }

    public function storeAttendance(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;

        // Ensure beneficiary belongs to current tenant (critical for multi-tenant safety)
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'description' => 'required|string'
        ]);

        $attendance = new Attendance($validated);
        $attendance->tenant_id = $tenantId;
        $attendance->beneficiary_id = $beneficiary->id;
        $attendance->user_id = auth()->id();
        $attendance->save();

        return redirect()->back()->with('success', 'Atendimento registrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $data = $request->all();
        if (isset($data['cpf'])) $data['cpf'] = preg_replace('/\D+/', '', (string) $data['cpf']);
        if (isset($data['nis'])) $data['nis'] = preg_replace('/\D+/', '', (string) $data['nis']);
        if (isset($data['phone'])) $data['phone'] = trim((string) $data['phone']);

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => 'required|string|max:255',
            'cpf' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('beneficiaries', 'cpf')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($beneficiary->id),
            ],
            'nis' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('beneficiaries', 'nis')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($beneficiary->id),
            ],
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,graduated',
        ])->validate();

        $beneficiary->fill($validated);
        $beneficiary->save();

        return redirect()->back()->with('success', 'Cadastro atualizado.');
    }

    public function destroy($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();
        $beneficiary->delete();
        return redirect('/ngo/beneficiaries')->with('success', 'Beneficiário removido.');
    }

    public function updateAttendance(Request $request, $id, $attendanceId)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $attendance = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
        ]);

        $attendance->fill($validated);
        $attendance->save();

        return redirect()->back()->with('success', 'Atendimento atualizado.');
    }

    public function destroyAttendance($id, $attendanceId)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $attendance = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $attendance->delete();

        return redirect()->back()->with('success', 'Atendimento removido.');
    }

    public function exportAttendanceCsv(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        AuditDownload::log('Beneficiaries:Attendances', (int) $beneficiary->id, [
            'format' => 'csv',
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'q' => $q,
        ]);

        $filename = 'atendimentos-' . \Illuminate\Support\Str::slug($beneficiary->name) . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $beneficiary, $from, $to, $type, $q) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Tipo', 'Descrição', 'Registrado por']);

            $baseQ = Attendance::where('tenant_id', $tenantId)
                ->where('beneficiary_id', $beneficiary->id)
                ->with('user');

            if (!empty($from)) $baseQ->whereDate('date', '>=', $from);
            if (!empty($to)) $baseQ->whereDate('date', '<=', $to);
            if ($type !== '') $baseQ->where('type', $type);
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('description', 'like', '%' . $q . '%')
                      ->orWhere('type', 'like', '%' . $q . '%');
                });
            }

            $baseQ->orderBy('date', 'desc')->orderBy('id', 'desc')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        optional($a->date)->format('Y-m-d'),
                        $a->type,
                        $a->description,
                        $a->user->name ?? 'Sistema',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function printAttendance(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        $baseQ = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->with('user')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($from)) $baseQ->whereDate('date', '>=', $from);
        if (!empty($to)) $baseQ->whereDate('date', '<=', $to);
        if ($type !== '') $baseQ->where('type', $type);
        if ($q !== '') {
            $baseQ->where(function ($w) use ($q) {
                $w->where('description', 'like', '%' . $q . '%')
                  ->orWhere('type', 'like', '%' . $q . '%');
            });
        }

        $attendances = $baseQ->get();

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');

        return view('ngo.beneficiaries.attendance_print', compact(
            'beneficiary',
            'attendances',
            'orgName',
            'generatedAt',
            'from',
            'to',
            'type',
            'q'
        ));
    }

    public function pdf(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with('familyMembers')
            ->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        $attQ = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->with('user')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($from)) $attQ->whereDate('date', '>=', $from);
        if (!empty($to)) $attQ->whereDate('date', '<=', $to);
        if ($type !== '') $attQ->where('type', $type);
        if ($q !== '') {
            $attQ->where(function ($w) use ($q) {
                $w->where('description', 'like', '%' . $q . '%')
                  ->orWhere('type', 'like', '%' . $q . '%');
            });
        }

        // Keep PDF lightweight (MVP): last 100 records
        $attendances = $attQ->limit(100)->get();

        $stats = [
            'attendances_total' => (int) Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->count(),
            'last_attendance_at' => Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->max('date'),
        ];

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');
        $emitter = auth()->user()->name ?? '—';

        AuditDownload::log('Beneficiaries:Term', (int) $beneficiary->id, [
            'format' => 'pdf',
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'q' => $q,
        ]);

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.term_pdf', compact(
            'beneficiary',
            'attendances',
            'stats',
            'orgName',
            'generatedAt',
            'emitter',
            'from',
            'to',
            'type',
            'q'
        ));

        $filename = 'ficha-beneficiario-' . Str::slug($beneficiary->name) . '-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function storeFamilyMember(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kinship' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
        ]);

        $member = new FamilyMember($validated);
        $member->beneficiary_id = $beneficiary->id;
        $member->save();

        // Manual audit (family_members doesn't carry tenant_id)
        try {
            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'event' => 'created',
                'auditable_type' => FamilyMember::class,
                'auditable_id' => $member->id,
                'old_values' => null,
                'new_values' => [
                    'beneficiary_id' => $beneficiary->id,
                    'name' => $member->name,
                    'kinship' => $member->kinship,
                    'birth_date' => optional($member->birth_date)->format('Y-m-d'),
                ],
                'ip_address' => RequestFacade::ip(),
                'user_agent' => RequestFacade::userAgent(),
                'url' => RequestFacade::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', 'Familiar adicionado com sucesso!');
    }

    public function destroyFamilyMember($id, $memberId)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $member = FamilyMember::where('beneficiary_id', $beneficiary->id)->where('id', $memberId)->firstOrFail();
        $old = $member->toArray();
        $member->delete();

        try {
            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'event' => 'deleted',
                'auditable_type' => FamilyMember::class,
                'auditable_id' => (int) $memberId,
                'old_values' => $old,
                'new_values' => null,
                'ip_address' => RequestFacade::ip(),
                'user_agent' => RequestFacade::userAgent(),
                'url' => RequestFacade::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', 'Familiar removido.');
    }

    public function exportCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        AuditDownload::log('Beneficiaries', null, [
            'format' => 'csv',
            'q' => $q,
            'status' => $status,
        ]);

        $filename = 'beneficiarios-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $q, $status) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'NIS', 'CPF', 'Nascimento', 'Telefone', 'Endereço', 'Status', 'Atendimentos']);

            $baseQ = Beneficiary::where('tenant_id', $tenantId)->withCount('attendances')->orderBy('name');
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%' . $q . '%')
                      ->orWhere('cpf', 'like', '%' . $q . '%')
                      ->orWhere('nis', 'like', '%' . $q . '%')
                      ->orWhere('phone', 'like', '%' . $q . '%');
                });
            }
            if ($status !== '') $baseQ->where('status', $status);

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $b) {
                    fputcsv($out, [
                        $b->name,
                        $b->nis,
                        $b->cpf,
                        optional($b->birth_date)->format('Y-m-d'),
                        $b->phone,
                        $b->address,
                        $b->status,
                        $b->attendances_count,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $beneficiariesQ = Beneficiary::where('tenant_id', $tenantId)->withCount('attendances')->orderBy('name');
        if ($q !== '') {
            $beneficiariesQ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('cpf', 'like', '%' . $q . '%')
                  ->orWhere('nis', 'like', '%' . $q . '%')
                  ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }
        if ($status !== '') $beneficiariesQ->where('status', $status);

        $beneficiaries = $beneficiariesQ->get();

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');

        return view('ngo.beneficiaries.print', compact('beneficiaries', 'orgName', 'generatedAt', 'q', 'status'));
    }
}
