<?php

namespace App\Jobs;

use App\Models\GeneratedReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;
    public int $timeout = 300;

    public function __construct(private int $reportId) {}

    public function handle(): void
    {
        $report = GeneratedReport::find($this->reportId);
        if (!$report) return;

        try {
            match ($report->type) {
                'dre_pdf'                   => $this->renderDrePdf($report),
                'annual_report'             => $this->renderAnnualReport($report),
                'annual_report_appendix'    => $this->renderAnnualReportAppendix($report),
                'beneficiary_term'          => $this->renderBeneficiaryTerm($report),
                'project_report'            => $this->renderProjectReport($report),
                default                     => throw new \InvalidArgumentException("Unknown PDF type: {$report->type}"),
            };
        } catch (\Throwable $e) {
            Log::error("GeneratePdfJob #{$this->reportId} ({$report->type}): {$e->getMessage()}");
            $report->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    // ─── DRE PDF ─────────────────────────────────────────────────────────────

    private function renderDrePdf(GeneratedReport $report): void
    {
        $p        = $report->params;
        $tenantId = $p['tenant_id'];
        $year     = (int) $p['year'];
        $orgName  = $p['org_name'];
        $from     = $p['from'] ?? "$year-01-01";
        $to       = $p['to']   ?? "$year-12-31";
        $categoryId = $p['category_id'] ?? null;

        $q = \App\Models\Transaction::where('tenant_id', $tenantId)
            ->whereBetween('date', [$from, $to])
            ->where('status', 'paid')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id');

        if ($categoryId) {
            $q->where('category_id', $categoryId);
        }

        $sumsByCat = $q->pluck('total', 'category_id');

        $incomeCategories  = \App\Models\FinancialCategory::where('tenant_id', $tenantId)->where('type', 'income')->get();
        $expenseCategories = \App\Models\FinancialCategory::where('tenant_id', $tenantId)->where('type', 'expense')->get();

        $totalIncome = 0;
        $incomesArr  = [];
        foreach ($incomeCategories as $cat) {
            $val = (float) ($sumsByCat[$cat->id] ?? 0);
            if ($val > 0) { $incomesArr[] = ['name' => $cat->name, 'value' => $val]; $totalIncome += $val; }
        }
        $expensesArr  = [];
        $totalExpense = 0;
        foreach ($expenseCategories as $cat) {
            $val = (float) ($sumsByCat[$cat->id] ?? 0);
            if ($val > 0) { $expensesArr[] = ['name' => $cat->name, 'value' => $val]; $totalExpense += $val; }
        }

        $result      = $totalIncome - $totalExpense;
        $periodLabel = Carbon::parse($from)->format('d/m/Y') . ' a ' . Carbon::parse($to)->format('d/m/Y');
        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.reports.dre_pdf', [
            'year'         => $year,
            'incomes'      => $incomesArr,
            'totalIncome'  => $totalIncome,
            'expenses'     => $expensesArr,
            'totalExpense' => $totalExpense,
            'result'       => $result,
            'orgName'      => $orgName,
            'periodLabel'  => $periodLabel,
            'generatedAt'  => $generatedAt,
        ]);

        $filename = 'dre-' . $year . '-' . now()->format('Y-m-d_His') . '.pdf';
        $this->savePdf($report, $pdf, $filename);
    }

    // ─── Annual Report PDF ────────────────────────────────────────────────────

    private function renderAnnualReport(GeneratedReport $report): void
    {
        $p          = $report->params;
        $tenantId   = $p['tenant_id'];
        $year       = (int) $p['year'];
        $benefStatus = $p['benef_status'] ?? '';
        $type        = $p['type'] ?? '';
        $orgName     = $p['org_name'];
        $emitter     = $p['emitter'];

        [$monthly, $byType, $byUser, $topFamilies, $totalAttendances, $uniqueFamilies, $from, $to] =
            $this->buildAnnualReportData($tenantId, $year, $benefStatus, $type);

        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.annual_report_pdf', compact(
            'year', 'from', 'to', 'benefStatus', 'type',
            'totalAttendances', 'uniqueFamilies', 'monthly', 'byType',
            'byUser', 'topFamilies', 'orgName', 'generatedAt', 'emitter'
        ));

        $filename = 'relatorio-social-' . $year . '-' . now()->format('Y-m-d_His') . '.pdf';
        $this->savePdf($report, $pdf, $filename);
    }

    // ─── Annual Report PDF + Appendix ─────────────────────────────────────────

    private function renderAnnualReportAppendix(GeneratedReport $report): void
    {
        $p          = $report->params;
        $tenantId   = $p['tenant_id'];
        $year       = (int) $p['year'];
        $benefStatus = $p['benef_status'] ?? '';
        $type        = $p['type'] ?? '';
        $orgName     = $p['org_name'];
        $emitter     = $p['emitter'];

        [$monthly, $byType, $byUser, $topFamilies, $totalAttendances, $uniqueFamilies, $from, $to] =
            $this->buildAnnualReportData($tenantId, $year, $benefStatus, $type);

        $base = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.tenant_id', $tenantId)
            ->where('b.tenant_id', $tenantId)
            ->whereBetween('a.date', [$from, $to]);
        if ($benefStatus !== '') $base->where('b.status', $benefStatus);
        if ($type !== '') $base->where('a.type', $type);

        $details = (clone $base)
            ->select(['a.date', 'a.type', 'a.description', 'b.name as beneficiary_name',
                      'b.status as beneficiary_status', DB::raw("COALESCE(u.name, 'Sistema') as user_name")])
            ->orderByDesc('a.date')->orderByDesc('a.id')
            ->limit(200)
            ->get();

        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.annual_report_pdf_appendix', compact(
            'year', 'from', 'to', 'benefStatus', 'type',
            'totalAttendances', 'uniqueFamilies', 'monthly', 'byType',
            'byUser', 'topFamilies', 'details', 'orgName', 'generatedAt', 'emitter'
        ));

        $filename = 'relatorio-social-' . $year . '-com-anexos-' . now()->format('Y-m-d_His') . '.pdf';
        $this->savePdf($report, $pdf, $filename);
    }

    // ─── Beneficiary Term PDF ─────────────────────────────────────────────────

    private function renderBeneficiaryTerm(GeneratedReport $report): void
    {
        $p            = $report->params;
        $tenantId     = $p['tenant_id'];
        $beneficiaryId = $p['beneficiary_id'];
        $from          = $p['from'];
        $to            = $p['to'];
        $type          = $p['type'] ?? '';
        $q             = $p['q'] ?? '';
        $orgName       = $p['org_name'];
        $emitter       = $p['emitter'];

        $beneficiary = \App\Models\Beneficiary::where('tenant_id', $tenantId)->findOrFail($beneficiaryId);

        $attQ = \App\Models\Attendance::where('beneficiary_id', $beneficiary->id)
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date', 'desc');
        if ($type) $attQ->where('type', $type);
        if ($q) {
            $attQ->where(function ($w) use ($q) {
                $w->where('description', 'like', "%{$q}%")->orWhere('type', 'like', "%{$q}%");
            });
        }
        $attendances = $attQ->limit(100)->get();

        $stats = [
            'attendances_total'  => (int) \App\Models\Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->count(),
            'last_attendance_at' => \App\Models\Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->max('date'),
        ];

        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.term_pdf', compact(
            'beneficiary', 'attendances', 'stats',
            'orgName', 'generatedAt', 'emitter', 'from', 'to', 'type', 'q'
        ));

        $filename = 'ficha-beneficiario-' . Str::slug($beneficiary->name) . '-' . now()->format('Y-m-d_His') . '.pdf';
        $this->savePdf($report, $pdf, $filename);
    }

    // ─── Project Report PDF ───────────────────────────────────────────────────

    private function renderProjectReport(GeneratedReport $report): void
    {
        $p         = $report->params;
        $tenantId  = $p['tenant_id'];
        $projectId = $p['project_id'];

        $project = \App\Models\Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        $totalIncome  = (float) \App\Models\Transaction::where('tenant_id', $tenantId)->where('project_id', $projectId)->where('type', 'income')->sum('amount');
        $totalExpense = (float) \App\Models\Transaction::where('tenant_id', $tenantId)->where('project_id', $projectId)->where('type', 'expense')->sum('amount');
        $totalSpent   = $totalExpense;
        $percentUsed  = ($project->budget > 0) ? min(100, round(($totalSpent / $project->budget) * 100)) : 0;

        $tasks = \App\Models\Task::where('project_id', $projectId)->where('tenant_id', $tenantId)
            ->orderByRaw("FIELD(status, 'todo', 'in_progress', 'review', 'done', 'completed')")
            ->get();
        $taskStats = [
            'total'       => $tasks->count(),
            'done'        => $tasks->whereIn('status', ['done', 'completed'])->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'todo'        => $tasks->where('status', 'todo')->count(),
            'overdue'     => $tasks->filter(fn($t) => $t->due_date && Carbon::parse($t->due_date)->isPast() && !in_array($t->status, ['done','completed']))->count(),
        ];
        $taskStats['progress'] = $taskStats['total'] > 0 ? round(($taskStats['done'] / $taskStats['total']) * 100) : 0;

        $members = \App\Models\ProjectMember::where('project_id', $projectId)->where('tenant_id', $tenantId)->with('user:id,name,email,role')->get();
        $transactions = \App\Models\Transaction::where('tenant_id', $tenantId)->where('project_id', $projectId)->orderBy('date', 'desc')->limit(20)->get();
        $tenant = \App\Models\Tenant::find($tenantId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('projects.report_pdf', compact(
            'project', 'totalSpent', 'percentUsed', 'tasks', 'taskStats', 'members', 'transactions', 'tenant'
        ));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Relatorio_' . Str::slug($project->name) . '_' . now()->format('Y-m-d') . '.pdf';
        $this->savePdf($report, $pdf, $filename);
    }

    // ─── Shared helpers ───────────────────────────────────────────────────────

    private function buildAnnualReportData(int $tenantId, int $year, string $benefStatus, string $type): array
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to   = Carbon::create($year, 12, 31)->toDateString();

        $base = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.tenant_id', $tenantId)
            ->where('b.tenant_id', $tenantId)
            ->whereBetween('a.date', [$from, $to]);
        if ($benefStatus !== '') $base->where('b.status', $benefStatus);
        if ($type !== '') $base->where('a.type', $type);

        $totalAttendances = (int) (clone $base)->count();
        $uniqueFamilies   = (int) (clone $base)->distinct('a.beneficiary_id')->count('a.beneficiary_id');

        $monthlyRows = (clone $base)->select(DB::raw('MONTH(a.date) as m'), DB::raw('COUNT(*) as c'))->groupBy(DB::raw('MONTH(a.date)'))->orderBy('m')->get();
        $monthly = array_fill(1, 12, 0);
        foreach ($monthlyRows as $r) $monthly[(int) $r->m] = (int) $r->c;

        $byType      = (clone $base)->select('a.type', DB::raw('COUNT(*) as c'))->groupBy('a.type')->orderByDesc('c')->limit(15)->get();
        $byUser      = (clone $base)->select(DB::raw("COALESCE(u.name, 'Sistema') as name"), DB::raw('COUNT(*) as c'))->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"))->orderByDesc('c')->limit(15)->get();
        $topFamilies = (clone $base)->select('b.id', 'b.name', 'b.status', DB::raw('COUNT(*) as c'))->groupBy('b.id', 'b.name', 'b.status')->orderByDesc('c')->limit(15)->get();

        return [$monthly, $byType, $byUser, $topFamilies, $totalAttendances, $uniqueFamilies, $from, $to];
    }

    private function savePdf(GeneratedReport $report, $pdf, string $filename): void
    {
        $dir  = "reports/{$report->tenant_id}";
        $path = "{$dir}/{$filename}";

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->put($path, $pdf->output());

        $report->update([
            'status'    => 'done',
            'file_path' => $path,
            'filename'  => $filename,
        ]);
    }
}
