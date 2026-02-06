<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransparencyPortal;
use App\Models\BoardMember;
use App\Models\TransparencyDocument;
use App\Models\PublicPartnership;
use App\Models\Transaction;
use App\Models\Beneficiary;
use App\Models\Employee;
use App\Models\Asset;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransparencyController extends Controller
{
    private static function dompdfPdfDateString(Carbon $dt): string
    {
        // Dompdf expects a PDF date like: D:YYYYMMDDHHMMSS+00'00'
        $dt = $dt->copy()->utc();
        $tz = $dt->format('O'); // +0000
        $tz = substr_replace($tz, "'", -2, 0) . "'"; // +00'00'
        return 'D:' . $dt->format('YmdHis') . $tz;
    }

    private static function dompdfStableInfo(string $title, ?Carbon $updatedAt = null): array
    {
        $dt = $updatedAt ? $updatedAt->copy() : now()->startOfYear();
        $pdfDate = self::dompdfPdfDateString($dt);

        return [
            // Force deterministic dates so PDF hash is stable.
            'CreationDate' => $pdfDate,
            'ModDate' => $pdfDate,
            // Helpful identifiers (stable).
            'Title' => $title,
            'Creator' => 'Vivensi Portal da Transparência',
        ];
    }

    private static function buildOpenDataCsvContent(string $portalSlug, int $year, $monthly, $expenseByCategory): string
    {
        $out = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        // Hint delimiter to Excel
        fwrite($out, "sep=;\n");
        // Use semicolon as delimiter (common in PT-BR spreadsheets)
        fputcsv($out, ['portal_slug', 'dataset', 'year', 'month', 'type', 'category', 'total'], ';');

        foreach ($monthly as $r) {
            fputcsv($out, [
                (string) $portalSlug,
                'monthly_totals',
                $year,
                (int) ($r->m ?? 0),
                (string) ($r->type ?? ''),
                '',
                number_format((float) ($r->total ?? 0), 2, '.', ''),
            ], ';');
        }

        foreach ($expenseByCategory as $r) {
            fputcsv($out, [
                (string) $portalSlug,
                'expense_by_category',
                $year,
                '',
                'expense',
                (string) ($r->category ?? 'Sem categoria'),
                number_format((float) ($r->total ?? 0), 2, '.', ''),
            ], ';');
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return (string) $csv;
    }

    /**
     * NGO Dashboard for Transparency Configuration
     */
    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;

        // Slug must be unique across ALL tenants (DB constraint).
        $base = Str::slug(auth()->user()->name ?? '') ?: 'ong';
        $defaultSlug = $base . '-' . $tenant_id;

        $portal = TransparencyPortal::firstOrCreate(['tenant_id' => $tenant_id], [
            'slug' => $defaultSlug,
            'title' => 'Portal da Transparência',
            'is_published' => false
        ]);

        $board = BoardMember::where('tenant_id', $tenant_id)->get();
        $docs = TransparencyDocument::where('tenant_id', $tenant_id)->get();
        $partnerships = PublicPartnership::where('tenant_id', $tenant_id)->get();

        return view('ngo.transparency.index', compact('portal', 'board', 'docs', 'partnerships'));
    }

    public function updatePortal(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $portal = TransparencyPortal::where('tenant_id', $tenant_id)->firstOrFail();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:30',
            'mission' => 'nullable|string|max:2000',
            'vision' => 'nullable|string|max:2000',
            'values' => 'nullable|string|max:2000',
            'sic_email' => 'nullable|email|max:255',
            'sic_phone' => 'nullable|string|max:60',
            'is_published' => 'nullable|boolean',
            // Institutional / LGPD (stored in settings JSON)
            'dpo_name' => 'nullable|string|max:255',
            'dpo_email' => 'nullable|email|max:255',
            'dpo_phone' => 'nullable|string|max:60',
            'privacy_policy' => 'nullable|string|max:5000',
            'legal_basis_note' => 'nullable|string|max:2000',
            'data_retention_note' => 'nullable|string|max:2000',
        ]);

        // Normalize publish checkbox (HTML sends "on")
        $validated['is_published'] = (bool) ($request->get('is_published', false));

        $clean = function ($v) {
            if ($v === null) return null;
            if (is_string($v)) {
                $v = trim($v);
                return $v === '' ? null : $v;
            }
            return $v;
        };

        $settings = is_array($portal->settings) ? $portal->settings : [];
        $settings['dpo_name'] = $clean($validated['dpo_name'] ?? null);
        $settings['dpo_email'] = $clean($validated['dpo_email'] ?? null);
        $settings['dpo_phone'] = $clean($validated['dpo_phone'] ?? null);
        $settings['privacy_policy'] = $clean($validated['privacy_policy'] ?? null);
        $settings['legal_basis_note'] = $clean($validated['legal_basis_note'] ?? null);
        $settings['data_retention_note'] = $clean($validated['data_retention_note'] ?? null);

        unset(
            $validated['dpo_name'],
            $validated['dpo_email'],
            $validated['dpo_phone'],
            $validated['privacy_policy'],
            $validated['legal_basis_note'],
            $validated['data_retention_note']
        );

        $validated['settings'] = $settings;

        $portal->update($validated);
        
        return back()->with('success', 'Configurações do portal atualizadas!');
    }

    public function addBoardMember(Request $request)
    {
        $request->validate(['name' => 'required', 'position' => 'required']);
        $tenant_id = auth()->user()->tenant_id;
        
        BoardMember::create(array_merge($request->all(), ['tenant_id' => $tenant_id]));
        
        return back()->with('success', 'Membro da diretoria adicionado!');
    }

    public function deleteBoardMember($id)
    {
        BoardMember::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        return back()->with('success', 'Membro removido.');
    }

    public function addDocument(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:80',
            'year' => 'nullable|integer|min:1900|max:2100',
            'document_date' => 'nullable|date',
            'file' => 'required|file|mimes:pdf,jpg,png,zip|max:5120',
        ]);
        $tenant_id = auth()->user()->tenant_id;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('transparency_docs/' . $tenant_id, 'public');
            
            TransparencyDocument::create([
                'tenant_id' => $tenant_id,
                'title' => $request->title,
                'type' => $request->type,
                'file_path' => $path,
                'year' => $request->year,
                'document_date' => $request->document_date
            ]);
        }

        return back()->with('success', 'Documento postado com sucesso!');
    }

    public function deleteDocument($id)
    {
        $doc = TransparencyDocument::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        try {
            if (!empty($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        } catch (\Throwable $e) {
            // ignore storage deletion failures
        }
        $doc->delete();
        return back()->with('success', 'Documento removido.');
    }

    public function addPartnership(Request $request)
    {
        $data = $request->all();
        if (isset($data['value'])) {
            $data['value'] = str_replace('.', '', (string) $data['value']);
            $data['value'] = str_replace(',', '.', (string) $data['value']);
        }

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'agency_name' => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'gazette_link' => 'nullable|string|max:2048',
            'status' => 'nullable|string|max:60',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ])->validate();
        $tenant_id = auth()->user()->tenant_id;
        
        PublicPartnership::create(array_merge($validated, ['tenant_id' => $tenant_id]));
        
        return back()->with('success', 'Parceria registrada!');
    }

    /**
     * Backward compatible public view by tenant_id.
     * Redirects to slug portal if published.
     */
    public function publicView($tenant_id)
    {
        $portal = TransparencyPortal::where('tenant_id', (int) $tenant_id)->where('is_published', true)->firstOrFail();
        return redirect()->route('transparency.portal', ['slug' => $portal->slug]);
    }

    public function downloadDocument($slug, $id)
    {
        $portal = TransparencyPortal::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $doc = TransparencyDocument::where('tenant_id', $portal->tenant_id)->findOrFail($id);

        if (empty($doc->file_path) || !Storage::disk('public')->exists($doc->file_path)) {
            abort(404);
        }

        // Public download audit
        try {
            AuditLog::create([
                'tenant_id' => $portal->tenant_id,
                'user_id' => null,
                'event' => 'download',
                'auditable_type' => TransparencyDocument::class,
                'auditable_id' => $doc->id,
                'old_values' => null,
                'new_values' => [
                    'portal_slug' => $portal->slug,
                    'doc_type' => $doc->type,
                    'doc_year' => $doc->year,
                ],
                'ip_address' => \Illuminate\Support\Facades\Request::ip(),
                'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                'url' => \Illuminate\Support\Facades\Request::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        $ext = pathinfo((string) $doc->file_path, PATHINFO_EXTENSION);
        $name = 'documento-' . Str::slug($doc->title) . ($ext ? ('.' . $ext) : '');
        return Storage::disk('public')->download($doc->file_path, $name);
    }

    public function deletePartnership($id)
    {
        PublicPartnership::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        return back()->with('success', 'Parceria removida.');
    }

    /**
     * Open data export (CSV) - aggregated only (no PII).
     */
    public function openDataCsv($slug)
    {
        $portal = TransparencyPortal::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $tenant_id = $portal->tenant_id;

        $year = (int) request()->get('year', (int) now()->year);
        if ($year < 2000 || $year > ((int) now()->year + 2)) $year = (int) now()->year;
        $yearStart = Carbon::create($year, 1, 1)->toDateString();
        $yearEnd = Carbon::create($year, 12, 31)->toDateString();

        // Public download audit (aggregated use only)
        try {
            AuditLog::create([
                'tenant_id' => $portal->tenant_id,
                'user_id' => null,
                'event' => 'download_opendata',
                'auditable_type' => 'transparency_opendata',
                'auditable_id' => null,
                'old_values' => null,
                'new_values' => [
                    'portal_slug' => $portal->slug,
                    'year' => $year,
                    'format' => 'csv',
                    'datasets' => ['monthly_totals', 'expense_by_category'],
                ],
                'ip_address' => \Illuminate\Support\Facades\Request::ip(),
                'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                'url' => \Illuminate\Support\Facades\Request::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        $monthly = DB::table('transactions')
            ->where('tenant_id', $tenant_id)
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->select(DB::raw('type as type'), DB::raw('MONTH(date) as m'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('type'), DB::raw('MONTH(date)'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get();

        $expenseByCategory = DB::table('transactions')
            ->leftJoin('financial_categories as c', function ($j) {
                $j->on('c.id', '=', 'transactions.category_id');
                $j->on('c.tenant_id', '=', 'transactions.tenant_id');
            })
            ->where('transactions.tenant_id', $tenant_id)
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'paid')
            ->whereBetween('transactions.date', [$yearStart, $yearEnd])
            ->select(DB::raw("COALESCE(c.name, 'Sem categoria') as category"), DB::raw('SUM(transactions.amount) as total'))
            ->groupBy(DB::raw("COALESCE(c.name, 'Sem categoria')"))
            ->orderByDesc('total')
            ->orderBy('category')
            ->get();

        $filename = 'dados-abertos-' . $portal->slug . '-' . $year . '.csv';
        $csv = self::buildOpenDataCsvContent((string) $portal->slug, $year, $monthly, $expenseByCategory);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function buildPublicReportData(TransparencyPortal $portal, int $year, string $yearStart, string $yearEnd): array
    {
        $tenant_id = $portal->tenant_id;

        $board = BoardMember::where('tenant_id', $tenant_id)
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['name', 'position', 'tenure_start', 'tenure_end']);
        $docs = TransparencyDocument::where('tenant_id', $tenant_id)
            ->orderByDesc('year')
            ->get(['id', 'title', 'type', 'year'])
            ->groupBy('type');
        $partnerships = PublicPartnership::where('tenant_id', $tenant_id)
            ->orderBy('start_date')
            ->orderBy('project_name')
            ->orderBy('id')
            ->get([
                'agency_name', 'project_name', 'value', 'gazette_link', 'status', 'start_date', 'end_date'
            ]);

        $totalIn = (float) Transaction::where('tenant_id', $tenant_id)
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->sum('amount');

        $totalOut = (float) Transaction::where('tenant_id', $tenant_id)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->sum('amount');

        $projectOut = (float) Transaction::where('tenant_id', $tenant_id)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->whereNotNull('project_id')
            ->sum('amount');

        $investmentSocial = $projectOut > 0 ? $projectOut : $totalOut;
        $investmentNote = $projectOut > 0
            ? 'Despesas pagas alocadas a projetos'
            : 'Sem rateio por projeto: exibindo despesas totais pagas';

        $balance = $totalIn - $totalOut;

        $expenseByCategory = DB::table('transactions')
            ->leftJoin('financial_categories as c', function ($j) {
                $j->on('c.id', '=', 'transactions.category_id');
                $j->on('c.tenant_id', '=', 'transactions.tenant_id');
            })
            ->where('transactions.tenant_id', $tenant_id)
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'paid')
            ->whereBetween('transactions.date', [$yearStart, $yearEnd])
            ->select(DB::raw("COALESCE(c.name, 'Sem categoria') as category"), DB::raw('SUM(transactions.amount) as total'))
            ->groupBy(DB::raw("COALESCE(c.name, 'Sem categoria')"))
            ->orderByDesc('total')
            ->orderBy('category')
            ->get();

        $monthlyRows = DB::table('transactions')
            ->where('tenant_id', $tenant_id)
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->select(DB::raw('MONTH(date) as m'), DB::raw('type as type'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date)'), DB::raw('type'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get();

        $monthlyMap = [];
        foreach ($monthlyRows as $r) {
            $m = (int) ($r->m ?? 0);
            if ($m < 1 || $m > 12) continue;
            $type = (string) ($r->type ?? '');
            $monthlyMap[$m] ??= ['income' => 0.0, 'expense' => 0.0];
            $monthlyMap[$m][$type] = (float) ($r->total ?? 0);
        }
        $monthlyTotals = collect(range(1, 12))->map(function (int $m) use ($monthlyMap, $year) {
            $income = (float) ($monthlyMap[$m]['income'] ?? 0.0);
            $expense = (float) ($monthlyMap[$m]['expense'] ?? 0.0);
            $date = Carbon::create($year, $m, 1);
            return [
                'month' => $m,
                'label' => $date->locale('pt_BR')->translatedFormat('M'),
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        })->values();

        $familiesCount = (int) Beneficiary::where('tenant_id', $tenant_id)->count();
        $familyMembersCount = (int) DB::table('family_members as fm')
            ->join('beneficiaries as b', 'b.id', '=', 'fm.beneficiary_id')
            ->where('b.tenant_id', $tenant_id)
            ->count();
        $peopleCount = $familiesCount + $familyMembersCount;

        $attendancesYearTotal = (int) DB::table('attendances')
            ->where('tenant_id', $tenant_id)
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->count();

        $assetsCount = (int) Asset::query()->where('tenant_id', $tenant_id)->count();
        $assetsTotalValue = (float) Asset::query()->where('tenant_id', $tenant_id)->sum('value');

        $hrRow = Employee::query()
            ->where('tenant_id', $tenant_id)
            ->where('status', 'active')
            ->selectRaw('COUNT(*) as employees_count, COALESCE(SUM(salary),0) as payroll_total, COALESCE(SUM(bonus),0) as bonus_total')
            ->first();
        $employeesCount = (int) ($hrRow->employees_count ?? 0);
        $payrollTotal = (float) ($hrRow->payroll_total ?? 0);
        $bonusTotal = (float) ($hrRow->bonus_total ?? 0);

        $updatedAts = [
            DB::table('transactions')->where('tenant_id', $tenant_id)->where('status', 'paid')->whereBetween('date', [$yearStart, $yearEnd])->max('updated_at'),
            DB::table('attendances')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('assets')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('employees')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('transparency_documents')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('public_partnerships')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('transparency_board')->where('tenant_id', $tenant_id)->max('updated_at'),
            $portal->updated_at ?? null,
        ];
        $publicDataUpdatedAt = collect($updatedAts)
            ->filter()
            ->map(fn ($v) => Carbon::parse($v))
            ->sortDesc()
            ->first();

        return compact(
            'portal',
            'year',
            'yearStart',
            'yearEnd',
            'publicDataUpdatedAt',
            'totalIn',
            'totalOut',
            'investmentSocial',
            'investmentNote',
            'balance',
            'expenseByCategory',
            'monthlyTotals',
            'familiesCount',
            'peopleCount',
            'attendancesYearTotal',
            'assetsCount',
            'assetsTotalValue',
            'employeesCount',
            'payrollTotal',
            'bonusTotal',
            'board',
            'docs',
            'partnerships'
        );
    }

    public function publicReportPdf($slug)
    {
        $portal = TransparencyPortal::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $year = (int) request()->get('year', (int) now()->year);
        if ($year < 2000 || $year > ((int) now()->year + 2)) $year = (int) now()->year;
        $yearStart = Carbon::create($year, 1, 1)->toDateString();
        $yearEnd = Carbon::create($year, 12, 31)->toDateString();

        // Public download audit (PDF report)
        try {
            AuditLog::create([
                'tenant_id' => $portal->tenant_id,
                'user_id' => null,
                'event' => 'download_report_pdf',
                'auditable_type' => 'transparency_report',
                'auditable_id' => null,
                'old_values' => null,
                'new_values' => [
                    'portal_slug' => $portal->slug,
                    'year' => $year,
                    'format' => 'pdf',
                ],
                'ip_address' => \Illuminate\Support\Facades\Request::ip(),
                'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                'url' => \Illuminate\Support\Facades\Request::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        $data = $this->buildPublicReportData($portal, $year, $yearStart, $yearEnd);
        $filename = 'relatorio-transparencia-' . $portal->slug . '-' . $year . '.pdf';

        $stableInfo = self::dompdfStableInfo(
            'Relatório de Transparência - ' . ($portal->title ?? $portal->slug) . ' (' . $year . ')',
            $data['publicDataUpdatedAt'] ?? null
        );
        $pdf = Pdf::loadView('transparency.report_pdf', $data)
            ->addInfo($stableInfo)
            ->setPaper('a4', 'portrait');
        return $pdf->download($filename);
    }

    /**
     * Public View of the Transparency Portal
     */
    public function renderPortal($slug)
    {
        $portal = TransparencyPortal::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $tenant_id = $portal->tenant_id;

        $year = (int) request()->get('year', (int) now()->year);
        if ($year < 2000 || $year > ((int) now()->year + 2)) $year = (int) now()->year;
        $yearStart = Carbon::create($year, 1, 1)->toDateString();
        $yearEnd = Carbon::create($year, 12, 31)->toDateString();

        // Metadata
        $board = BoardMember::where('tenant_id', $tenant_id)->get();
        $docs = TransparencyDocument::where('tenant_id', $tenant_id)->get()->groupBy('type');
        $partnerships = PublicPartnership::where('tenant_id', $tenant_id)->get();

        // Financial Data (Aggregated from Transactions)
        $totalIn = Transaction::where('tenant_id', $tenant_id)
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->sum('amount');
        $totalOut = Transaction::where('tenant_id', $tenant_id)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->sum('amount');
        $projectOut = Transaction::where('tenant_id', $tenant_id)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->whereNotNull('project_id')
            ->sum('amount');

        // Prefer expenses linked to projects; fallback to total expenses when not categorized.
        $investmentSocial = ((float) $projectOut) > 0 ? $projectOut : $totalOut;
        $investmentNote = ((float) $projectOut) > 0
            ? 'Despesas pagas alocadas a projetos'
            : 'Sem rateio por projeto: exibindo despesas totais pagas';
        $balance = $totalIn - $totalOut;

        // Minimize fields to reduce risk of PII exposure (no description, no attachments).
        $lastExpenses = Transaction::query()
            ->select(['id', 'tenant_id', 'category_id', 'amount', 'date'])
            ->where('tenant_id', $tenant_id)
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->with(['category:id,tenant_id,name'])
            ->get();

        // Expense distribution (by category)
        $expenseRows = Transaction::query()
            ->leftJoin('financial_categories as c', function ($j) {
                $j->on('c.id', '=', 'transactions.category_id');
                $j->on('c.tenant_id', '=', 'transactions.tenant_id');
            })
            ->where('transactions.tenant_id', $tenant_id)
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'paid')
            ->whereBetween('transactions.date', [$yearStart, $yearEnd])
            ->select(DB::raw("COALESCE(c.name, 'Sem categoria') as name"), DB::raw('SUM(transactions.amount) as total'))
            ->groupBy(DB::raw("COALESCE(c.name, 'Sem categoria')"))
            ->orderByDesc('total')
            ->get();

        $expenseChartLabels = [];
        $expenseChartData = [];
        $other = 0.0;
        foreach ($expenseRows as $idx => $r) {
            $val = (float) ($r->total ?? 0);
            if ($idx < 4) {
                $expenseChartLabels[] = (string) $r->name;
                $expenseChartData[] = $val;
            } else {
                $other += $val;
            }
        }
        if ($other > 0) {
            $expenseChartLabels[] = 'Outros';
            $expenseChartData[] = $other;
        }
        $expenseChart = ['labels' => $expenseChartLabels, 'data' => $expenseChartData];

        // Open data preview (aggregated)
        $monthlyRows = DB::table('transactions')
            ->where('tenant_id', $tenant_id)
            ->where('status', 'paid')
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->select(DB::raw('MONTH(date) as m'), DB::raw('type as type'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date)'), DB::raw('type'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get();

        $monthlyMap = [];
        foreach ($monthlyRows as $r) {
            $m = (int) ($r->m ?? 0);
            if ($m < 1 || $m > 12) continue;
            $type = (string) ($r->type ?? '');
            $monthlyMap[$m] ??= ['income' => 0.0, 'expense' => 0.0];
            $monthlyMap[$m][$type] = (float) ($r->total ?? 0);
        }

        $openDataMonthly = collect(range(1, 12))->map(function (int $m) use ($monthlyMap, $year) {
            $income = (float) ($monthlyMap[$m]['income'] ?? 0.0);
            $expense = (float) ($monthlyMap[$m]['expense'] ?? 0.0);
            $date = Carbon::create($year, $m, 1);
            return [
                'month' => $m,
                'label' => $date->locale('pt_BR')->translatedFormat('M'),
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        });

        $openDataExpenseByCategory = $expenseRows
            ->map(fn ($r) => [
                'category' => (string) ($r->name ?? 'Sem categoria'),
                'total' => (float) ($r->total ?? 0),
            ])
            ->take(10)
            ->values();

        // Social Impact (Aggregated)
        $familiesCount = Beneficiary::where('tenant_id', $tenant_id)->count();
        $familyMembersCount = (int) DB::table('family_members as fm')
            ->join('beneficiaries as b', 'b.id', '=', 'fm.beneficiary_id')
            ->where('b.tenant_id', $tenant_id)
            ->count();
        $peopleCount = $familiesCount + $familyMembersCount;
        $attendancesCount = (int) DB::table('attendances')
            ->where('tenant_id', $tenant_id)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        // Attendances evolution (last 6 months)
        $start = now()->startOfMonth()->subMonths(5);
        $end = now()->endOfMonth();
        $impactRows = DB::table('attendances')
            ->where('tenant_id', $tenant_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->select(DB::raw('YEAR(date) as y'), DB::raw('MONTH(date) as m'), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTH(date)'))
            ->orderBy('y')->orderBy('m')
            ->get();
        $impactLabels = [];
        $impactData = [];
        $cursor = $start->copy();
        $map = [];
        foreach ($impactRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $map[$key] = (int) $r->c;
        }
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $impactLabels[] = $cursor->locale('pt_BR')->translatedFormat('M');
            $impactData[] = (int) ($map[$key] ?? 0);
            $cursor->addMonth();
        }
        $impactChart = ['labels' => $impactLabels, 'data' => $impactData];
        
        // Minimize asset fields exposed/processed in public view.
        $assets = Asset::query()
            ->select(['id', 'tenant_id', 'name', 'code', 'acquisition_date', 'value', 'status'])
            ->where('tenant_id', $tenant_id)
            ->orderBy('acquisition_date', 'desc')
            ->get();
        
        // HR: aggregated only (LGPD - do not load names).
        $hrRow = Employee::query()
            ->where('tenant_id', $tenant_id)
            ->where('status', 'active')
            ->selectRaw('COUNT(*) as employees_count, COALESCE(SUM(salary),0) as payroll_total, COALESCE(SUM(bonus),0) as bonus_total')
            ->first();
        $employeesCount = (int) ($hrRow->employees_count ?? 0);
        $payrollTotal = (float) ($hrRow->payroll_total ?? 0);
        $bonusTotal = (float) ($hrRow->bonus_total ?? 0);

        // Public data "last updated" timestamp (for auditability / LAI good practice)
        $updatedAts = [
            DB::table('transactions')->where('tenant_id', $tenant_id)->where('status', 'paid')->whereBetween('date', [$yearStart, $yearEnd])->max('updated_at'),
            DB::table('attendances')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('assets')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('employees')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('transparency_documents')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('public_partnerships')->where('tenant_id', $tenant_id)->max('updated_at'),
            DB::table('transparency_board')->where('tenant_id', $tenant_id)->max('updated_at'),
            $portal->updated_at ?? null,
        ];
        $publicDataUpdatedAt = collect($updatedAts)
            ->filter()
            ->map(fn ($v) => Carbon::parse($v))
            ->sortDesc()
            ->first();

        // Public audit (aggregated counts) - last 6 months
        $auditStart = now()->startOfMonth()->subMonths(5);
        $auditEnd = now()->endOfMonth();
        $auditRows = DB::table('audit_logs')
            ->where('tenant_id', $tenant_id)
            ->whereBetween('created_at', [$auditStart->toDateTimeString(), $auditEnd->toDateTimeString()])
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('event', 'download')
                        ->where('auditable_type', TransparencyDocument::class);
                })->orWhere('event', 'download_opendata');
            })
            ->select(DB::raw('YEAR(created_at) as y'), DB::raw('MONTH(created_at) as m'), DB::raw('event as event'), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'), DB::raw('event'))
            ->orderBy('y')->orderBy('m')
            ->get();

        $auditMap = [];
        foreach ($auditRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $auditMap[$key] ??= ['docs' => 0, 'opendata' => 0];
            if (($r->event ?? '') === 'download') $auditMap[$key]['docs'] = (int) ($r->c ?? 0);
            if (($r->event ?? '') === 'download_opendata') $auditMap[$key]['opendata'] = (int) ($r->c ?? 0);
        }

        $publicAuditDownloads = [];
        $cursor2 = $auditStart->copy();
        while ($cursor2 <= $auditEnd) {
            $key = $cursor2->format('Y-m');
            $publicAuditDownloads[] = [
                'label' => $cursor2->locale('pt_BR')->translatedFormat('M'),
                'docs' => (int) (($auditMap[$key]['docs'] ?? 0)),
                'opendata' => (int) (($auditMap[$key]['opendata'] ?? 0)),
            ];
            $cursor2->addMonth();
        }

        return view('transparency.portal', compact(
            'portal', 'board', 'docs', 'partnerships', 
            'totalIn', 'totalOut', 'investmentSocial', 'balance', 'lastExpenses',
            'familiesCount', 'peopleCount', 'attendancesCount', 'assets',
            'year', 'expenseChart', 'impactChart',
            'employeesCount', 'payrollTotal', 'bonusTotal',
            'investmentNote',
            'publicDataUpdatedAt',
            'openDataMonthly',
            'openDataExpenseByCategory',
            'publicAuditDownloads'
        ));
    }
}
