<?php

namespace App\Http\Controllers;

use App\Models\NgoGrant;
use App\Models\NgoGrantDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AuditLog;
use App\Models\Project;

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
        $tenantId = auth()->user()->tenant_id;
        $grant = NgoGrant::where('tenant_id', $tenantId)->with([
            'documents' => fn($q) => $q->orderBy('created_at', 'desc'),
            'project.transactions' => fn($q) => $q->orderBy('date', 'desc'),
            'project.tasks',
        ])->findOrFail($id);

        $project      = $grant->project;
        $transactions = $project?->transactions ?? collect();
        $tasks        = $project?->tasks ?? collect();

        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance      = $totalIncome - $totalExpense;
        $budget       = (float) ($project?->budget ?? 0);
        $usedPercent  = $budget > 0 ? min(100, round(($totalExpense / $budget) * 100)) : 0;

        return view('ngo.grants.show', compact(
            'grant', 'project', 'transactions', 'tasks',
            'totalIncome', 'totalExpense', 'balance', 'budget', 'usedPercent'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $grant = NgoGrant::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,reporting,closed',
        ]);

        $grant->status = $validated['status'];
        $grant->save();

        return back()->with('success', 'Status atualizado com sucesso!');
    }

    public function generateProposal($id)
    {
        $grant = NgoGrant::findOrFail($id);
        $tenant = auth()->user()->tenant;

        $prompt = "Aja como um consultor sênior em captação de recursos para o Terceiro Setor. 
        Crie um rascunho estruturado de proposta de projeto para o edital abaixo:
        
        Título do Edital: {$grant->title}
        Órgão Concessor: {$grant->agency}
        Valor solicitado: R$ " . number_format($grant->value, 2, ',', '.') . "
        Notas/Requisitos: {$grant->notes}
        
        Nome da ONG: " . ($tenant->corpo_name ?? 'Nossa Organização') . "
        
        Estruture a proposta com:
        1. Resumo Executivo
        2. Justificativa e Impacto Social
        3. Objetivos Gerais e Específicos
        4. Metodologia de Execução
        5. Plano de Sustentabilidade
        
        Use um tom profissional, persuasivo e focado em resultados sociais mensuráveis. Formate em Markdown.";

        $ds     = new \App\Services\DeepSeekService();
        $result = $ds->chat([['role' => 'user', 'content' => $prompt]]);
        $proposal = $result['choices'][0]['message']['content'] ?? null;

        if (!$proposal) {
            return response()->json(['error' => 'Não foi possível gerar a proposta no momento. Verifique a chave DeepSeek no painel admin.'], 500);
        }

        // Persist so the user can retrieve it without regenerating
        $grant->ai_proposal = $proposal;
        $grant->save();

        return response()->json(['proposal' => $proposal]);
    }

    public function aiAnalyze($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $grant    = NgoGrant::where('tenant_id', $tenantId)->with([
            'project.transactions',
            'project.tasks',
        ])->findOrFail($id);

        $tenant  = auth()->user()->tenant;
        $orgName = $tenant->corpo_name ?? $tenant->name ?? 'Nossa Organização';

        $deadline    = $grant->deadline?->format('d/m/Y') ?? 'Não informado';
        $daysLeft    = $grant->deadline ? now()->diffInDays($grant->deadline, false) : null;
        $prazoInfo   = $daysLeft !== null
            ? ($daysLeft >= 0 ? "{$deadline} ({$daysLeft} dias restantes)" : "{$deadline} (vencido há " . abs((int)$daysLeft) . " dias)")
            : 'Não informado';

        $valor       = 'R$ ' . number_format((float) $grant->value, 2, ',', '.');
        $statusLabel = ['open' => 'Ativo', 'reporting' => 'Prestação de Contas', 'closed' => 'Encerrado'][$grant->status] ?? $grant->status;

        $project      = $grant->project;
        $transactions = $project?->transactions ?? collect();
        $tasks        = $project?->tasks ?? collect();
        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $tasksDone    = $tasks->where('status', 'done')->count();
        $tasksTotal   = $tasks->count();
        $budget       = (float) ($project?->budget ?? $grant->value ?? 0);
        $usedPercent  = $budget > 0 ? min(100, round(($totalExpense / $budget) * 100)) : 0;

        $contextFinanceiro = $project
            ? "Captado: {$totalIncome} | Gasto: {$totalExpense} | Orçamento utilizado: {$usedPercent}% | Tarefas: {$tasksDone}/{$tasksTotal} concluídas"
            : 'Nenhum projeto associado ainda.';

        $isReporting = $grant->status === 'reporting';

        $prompt = <<<PROMPT
Você é um especialista em gestão de convênios e editais para o Terceiro Setor brasileiro.
Analise o seguinte edital/convênio e gere uma análise técnica detalhada.

## DADOS DO EDITAL
- **Organização:** {$orgName}
- **Título:** {$grant->title}
- **Concedente:** {$grant->agency}
- **Valor:** {$valor}
- **Prazo:** {$prazoInfo}
- **Status atual:** {$statusLabel}
- **Requisitos/Observações:** {$grant->notes}
- **Progresso financeiro e operacional:** {$contextFinanceiro}

## ANÁLISE SOLICITADA
Gere uma análise estruturada em Markdown com EXATAMENTE estas seções:

### 🎯 Viabilidade
Avalie a viabilidade de executar/captar este edital com uma pontuação de 1 a 10 e justificativa objetiva em 2-3 linhas.

### ✅ Checklist de Requisitos
Liste em checkboxes (`- [ ]`) os principais requisitos que precisam ser atendidos ou verificados, baseados nas informações disponíveis.

### ⚠️ Riscos Identificados
Liste os 3-5 principais riscos com nível (🔴 Alto / 🟡 Médio / 🟢 Baixo) e breve descrição.

### 📋 Próximas Ações
Liste 3-5 ações concretas e prioritárias com prazo sugerido (ex: "nos próximos 7 dias", "até 30 dias antes do deadline").

### 📅 Cronograma Sugerido
Sugira 4-6 marcos/milestones com estimativa de prazo relativo ao deadline do edital.
PROMPT;

        if ($isReporting) {
            $prompt .= <<<REPORTING

### 📊 Análise de Prestação de Contas
Com base no progresso financeiro ({$usedPercent}% do orçamento utilizado, {$tasksDone}/{$tasksTotal} tarefas concluídas), avalie:
- Conformidade com o planejado
- Pontos de atenção para o relatório final
- Documentos essenciais a providenciar
REPORTING;
        }

        $prompt .= "\n\nResponda APENAS em Português brasileiro. Seja direto, objetivo e prático.";

        $ds     = new \App\Services\DeepSeekService();
        $result = $ds->chat([
            ['role' => 'system', 'content' => 'Você é um especialista em captação de recursos e gestão de convênios para ONGs brasileiras. Suas análises são diretas, técnicas e acionáveis.'],
            ['role' => 'user',   'content' => $prompt],
        ]);

        $analysis = trim($result['choices'][0]['message']['content'] ?? '');

        if (empty($analysis)) {
            // Fallback para Gemini se DeepSeek falhar
            try {
                $gemini   = new \App\Services\GeminiService();
                $gRes     = $gemini->generateText($prompt);
                $analysis = trim($gRes['candidates'][0]['content']['parts'][0]['text'] ?? '');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('aiAnalyze: Gemini fallback também falhou — ' . $e->getMessage());
            }
        }

        if (empty($analysis)) {
            return response()->json(['error' => 'Não foi possível gerar a análise. Verifique as chaves de API no painel admin.'], 500);
        }

        $grant->ai_analysis = $analysis;
        $grant->save();

        return response()->json(['analysis' => $analysis]);
    }

    public function uploadDocument(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $grant = NgoGrant::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:edital,plano_trabalho,anexo,comprovante,outros',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('file');
        $disk = 'local'; // disco privado — não acessível via URL pública
        $dir = "ngo_grants/{$tenantId}/{$grant->id}";
        $path = $file->store($dir, $disk);

        NgoGrantDocument::create([
            'tenant_id' => $tenantId,
            'ngo_grant_id' => $grant->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Documento anexado com sucesso!');
    }

    public function downloadDocument($id, $docId)
    {
        $tenantId = auth()->user()->tenant_id;
        $grant = NgoGrant::where('tenant_id', $tenantId)->findOrFail($id);
        $doc = NgoGrantDocument::where('ngo_grant_id', $grant->id)->findOrFail($docId);

        // Tenta disco local (privado) primeiro; fallback para public (arquivos legados)
        $disk = Storage::disk('local')->exists($doc->file_path) ? 'local' : 'public';
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

        $filename = basename($doc->original_name ?: $doc->file_path);
        return Storage::disk($disk)->download($doc->file_path, $filename);
    }

    public function deleteDocument($id, $docId)
    {
        $grant = NgoGrant::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
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
            Log::error('NgoGrant analyze error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao processar resposta da IA. Tente novamente.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        if (isset($data['total_amount'])) {
            $data['total_amount'] = sanitize_br_currency($data['total_amount']);
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

        // Create associated Project
        Project::create([
            'tenant_id' => $grant->tenant_id,
            'name' => $grant->title,
            'description' => $grant->notes,
            'budget' => $grant->value,
            'start_date' => $grant->start_date,
            'end_date' => $grant->deadline,
            'status' => 'active',
            'ngo_grant_id' => $grant->id,
        ]);

        return redirect('/ngo/grants')->with('success', 'Edital/Convênio registrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $grant = NgoGrant::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $data = $request->all();
        if (isset($data['total_amount'])) {
            $data['total_amount'] = sanitize_br_currency($data['total_amount']);
        }

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'title'           => 'required|string|max:255',
            'grantor_name'    => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:100',
            'total_amount'    => 'required|numeric',
            'start_date'      => 'nullable|date',
            'end_date'        => 'required|date',
            'notes'           => 'nullable|string|max:20000',
        ])->validate();

        $grant->title           = $validated['title'];
        $grant->agency          = $validated['grantor_name'];
        $grant->contract_number = $validated['contract_number'] ?? null;
        $grant->value           = $validated['total_amount'];
        $grant->start_date      = $validated['start_date'] ?? null;
        $grant->deadline        = $validated['end_date'];
        $grant->notes           = $validated['notes'] ?? null;
        $grant->save();

        // Sync project metadata
        if ($grant->project) {
            $grant->project->update([
                'name'       => $grant->title,
                'description'=> $grant->notes,
                'budget'     => $grant->value,
                'start_date' => $grant->start_date,
                'end_date'   => $grant->deadline,
            ]);
        }

        return redirect()->route('ngo.grants.show', $grant->id)->with('success', 'Convênio atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $grant = NgoGrant::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        // Delete associated stored files (DB cascade won't trigger Eloquent events for docs).
        try {
            $tenantId = auth()->user()->tenant_id;
            Storage::disk('public')->deleteDirectory("ngo_grants/{$tenantId}/{$grant->id}");
        } catch (\Throwable $e) {
            // Keep flow stable.
        }

        // Associated project will be deleted by DB cascade if foreign key is set correctly,
        // but we can also do it explicitly if we want to trigger Eloquent events.
        if ($grant->project) {
            $grant->project->delete();
        }

        $grant->delete();
        return redirect('/ngo/grants')->with('success', 'Convênio/Edital excluído com sucesso!');
    }
}
