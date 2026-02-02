<?php

namespace App\Http\Controllers;

use App\Models\NgoGrant;
use Illuminate\Http\Request;

class NgoGrantController extends Controller
{
    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        $grants = NgoGrant::where('tenant_id', $tenant_id)
                          ->orderBy('deadline', 'asc') // Prazos mais próximos primeiro
                          ->get();

        $stats = [
            'total_funding' => $grants->sum('value'),
            'active_count' => $grants->where('status', 'open')->count(),
            'reporting_count' => $grants->where('status', 'reporting')->count(),
            'expiring_soon' => $grants->filter(function($g) {
                return $g->deadline && now()->diffInDays($g->deadline, false) < 30 && now()->diffInDays($g->deadline, false) > 0;
            })->count()
        ];

        return view('ngo.grants.index', compact('grants', 'stats'));
    }

    public function create()
    {
        return view('ngo.grants.create');
    }

    public function createFromAi()
    {
        return view('ngo.grants.create_ai');
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'edital_file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        $file = $request->file('edital_file');
        $base64 = base64_encode(file_get_contents($file->getRealPath()));

        $prompt = "Aja como um especialista em Captação de Recursos. Analise este edital em PDF e extraia as seguintes informações em formato JSON, sem marcações de código markdown:
        - titulo (Título resumido do edital)
        - orgao_concessor (Nome do órgão ou empresa)
        - valor_total (Valor total do recurso, apenas números)
        - data_inicio (Data de início prevista ou data do edital, YYYY-MM-DD)
        - data_fim (Data limite para submissão ou fim do convênio, YYYY-MM-DD)
        - requisitos (Resumo dos principais requisitos de elegibilidade)
        - objeto (Resumo do objeto do convênio)";

        $gemini = new \App\Services\GeminiService();
        $result = $gemini->analyzePdfFile($base64, $prompt);

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        // Tentar extrair o JSON da resposta (O Gemini as vezes retorna texto antes/depois)
        try {
            $text = $result['candidates'][0]['content']['parts'][0]['text'];
            // Remove markdown code blocks if present
            $text = preg_replace('/^```json/', '', $text);
            $text = preg_replace('/```$/', '', $text);
            $data = json_decode(trim($text), true);

            if (!$data) {
                // Fallback se não for JSON válido
                return back()->with('error', 'A IA leu o arquivo mas não conseguiu estruturar os dados. Tente novamente.');
            }

            return view('ngo.grants.create', ['analyzed_data' => $data]);

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao processar resposta da IA: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        // Sanitização de Moeda
        if (isset($data['total_amount'])) {
             $data['total_amount'] = str_replace('.', '', $data['total_amount']);
             $data['total_amount'] = str_replace(',', '.', $data['total_amount']);
        }

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'title' => 'required|string|max:255',
            'grantor_name' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:100',
            'total_amount' => 'required|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'required|date', // 'after_or_equal:start_date' removed for simplicity if start_date is null
            'compliance_rules' => 'nullable|string'
        ])->validate();

        // Map form fields to Model attributes
        $grant = new NgoGrant();
        $grant->tenant_id = auth()->user()->tenant_id;
        $grant->title = $validated['title'];
        $grant->agency = $validated['grantor_name']; // Map grantor_name to agency
        $grant->value = $validated['total_amount'];   // Map total_amount to value
        $grant->deadline = $validated['end_date'];    // Map end_date to deadline
        $grant->status = 'open';
        $grant->save();

        return redirect('/ngo/grants')->with('success', 'Edital/Convênio registrado com sucesso!');
    }
}
