<?php

namespace App\Http\Controllers;

use App\Models\NgoGrant;
use App\Models\NgoGrantDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AuditLog;

class NgoGrantController extends Controller
{
    private function extractJsonFromAiText(string $text): ?string
    {
        $t = trim($text);

        // If Gemini wrapped in ```json ... ```
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $t, $m)) {
            $t = trim($m[1]);
        }

        // Try to cut to the first JSON object/array if there is extra text around it
        $firstBrace = strpos($t, '{');
        $lastBrace = strrpos($t, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            return substr($t, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        $firstBracket = strpos($t, '[');
        $lastBracket = strrpos($t, ']');
        if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
            return substr($t, $firstBracket, $lastBracket - $firstBracket + 1);
        }

        return null;
    }

    private function mapAiGrantDataToForm(array $ai): array
    {
        $title = $ai['title'] ?? $ai['titulo'] ?? $ai['título'] ?? null;
        $grantor = $ai['grantor_name'] ?? $ai['orgao_concessor'] ?? $ai['órgão_concessor'] ?? $ai['agency'] ?? null;
        $amount = $ai['total_amount'] ?? $ai['valor_total'] ?? $ai['value'] ?? null;
        $start = $ai['start_date'] ?? $ai['data_inicio'] ?? null;
        $end = $ai['end_date'] ?? $ai['data_fim'] ?? $ai['deadline'] ?? null;
        $contractNumber = $ai['contract_number'] ?? $ai['numero_processo'] ?? $ai['número_processo'] ?? $ai['numero_contrato'] ?? null;

        // Optional: if the AI provided extra fields, merge into a notes-like string (not stored yet).
        $requisitos = $ai['requisitos'] ?? null;
        $objeto = $ai['objeto'] ?? null;

        $notes = trim(implode("\n\n", array_filter([
            $objeto ? "Objeto:\n" . trim((string) $objeto) : null,
            $requisitos ? "Requisitos:\n" . trim((string) $requisitos) : null,
        ], fn ($v) => $v !== null && trim((string) $v) !== '')));

        return [
            'title' => is_string($title) ? trim($title) : $title,
            'grantor_name' => is_string($grantor) ? trim($grantor) : $grantor,
            'total_amount' => is_string($amount) ? trim($amount) : $amount,
            'start_date' => is_string($start) ? trim($start) : $start,
            'end_date' => is_string($end) ? trim($end) : $end,
            'contract_number' => is_string($contractNumber) ? trim($contractNumber) : $contractNumber,
            'notes' => $notes ?: null,
            '_ai_notes' => $notes ?: null,
        ];
    }

    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        $base = NgoGrant::where('tenant_id', $tenant_id);

        // Stats should reflect the whole tenant dataset (not only current page/filters).
        $stats = [
            'total_funding' => (clone $base)->sum('value'),
            'active_count' => (clone $base)->where('status', 'open')->count(),
            'reporting_count' => (clone $base)->where('status', 'reporting')->count(),
            'expiring_soon' => (clone $base)
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
                ->count(),
            'expired_pending' => (clone $base)
                ->whereNotNull('deadline')
                ->where('deadline', '<', now()->startOfDay())
                ->where('status', '!=', 'closed')
                ->count(),
        ];

        $q = trim((string) request()->query('q', ''));
        $status = trim((string) request()->query('status', ''));
        $deadline = trim((string) request()->query('deadline', '')); // expired|soon|none
        $hasDocs = trim((string) request()->query('has_docs', '')); // 1
        $attention = trim((string) request()->query('attention', '')); // expired_pending

        $listQ = (clone $base)->withCount('documents');

        if ($q !== '') {
            $listQ->where(function ($sub) use ($q) {
                $sub->where('title', 'LIKE', '%' . $q . '%')
                    ->orWhere('agency', 'LIKE', '%' . $q . '%')
                    ->orWhere('contract_number', 'LIKE', '%' . $q . '%');
            });
        }

        if (in_array($status, ['open', 'reporting', 'closed'], true)) {
            $listQ->where('status', $status);
        }

        // One-click operational filter: overdue and not closed.
        if ($attention === 'expired_pending') {
            $listQ->whereNotNull('deadline')
                ->where('deadline', '<', now()->startOfDay())
                ->where('status', '!=', 'closed');
        }

        if (in_array($deadline, ['expired', 'soon', 'none'], true)) {
            if ($deadline === 'expired') {
                $listQ->whereNotNull('deadline')->where('deadline', '<', now()->startOfDay());
            } elseif ($deadline === 'soon') {
                $listQ->whereNotNull('deadline')->whereBetween('deadline', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
            } elseif ($deadline === 'none') {
                $listQ->whereNull('deadline');
            }
        }

        if ($hasDocs === '1') {
            $listQ->has('documents');
        }

        // Deadlines first, nulls last.
        $grants = $listQ
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline', 'asc')
            ->paginate(20)
            ->withQueryString();

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

    public function show($id)
    {
        $grant = NgoGrant::with(['documents' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])->findOrFail($id);
        return view('ngo.grants.show', compact('grant'));
    }

    public function updateStatus(Request $request, $id)
    {
        $grant = NgoGrant::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,reporting,closed',
        ]);

        $grant->status = $validated['status'];
        $grant->save();

        return back()->with('success', 'Status atualizado com sucesso!');
    }

    public function uploadDocument(Request $request, $id)
    {
        $grant = NgoGrant::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:edital,plano_trabalho,anexo,comprovante,outros',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,zip,doc,docx,xls,xlsx|max:10240',
        ]);

        $file = $request->file('file');
        $tenantId = auth()->user()->tenant_id;
        $disk = 'public';
        $dir = "ngo_grants/{$tenantId}/{$grant->id}";
        $path = $file->store($dir, $disk);

        NgoGrantDocument::create([
            'tenant_id' => $tenantId,
            'ngo_grant_id' => $grant->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Documento anexado com sucesso!');
    }

    public function downloadDocument($id, $docId)
    {
        $grant = NgoGrant::findOrFail($id);
        $doc = NgoGrantDocument::where('ngo_grant_id', $grant->id)->findOrFail($docId);

        $disk = 'public';
        if (!Storage::disk($disk)->exists($doc->file_path)) {
            return back()->with('error', 'Arquivo não encontrado no servidor.');
        }

        try {
            AuditLog::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'event' => 'download',
                'auditable_type' => NgoGrantDocument::class,
                'auditable_id' => $doc->id,
                'old_values' => null,
                'new_values' => [
                    'ngo_grant_id' => $grant->id,
                    'title' => $doc->title,
                    'type' => $doc->type,
                    'original_name' => $doc->original_name,
                    'size' => $doc->size,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // Keep flow stable.
        }

        $filename = $doc->original_name ?: basename($doc->file_path);
        return Storage::disk($disk)->download($doc->file_path, $filename);
    }

    public function deleteDocument($id, $docId)
    {
        $grant = NgoGrant::findOrFail($id);
        $doc = NgoGrantDocument::where('ngo_grant_id', $grant->id)->findOrFail($docId);

        $doc->delete();
        return back()->with('success', 'Documento removido com sucesso!');
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

        // Tentar extrair o JSON da resposta (O Gemini às vezes retorna texto antes/depois)
        try {
            $text = (string) ($result['candidates'][0]['content']['parts'][0]['text'] ?? '');
            if (trim($text) === '') {
                return back()->with('error', 'A IA não retornou texto analisável. Tente novamente.');
            }

            $json = $this->extractJsonFromAiText($text);
            if (!$json) {
                return back()->with('error', 'A IA leu o arquivo, mas não retornou JSON. Tente novamente.');
            }

            $aiData = json_decode(trim($json), true);

            if (!$aiData) {
                return back()->with('error', 'A IA leu o arquivo mas não conseguiu estruturar os dados. Tente novamente.');
            }

            $mapped = $this->mapAiGrantDataToForm($aiData);
            return view('ngo.grants.create', ['analyzed_data' => $mapped]);

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
            'notes' => 'nullable|string|max:20000',
        ])->validate();

        // Map form fields to Model attributes
        $grant = new NgoGrant();
        $grant->tenant_id = auth()->user()->tenant_id;
        $grant->title = $validated['title'];
        $grant->agency = $validated['grantor_name']; // Map grantor_name to agency
        $grant->contract_number = $validated['contract_number'] ?? null;
        $grant->value = $validated['total_amount'];   // Map total_amount to value
        $grant->start_date = $validated['start_date'] ?? null;
        $grant->deadline = $validated['end_date'];    // Map end_date to deadline
        $grant->status = 'open';
        $grant->notes = $validated['notes'] ?? null;
        $grant->save();

        return redirect('/ngo/grants')->with('success', 'Edital/Convênio registrado com sucesso!');
    }

    public function destroy($id)
    {
        $grant = NgoGrant::findOrFail($id);

        // Delete associated stored files (DB cascade won't trigger Eloquent events for docs).
        try {
            $tenantId = auth()->user()->tenant_id;
            Storage::disk('public')->deleteDirectory("ngo_grants/{$tenantId}/{$grant->id}");
        } catch (\Throwable $e) {
            // Keep flow stable.
        }

        $grant->delete();
        return redirect('/ngo/grants')->with('success', 'Convênio/Edital excluído com sucesso!');
    }
}
