<?php

namespace App\Http\Controllers;

use App\Services\OfxParserService;
use App\Models\Transaction;
use App\Models\FinancialCategory;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReconciliationController extends Controller
{
    public function index()
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $projects = Project::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('ngo.reconciliation.index', compact('projects'));
    }

    public function upload(Request $request, OfxParserService $parser)
    {
        $request->validate([
            'ofx_file'   => 'required|file|max:2048|mimetypes:text/plain,application/xml,text/xml,application/octet-stream,application/x-ofx',
            'project_id' => 'nullable|integer',
        ]);

        $tenantId = (int) auth()->user()->tenant_id;

        // Valida projeto opcional contra o tenant do user (anti-IDOR).
        $projectIdOverride = null;
        $projectNameLabel  = null;
        if ($request->filled('project_id')) {
            $p = Project::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', (int) $request->input('project_id'))
                ->first(['id', 'name']);
            if ($p) {
                $projectIdOverride = $p->id;
                $projectNameLabel  = $p->name;
            }
        }

        $file = $request->file('ofx_file');
        $path = $file->storeAs('temp', 'upload_' . auth()->id() . '_' . uniqid() . '.ofx');
        
        try {
            $parsedTransactions = $parser->parse(storage_path('app/' . $path));
            
            // Get all categories for dropdown
            $categories = FinancialCategory::where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('name')
                            ->get();

            $tenantId = auth()->user()->tenant_id;

            // Carrega fitids já importados para este tenant — evita N+1 e duplicação
            $existingFitids = Transaction::withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('ofx_fitid')
                ->pluck('ofx_fitid')
                ->flip();

            $matches = [];
            foreach ($parsedTransactions as $pt) {
                // Verifica duplicata pelo FITID (ID único do banco)
                $alreadyImported = isset($existingFitids[$pt['fitid']]);

                // Tenta encontrar transação correspondente no sistema (por valor + data ±2 dias)
                $dbTrn = Transaction::where('tenant_id', $tenantId)
                    ->where('amount', $pt['amount'])
                    ->where('type', $pt['type'])
                    ->whereBetween('date', [
                        Carbon::parse($pt['date'])->subDays(2),
                        Carbon::parse($pt['date'])->addDays(2),
                    ])
                    ->first();

                // Sugere categoria apenas para transações novas
                $suggestedCategoryId = null;
                if (!$dbTrn && !$alreadyImported) {
                    $suggestedCatName = $this->guessCategory($pt['description'], $pt['type']);
                    if ($suggestedCatName) {
                        $cat = FinancialCategory::firstOrCreate(
                            ['tenant_id' => $tenantId, 'name' => $suggestedCatName],
                            ['type' => $pt['type']]
                        );
                        $suggestedCategoryId = $cat->id;
                    }
                }

                $matches[] = [
                    'ofx'                    => $pt,
                    'system'                 => $dbTrn,
                    'suggested_category_id'  => $suggestedCategoryId,
                    'already_imported'       => $alreadyImported,
                ];
            }

            // Refresh categories list if new ones were created
            $categories = FinancialCategory::where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('name')
                            ->get();

            Storage::delete($path);

            return view('ngo.reconciliation.match', compact('matches', 'categories', 'projectIdOverride', 'projectNameLabel'));

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao ler arquivo OFX: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate(['project_id' => 'nullable|integer']);

        $data = $request->input('transactions');

        if (!$data) {
             return redirect('/ngo/reconciliation')->with('success', 'Nenhuma transação importada.');
        }

        $tenantId = auth()->user()->tenant_id;

        // Valida projeto opcional (vem do hidden field da match.blade.php).
        $projectIdOverride = null;
        if ($request->filled('project_id')) {
            $projectIdOverride = Project::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', (int) $request->input('project_id'))
                ->value('id');
        }

        // Carrega fitids existentes para bloquear duplicatas na importação
        $existingFitids = Transaction::withTrashed()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ofx_fitid')
            ->pluck('ofx_fitid')
            ->flip();

        // Garante que existe categoria "Não Categorizado" para o tenant
        $uncategorized = FinancialCategory::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Não Categorizado'],
            ['type' => 'expense']
        );

        $count = 0;
        foreach ($data as $trnData) {
            if (!isset($trnData['checked']) || $trnData['checked'] != 1) {
                continue;
            }

            // Bloqueia duplicatas pelo fitid mesmo que o checkbox esteja marcado
            $fitid = $trnData['fitid'] ?? null;
            if ($fitid && isset($existingFitids[$fitid])) {
                continue;
            }

            $trn = new Transaction();
            $trn->tenant_id    = $tenantId;
            $trn->project_id   = $projectIdOverride; // null se nao selecionado no upload
            $trn->description  = $trnData['description'];
            $trn->amount       = $trnData['amount'];
            $trn->type         = $trnData['type'];
            $trn->date         = $trnData['date'];
            $trn->status       = 'paid';
            $trn->category_id  = !empty($trnData['category_id']) ? $trnData['category_id'] : $uncategorized->id;
            $trn->ofx_fitid    = $fitid;
            $trn->reconciled_at = now();
            $trn->save();
            $count++;
        }

        return redirect('/ngo/reconciliation')->with('success', "$count transações importadas com sucesso!");
    }

    /**
     * Tenta adivinhar a categoria baseada na descrição do banco
     */
    private function guessCategory($description, $type)
    {
        $description = strtoupper($description);

        // Keywords Mapping
        $map = [
            'UBER' => 'Transporte e Deslocamento',
            '99APP' => 'Transporte e Deslocamento',
            'POSTO' => 'Combustível',
            'IPIRANGA' => 'Combustível',
            'SHELL' => 'Combustível',
            'ELETRO' => 'Energia Elétrica',
            'ENEL' => 'Energia Elétrica',
            'LIGHT' => 'Energia Elétrica',
            'CEMIG' => 'Energia Elétrica',
            'CPFL' => 'Energia Elétrica',
            'SABESP' => 'Água e Esgoto',
            'DAE' => 'Água e Esgoto',
            'VIVO' => 'Telefonia e Internet',
            'CLARO' => 'Telefonia e Internet',
            'TIM' => 'Telefonia e Internet',
            'OI' => 'Telefonia e Internet',
            'NET' => 'Telefonia e Internet',
            'AMAZON' => 'Serviços de Tecnologia',
            'AWS' => 'Serviços de Tecnologia',
            'GOOGLE' => 'Serviços de Tecnologia',
            'MICROSOFT' => 'Serviços de Tecnologia',
            'DIGITALOCEAN' => 'Serviços de Tecnologia',
            'HOSTGATOR' => 'Serviços de Tecnologia',
            'HOSTINGER' => 'Serviços de Tecnologia',
            'MARKET' => 'Alimentação',
            'SUPERMERCADO' => 'Alimentação',
            'ASSAI' => 'Alimentação',
            'CARREFOUR' => 'Alimentação',
            'PADARIA' => 'Alimentação',
            'IFOOL' => 'Refeições',
            'RESTAURANTE' => 'Refeições',
            'ALUGUEL' => 'Aluguel e Condomínio',
            'CONDOMINIO' => 'Aluguel e Condomínio',
            'TAR' => 'Tarifas Bancárias',
            'TARIFA' => 'Tarifas Bancárias',
            'CESTA' => 'Tarifas Bancárias',
            'MENSALIDADE' => 'Tarifas Bancárias',
            'IOF' => 'Impostos e Taxas',
            'DARF' => 'Impostos e Taxas',
            'DAS' => 'Impostos e Taxas',
            'PIX ENV' => 'Pagamentos Diversos',
            'PAGTO' => 'Pagamentos Diversos',
        ];

        // Specific checks for Income
        if ($type == 'income') {
            if (str_contains($description, 'PIX REC') || str_contains($description, 'PIX RECEBIDO')) return 'Doações - PIX';
            if (str_contains($description, 'DEPOSITO')) return 'Doações - Depósito';
            if (str_contains($description, 'TED')) return 'Doações - Transferência';
            if (str_contains($description, 'RESGATE')) return 'Resgate de Aplicação';
            return 'Entradas a Classificar';
        }

        // Expense mapping
        foreach ($map as $key => $category) {
            if (str_contains($description, $key)) {
                return $category;
            }
        }

        return null;
    }
}
