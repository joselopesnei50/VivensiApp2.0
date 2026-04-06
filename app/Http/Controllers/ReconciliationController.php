<?php

namespace App\Http\Controllers;

use App\Services\OfxParserService;
use App\Models\Transaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReconciliationController extends Controller
{
    public function index()
    {
        return view('ngo.reconciliation.index');
    }

    public function upload(Request $request, OfxParserService $parser)
    {
        $request->validate([
            'ofx_file' => 'required|file|max:2048', // 2MB
        ]);

        $file = $request->file('ofx_file');
        $path = $file->storeAs('temp', 'upload.ofx');
        
        try {
            $parsedTransactions = $parser->parse(storage_path('app/' . $path));
            
            // Get all categories for dropdown
            $categories = FinancialCategory::where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('name')
                            ->get();

            $matches = [];
            foreach ($parsedTransactions as $pt) {
                // Tenta encontrar transação no sistema
                $dbTrn = Transaction::where('tenant_id', auth()->user()->tenant_id)
                    ->where('amount', $pt['amount']) // Amount is absolute in parser
                    ->where('type', $pt['type'])
                    ->whereBetween('date', [
                        Carbon::parse($pt['date'])->subDays(2), 
                        Carbon::parse($pt['date'])->addDays(2)
                    ])
                    ->first();

                // Guess Category ID
                $suggestedCategoryId = null;
                if (!$dbTrn) {
                    $suggestedCatName = $this->guessCategory($pt['description'], $pt['type']);
                    if ($suggestedCatName) {
                        // Find or Create the category
                        $cat = FinancialCategory::firstOrCreate(
                            ['tenant_id' => auth()->user()->tenant_id, 'name' => $suggestedCatName],
                            ['type' => $pt['type']]
                        );
                        $suggestedCategoryId = $cat->id;
                    }
                }

                $matches[] = [
                    'ofx' => $pt,
                    'system' => $dbTrn,
                    'suggested_category_id' => $suggestedCategoryId
                ];
            }

            // Refresh categories list if new ones were created
            $categories = FinancialCategory::where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('name')
                            ->get();

            Storage::delete($path);

            return view('ngo.reconciliation.match', compact('matches', 'categories'));

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao ler arquivo OFX: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $data = $request->input('transactions');
        
        if (!$data) {
             return redirect('/ngo/reconciliation')->with('success', 'Nenhuma transação importada.');
        }

        $count = 0;
        foreach ($data as $trnData) {
            // Se foi marcado para importar (checked) e não é duplicado
            if (isset($trnData['checked']) && $trnData['checked'] == 1) {
                $trn = new Transaction();
                $trn->tenant_id = auth()->user()->tenant_id;
                $trn->description = $trnData['description'];
                $trn->amount = $trnData['amount'];
                $trn->type = $trnData['type'];
                $trn->date = $trnData['date'];
                $trn->status = 'paid'; // OFX é realizado
                
                // Use selected category or Uncategorized (1) default
                $trn->category_id = $trnData['category_id'] ?? 1;
                
                $trn->save();
                $count++;
            }
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
