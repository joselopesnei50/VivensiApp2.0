<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\ProjectGoal;
use App\Models\ProjectLog;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTimelineRecord;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BruceAiService — Assistente contextual DeepSeek com memória por tenant.
 *
 * - Mantém histórico de conversa (Redis, TTL 30 min, max 10 mensagens)
 * - Injeta métricas do tenant no system prompt automaticamente
 * - Usa DeepSeek como provider principal, sem dependência de Copilot
 */
class BruceAiService
{
    const HISTORY_TTL = 3600; // 1 hora
    const MAX_HISTORY = 20;   // mensagens mantidas por tenant

    public function __construct(
        private DeepSeekService $deepSeek,
        private TenantContextService $tenantCtx,
    ) {}

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function chat(
        string $userMessage,
        int $tenantId,
        string $role = 'common',
        int $userId = 0,
        ?string $contextType = null,
        ?int $contextId = null
    ): array {
        $history = $this->getHistory($tenantId, $userId, $contextType, $contextId);

        // Bruno — se ainda nao identificou o segmento, tenta pela ultima msg
        // do usuario. Uma vez identificado, cachea e injeta no prompt pra
        // (a) evitar re-perguntar (b) filtrar o painel relevante.
        // Segundo nivel: dentro de segment=mei, tenta detectar business_type
        // (mei/autonomo/pj_simples) pra tailored pitching.
        $segment      = null;
        $businessType = null;
        if ($role === 'sales_bot') {
            $segment = Cache::get($this->segmentKey($tenantId, $userId));
            if (!$segment) {
                $detected = $this->detectSegment($userMessage);
                if ($detected) {
                    Cache::put($this->segmentKey($tenantId, $userId), $detected, self::HISTORY_TTL);
                    $segment = $detected;
                }
            }

            // business_type so faz sentido dentro do segment mei
            if ($segment === 'mei') {
                $businessType = Cache::get($this->businessTypeKey($tenantId, $userId));
                if (!$businessType) {
                    $detected = $this->detectBusinessType($userMessage);
                    if ($detected) {
                        Cache::put($this->businessTypeKey($tenantId, $userId), $detected, self::HISTORY_TTL);
                        $businessType = $detected;
                    }
                }
            }
        }

        $systemPrompt = $role === 'sales_bot'
            ? $this->buildSalesBotPrompt($tenantId, Cache::get($this->qualificationKey($tenantId, $userId)), $segment, $businessType)
            : $this->buildSystemPrompt($tenantId, $role, $contextType, $contextId);

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $userMessage]]
        );

        // Bruno tem ferramentas (consultar slots de agenda, criar agendamento).
        // Loop até resposta final (sem tool_calls) ou limite de 5 iterações.
        $tools  = $role === 'sales_bot' ? \App\Services\Bruno\BrunoTools::definitions() : null;
        $reply  = '';
        $tokens = 0;
        $maxIter = $tools ? 5 : 1;

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $response = $this->deepSeek->chat($messages, null, $tools);

            if (isset($response['error'])) {
                return ['error' => $response['error']];
            }

            $assistantMsg = data_get($response, 'choices.0.message', []);
            $tokens      += (int) data_get($response, 'usage.total_tokens', 0);
            $toolCalls    = $assistantMsg['tool_calls'] ?? null;

            if (empty($toolCalls)) {
                $reply = $assistantMsg['content'] ?? 'Não consegui processar sua mensagem.';
                break;
            }

            // O LLM pediu pra rodar uma ou mais tools. Anexa a assistant msg
            // (que contém os tool_calls) E o resultado de cada tool, depois
            // chama de novo pra LLM redigir a resposta final.
            $messages[] = $assistantMsg;

            foreach ($toolCalls as $tc) {
                $name = data_get($tc, 'function.name', '');
                $args = json_decode(data_get($tc, 'function.arguments', '{}'), true) ?: [];
                $result = \App\Services\Bruno\BrunoTools::execute($name, $args);

                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $tc['id'] ?? '',
                    'name'         => $name,
                    'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if ($reply === '') {
            $reply = 'Não consegui concluir essa solicitação agora. Pode tentar de novo?';
        }

        // Pos-processamento: Bruno (sales_bot) responde em WhatsApp, que não
        // renderiza Markdown. LLM as vezes ignora a regra do prompt — strip
        // forçado garante texto limpo.
        if ($role === 'sales_bot') {
            $reply = $this->stripMarkdown($reply);
        }

        // Persistir histórico (user + assistant) — chave isolada por contexto
        $newHistory = array_merge($history, [
            ['role' => 'user',      'content' => $userMessage],
            ['role' => 'assistant', 'content' => $reply],
        ]);

        // Manter apenas as últimas N mensagens
        if (count($newHistory) > self::MAX_HISTORY) {
            $newHistory = array_slice($newHistory, -self::MAX_HISTORY);
        }

        $this->saveHistory($tenantId, $userId, $newHistory, $contextType, $contextId);

        // Qualificação automática do lead (sales_bot apenas). Dispara a cada
        // 4 mensagens do user (4, 8, 12...). Resultado é cacheado e injetado
        // no system prompt do PRÓXIMO turno, ajustando o tom do Bruno.
        // Qualificação automática (sales_bot apenas). A partir da 3ª mensagem
        // do lead, qualifica a cada turno e cacheia o resultado. Em caso de
        // falha do provedor, devolve o último cache disponível.
        $qualification = null;
        if ($role === 'sales_bot') {
            $userCount = count(array_filter($newHistory, fn ($m) => ($m['role'] ?? '') === 'user'));
            if ($userCount >= 3) {
                try {
                    $qual = app(\App\Services\Messaging\LeadQualificationService::class)
                        ->qualifyMessages($newHistory, 'Lead Sandbox');
                    if (empty($qual['error'])) {
                        Cache::put($this->qualificationKey($tenantId, $userId), $qual, self::HISTORY_TTL);
                        $qualification = $qual;
                    } else {
                        Log::warning('Bruno: qualifyMessages devolveu erro', ['err' => $qual['error']]);
                        $qualification = Cache::get($this->qualificationKey($tenantId, $userId));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Bruno: qualifyMessages exception', ['err' => $e->getMessage()]);
                    $qualification = Cache::get($this->qualificationKey($tenantId, $userId));
                }
            } else {
                $qualification = Cache::get($this->qualificationKey($tenantId, $userId));
            }
        }

        return [
            'reply'         => $reply,
            'tokens'        => $tokens,
            'timestamp'     => now()->toIso8601String(),
            'context'       => $contextType ? ['type' => $contextType, 'id' => $contextId] : null,
            'qualification' => $qualification,
        ];
    }

    private function qualificationKey(int $tenantId, int $userId): string
    {
        return "bruno.qualification.{$tenantId}.{$userId}";
    }

    private function segmentKey(int $tenantId, int $userId): string
    {
        return "bruno.segment.{$tenantId}.{$userId}";
    }

    private function businessTypeKey(int $tenantId, int $userId): string
    {
        return "bruno.business_type.{$tenantId}.{$userId}";
    }

    // Heuristica leve pra identificar o segmento do lead pela mensagem.
    // Retorna 'ongs' | 'mei' | 'gestor' | null. Prefere numeros explicitos
    // (1/2/3), depois palavras-chave. Case-insensitive, tolera acentos.
    private function detectSegment(string $userMessage): ?string
    {
        $t = $this->normalizeForDetection($userMessage);

        // Respostas numericas puras ou com prefixo comum ('1', '1)', 'opcao 1', 'sou o 1').
        if (preg_match('/(^|\D)1(\D|$)/', $t) && !preg_match('/1[0-9]/', $t)) return 'ongs';
        if (preg_match('/(^|\D)2(\D|$)/', $t) && !preg_match('/2[0-9]/', $t)) return 'mei';
        if (preg_match('/(^|\D)3(\D|$)/', $t) && !preg_match('/3[0-9]/', $t)) return 'gestor';

        // Palavras-chave por segmento — ordem importa (mais especifico primeiro).
        if (preg_match('/\b(ong|osc|terceiro setor|instituto|associacao|fundacao|filantropia|assistencia social|voluntari)/', $t)) return 'ongs';
        if (preg_match('/\b(mei|micro ?empreendedor|pequeno negocio|autonomo|freelance|profissional liberal)/', $t)) return 'mei';
        if (preg_match('/\b(gestor|gerente de projetos|pme|empresa|equipe|escritorio|consultoria|agencia)/', $t)) return 'gestor';

        return null;
    }

    /**
     * Segundo nivel de detecao — dentro do segmento MEI (segmento 2), tenta
     * distinguir MEI de fato vs autonomo vs PJ simples. Casa com os valores
     * da coluna tenants.business_type. So faz sentido chamar quando o
     * segmento ja foi identificado como 'mei'.
     *
     * Retorna 'mei' | 'autonomo' | 'pj_simples' | null.
     */
    private function detectBusinessType(string $userMessage): ?string
    {
        $t = $this->normalizeForDetection($userMessage);

        // Ordem importa: mais especifico primeiro. MEI e o mais claro.
        if (preg_match('/\b(mei|micro ?empreendedor|cnpj mei|simei|das mei|teto mei|simples mei)\b/', $t)) return 'mei';

        // Autonomo — sem CNPJ formal ou explicitamente autonomo/freelance
        if (preg_match('/\b(autonomo|autonoma|freelance|freelancer|sem cnpj|profissional liberal|prestador de servico|presto servico)\b/', $t)) return 'autonomo';

        // PJ Simples / pequena empresa (mas nao MEI)
        if (preg_match('/\b(simples nacional|pequena empresa|micro empresa|me\b|epp|ltda|pj|pessoa juridica|razao social)\b/', $t)
            && !preg_match('/\bmei\b/', $t)) {
            return 'pj_simples';
        }

        return null;
    }

    private function normalizeForDetection(string $s): string
    {
        $s = mb_strtolower(trim($s));
        return strtr($s, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
    }

    public function clearHistory(int $tenantId, int $userId = 0, ?string $contextType = null, ?int $contextId = null): void
    {
        Cache::forget($this->historyKey($tenantId, $userId, $contextType, $contextId));
    }

    /**
     * Remove sintaxe Markdown comum da resposta antes de devolver pro canal
     * WhatsApp (que não renderiza). Defesa em profundidade — o prompt já pede
     * pra não usar, mas o LLM as vezes escapa.
     */
    private function stripMarkdown(string $text): string
    {
        // Negrito **texto** → texto, e __texto__ → texto
        $text = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text);
        $text = preg_replace('/__(.+?)__/u', '$1', $text);
        // Itálico *texto* → texto, e _texto_ → texto (evita pegar **)
        $text = preg_replace('/(?<!\*)\*(?!\*)([^\*\n]+?)\*(?!\*)/u', '$1', $text);
        $text = preg_replace('/(?<!_)_(?!_)([^_\n]+?)_(?!_)/u', '$1', $text);
        // Strike ~~texto~~ → texto
        $text = preg_replace('/~~(.+?)~~/u', '$1', $text);
        // Headers no início de linha: ### Texto → Texto
        $text = preg_replace('/^#{1,6}\s+/mu', '', $text);
        // Bullets no início de linha: "- item" ou "* item" → "item"
        $text = preg_replace('/^[\-\*]\s+/mu', '', $text);
        // Múltiplas linhas em branco viram uma só
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    // ── Insight proativo (usado no dashboard, cache 6h) ───────────────────────

    public function dailyInsight(int $tenantId, string $role = 'common'): string
    {
        $cacheKey = "bruce.insight.{$tenantId}." . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 21600, function () use ($tenantId, $role) {
            $ctx = $this->tenantCtx->for($tenantId);

            $prompt = "Em no máximo 2 frases diretas e profissionais, dê um insight acionável sobre a situação financeira/operacional atual. "
                . "Sem saudação, sem emojis, sem menções a animais. "
                . "Dados: saldo R$ {$this->fmt($ctx['balance'])}, receitas R$ {$this->fmt($ctx['income'])}, "
                . "despesas R$ {$this->fmt($ctx['expense'])}, {$ctx['active_projects']} projetos ativos, "
                . "{$ctx['overdue_tasks']} tarefas vencidas.";

            $response = $this->deepSeek->chat([
                ['role' => 'system', 'content' => $this->buildSystemPrompt($tenantId, $role)],
                ['role' => 'user',   'content' => $prompt],
            ]);

            return data_get($response, 'choices.0.message.content', '')
                ?: 'Adicione mais transações para gerar insights precisos.';
        });
    }

    // ── System prompt contextual ──────────────────────────────────────────────

    private function buildSystemPrompt(int $tenantId, string $role, ?string $contextType = null, ?int $contextId = null): string
    {
        // Defaults garantem que cache stale (de versoes antigas do array) nao
        // explode com Undefined array key. tenantContext sempre retorna todas
        // as chaves; mas se um deploy anterior tiver cacheado um shape menor,
        // o merge cobre o vazio.
        $ctx = array_merge([
            'income'          => 0.0,
            'expense'         => 0.0,
            'balance'         => 0.0,
            'active_projects' => 0,
            'open_tasks'      => 0,
            'overdue_tasks'   => 0,
            'org_name'        => null,
            'org_type'        => null,
            'ai_training'     => null,
        ], $this->tenantCtx->for($tenantId));

        $roleContext = match ($role) {
            'ngo'         => "O usuário gerencia uma ONG/OSC. Use termos do terceiro setor: doadores, editais, captação, beneficiários, voluntários, prestação de contas, transparência. Jamais use termos de SaaS, startup, MRR ou ARR.",
            'manager'     => "O usuário é gestor de projetos e equipes. Foque em: projetos, tarefas, produtividade, aprovações de despesas, fluxo de caixa e desempenho da equipe.",
            'super_admin' => "O usuário é o administrador da plataforma Vivensi. Ele gerencia todos os clientes (tenants), planos de assinatura, saúde do sistema e pipeline comercial. Forneça visão estratégica de plataforma: MRR, churn, conversão de leads, crescimento de clientes ativos, saúde da infraestrutura.",
            default       => "O usuário gerencia suas finanças e tarefas pessoais/empresariais. Foque em: saldo, receitas, despesas, fluxo de caixa, tarefas pendentes e metas financeiras.",
        };

        $systemCapabilities = <<<CAP
## Funcionalidades do sistema Vivensi que você pode explicar:
- **Finanças**: lançar receitas e despesas, aprovar/rejeitar transações, conciliação bancária, relatórios financeiros por período, fluxo de caixa semestral.
- **Projetos**: criar e acompanhar projetos com orçamento, progresso de tarefas, equipe alocada, relatório de gastos por projeto.
- **Tarefas**: criar tarefas, atribuir a membros, definir prazo e prioridade (crítica/alta/média/baixa), acompanhar status.
- **WhatsApp CRM**: atender clientes/contatos via chat, disparar mensagens em massa, configurar bot com IA, automações.
- **Equipe/RH**: cadastrar membros, definir hierarquia, acompanhar supervisor e departamento.
- **Clientes/Prospecção**: gestão de clientes, funil de prospecção com análise por IA.
- **Marketing**: estratégias de marketing geradas por IA, hub de conteúdo para redes sociais.
- **Landing Pages**: criar páginas de captura vinculadas à conta.
- **Relatórios**: exportar dados em CSV, relatórios de auditoria, log de atividades.
- **Configurações**: integrações (WhatsApp, pagamentos), dados da organização, personalização de marca.
CAP;

        $orgBlock      = $ctx['org_name']
            ? "## ORGANIZAÇÃO\nVocê está respondendo para **{$ctx['org_name']}**" . ($ctx['org_type'] ? " ({$ctx['org_type']})" : '') . ".\n"
            : '';

        $trainingBlock = $ctx['ai_training']
            ? "## INSTRUÇÕES ESPECÍFICAS DA ORGANIZAÇÃO\n{$ctx['ai_training']}\n"
            : '';

        $contextBlock = '';
        if ($contextType === 'project' && $contextId && config('bruce.context_project_enabled', false)) {
            $contextBlock = $this->projectContext($contextId, $tenantId);
        }

        return <<<PROMPT
Você é Bruce, assistente de inteligência artificial do sistema Vivensi.

## IDENTIDADE E TOM (OBRIGATÓRIO — nunca ignore estas regras)
- Você é um assistente profissional, direto e inteligente.
- Tom: formal mas acessível. Nunca use linguagem infantil, piadas ou metáforas de animais.
- PROIBIDO absolutamente: referências a cachorro, Golden Retriever, latir, 🐾, ou qualquer linguagem de mascote/animal. Isso é inadequado e ofensivo.
- NÃO use emojis excessivos. No máximo 1 emoji por resposta, apenas quando realmente agrega valor.
- Não se apresente repetidamente. Se o usuário já iniciou uma conversa, responda diretamente ao que foi perguntado.
- Respostas curtas para perguntas simples. Use Markdown (negrito, listas) quando organiza melhor a informação.
- Idioma: português do Brasil.

{$orgBlock}
## PAPEL
{$roleContext}

{$trainingBlock}
{$systemCapabilities}

## DADOS ATUAIS DA CONTA (em tempo real)
- Receitas do mês: R$ {$this->fmt($ctx['income'])}
- Despesas do mês: R$ {$this->fmt($ctx['expense'])}
- Saldo do mês: R$ {$this->fmt($ctx['balance'])}
- Projetos ativos: {$ctx['active_projects']}
- Tarefas em aberto: {$ctx['open_tasks']}
- Tarefas vencidas: {$ctx['overdue_tasks']}
- Data: {$this->today()}

Use esses dados para responder perguntas sobre finanças, projetos e tarefas sem pedir que o usuário os forneça novamente.

{$contextBlock}
PROMPT;
    }

    /**
     * System prompt do Bot Vendedor "Bruno" (vide docs/bot-vendedor.md).
     * Lê KB de config/prompts/bot-vendedor.php — editável sem deploy de código.
     */
    private function buildSalesBotPrompt(int $tenantId, ?array $qualification = null, ?string $segment = null, ?string $businessType = null): string
    {
        $kb = config('bot-vendedor');
        if (!is_array($kb)) {
            return 'Você é Bruno, consultor comercial Vivensi. (KB do bot-vendedor não carregada — verificar config/bot-vendedor.php)';
        }

        // Bloco de status do lead — injetado quando há qualificação cacheada.
        // Bruno usa isso pra adaptar tom: frio → educar, morno → descoberta,
        // quente → propor demo ou link de pagamento.
        $qualBlock = '';
        if (!empty($qualification['qualification'])) {
            $q       = $qualification['qualification'];
            $conf    = round(((float) ($qualification['confidence'] ?? 0)) * 100);
            $intent  = $qualification['intent']      ?? null;
            $summary = $qualification['summary']     ?? '';
            $action  = $qualification['next_action'] ?? '';

            $estrategia = match ($q) {
                'frio'   => 'EDUQUE — apresente diferenciais relevantes e descubra a dor real. NÃO ofereça demo ainda.',
                'morno'  => 'DESCUBRA — aprofunde 1-2 perguntas SPIN antes de propor demo. Mencione um case real se couber.',
                'quente' => 'FECHE — proponha agendamento de demo (use a tool consultar_slots) OU link de pagamento.',
                default  => 'Continue a descoberta natural.',
            };

            $qualBlock = "\n### STATUS ATUAL DO LEAD (atualizado automaticamente pela IA de qualificação)\n"
                . "- Classificação: {$q} ({$conf}% de confiança)\n"
                . ($intent ? "- Intenção: {$intent}\n" : '')
                . ($summary ? "- Resumo: {$summary}\n" : '')
                . ($action ? "- Próxima ação sugerida: {$action}\n" : '')
                . "- Estratégia recomendada: {$estrategia}\n";
        }

        $persona       = $kb['persona']             ?? [];
        $product       = $kb['product']             ?? [];
        $discovery     = $kb['discovery_framework'] ?? [];
        $objections    = $kb['objections']          ?? [];
        $escalation    = $kb['escalation']          ?? [];
        $ctas          = $kb['ctas']                ?? [];
        $fewShot       = $kb['few_shot']            ?? [];
        $links         = $kb['links']               ?? [];
        $lgpd          = $kb['lgpd']                ?? [];
        $agendamento   = $kb['agendamento']         ?? [];

        $personaRules = isset($persona['rules']) && is_array($persona['rules'])
            ? implode("\n", array_map(fn ($r) => "- {$r}", $persona['rules']))
            : '';

        $differentials = isset($product['differentials']) && is_array($product['differentials'])
            ? implode("\n", array_map(fn ($d) => "- {$d}", $product['differentials']))
            : '';

        $verticalsBlock = isset($product['verticals']) && is_array($product['verticals'])
            ? implode("\n", array_map(fn ($k, $v) => "- **{$k}**: {$v}", array_keys($product['verticals']), $product['verticals']))
            : '';

        // Catálogo detalhado de funcionalidades por painel — fonte da verdade
        // pra Bruno responder "o que vocês têm" sem inventar nada.
        // Quando o segmento ja foi identificado (cache), filtra pro painel
        // relevante — economiza tokens e afia a resposta.
        $segmentToPanelKey = ['ongs' => 'terceiro_setor', 'mei' => 'mei', 'gestor' => 'gestor'];
        $panelKeyForSegment = $segment ? ($segmentToPanelKey[$segment] ?? null) : null;

        $panelsBlock = '';
        $segmentBlock = '';
        if (isset($product['panels']) && is_array($product['panels'])) {
            foreach ($product['panels'] as $key => $painel) {
                if ($panelKeyForSegment && $key !== $panelKeyForSegment) {
                    continue; // segmento definido — inclui apenas o painel relevante
                }
                $panelsBlock .= "\n#### {$painel['titulo']}\n";
                foreach (($painel['grupos'] ?? []) as $grupo => $itens) {
                    $itensTxt = is_array($itens) ? implode('; ', $itens) : (string) $itens;
                    $panelsBlock .= "- {$grupo}: {$itensTxt}\n";
                }
            }
        }

        if ($segment) {
            $segmentLabel = ['ongs' => 'ONG/OSC (Terceiro Setor)', 'mei' => 'Pequeno Negocio (MEI, autonomo ou PJ)', 'gestor' => 'Gestor de projetos / PME'][$segment] ?? $segment;
            $segmentBlock = "\n### SEGMENTO IDENTIFICADO DO LEAD\n"
                . "- Segmento: {$segmentLabel}\n"
                . "- NAO pergunte de novo qual e o segmento — ja foi respondido.\n"
                . "- Foque as respostas nas funcionalidades do painel acima. Nao mencione features dos outros paineis salvo se o lead perguntar diretamente.\n";

            // Sub-refinamento: dentro de segment=mei, se ja identificamos o
            // business_type especifico, injeta guidance tailored.
            if ($segment === 'mei' && $businessType) {
                $btKb = $kb['business_type_context'][$businessType] ?? null;
                if (is_array($btKb) && !empty($btKb['label'])) {
                    $segmentBlock .= "\n### TIPO ESPECIFICO IDENTIFICADO ({$btKb['label']})\n"
                        . (!empty($btKb['pitch'])     ? "- Pitch: {$btKb['pitch']}\n" : '')
                        . (!empty($btKb['destacar']) && is_array($btKb['destacar'])
                            ? "- Destaque estas features: " . implode('; ', $btKb['destacar']) . "\n"
                            : '')
                        . (!empty($btKb['evitar']) && is_array($btKb['evitar'])
                            ? "- NAO mencione (nao se aplica a este perfil): " . implode('; ', $btKb['evitar']) . "\n"
                            : '')
                        . (!empty($btKb['dores']) && is_array($btKb['dores'])
                            ? "- Dores tipicas: " . implode('; ', $btKb['dores']) . "\n"
                            : '');
                }
            }
        } else {
            $segmentBlock = "\n### ABERTURA COM LEAD NOVO\n"
                . "- Se essa e a primeira mensagem util do lead (ele acabou de chegar e nao disse o segmento ainda), sua PRIMEIRA resposta deve OBRIGATORIAMENTE perguntar em qual cenario ele atua, usando exatamente estes tres:\n"
                . "  1) ONG ou OSC (Terceiro Setor)\n"
                . "  2) MEI, autonomo ou pequena empresa\n"
                . "  3) Gestor de projetos ou PME\n"
                . "- Formato sugerido (adapte tom, mas mantenha as 3 opcoes numeradas): 'Oi, tudo bem? Sou o Bruno, da Vivensi. Antes de te ajudar melhor, me conta rapidinho: voce atua em qual desses cenarios? 1) ONG ou OSC 2) MEI, autonomo ou pequena empresa 3) Gestor de projetos ou PME'\n"
                . "- Se o lead ja falou algo que revela o segmento (ex: 'sou de uma ong'), NAO pergunte — siga direto pra descoberta focada.\n";
        }

        // Detalhes do WhatsApp (Evolution vs Meta) + treinamento do bot.
        $waBlock = '';
        $waDet = $product['whatsapp_detalhes'] ?? [];
        if (!empty($waDet['evolution'])) {
            $waBlock .= "\n#### {$waDet['evolution']['titulo']}\n";
            foreach (($waDet['evolution']['pontos'] ?? []) as $p) {
                $waBlock .= "- {$p}\n";
            }
        }
        if (!empty($waDet['meta_oficial'])) {
            $waBlock .= "\n#### {$waDet['meta_oficial']['titulo']}\n";
            foreach (($waDet['meta_oficial']['pontos'] ?? []) as $p) {
                $waBlock .= "- {$p}\n";
            }
        }
        if (!empty($waDet['treinamento_bot'])) {
            $tb = $waDet['treinamento_bot'];
            $waBlock .= "\n#### Treinamento da Bruce AI\n";
            $waBlock .= "- Onde: {$tb['onde']}\n";
            $waBlock .= "- Como: {$tb['como']}\n";
            if (!empty($tb['exemplos_de_instrucao'])) {
                $waBlock .= "- Exemplos de instrução do cliente:\n";
                foreach ($tb['exemplos_de_instrucao'] as $ex) {
                    $waBlock .= "  * \"{$ex}\"\n";
                }
            }
        }

        $cases = isset($product['cases']) && is_array($product['cases'])
            ? array_values(array_filter($product['cases']))
            : [];
        $casesBlock = !empty($cases)
            ? implode("\n", array_map(fn ($c) => "- {$c}", $cases))
            : 'Ainda não temos cases publicáveis. NUNCA invente números, nomes ou histórias de clientes. Se o lead pedir referências, ofereça conectar com a Cristiane.';

        $notForBlock = isset($product['not_for']) && is_array($product['not_for'])
            ? implode("\n", array_map(fn ($n) => "- {$n}", $product['not_for']))
            : '';

        $discoveryBlock = '';
        foreach (['situacao', 'problema', 'implicacao', 'necessidade'] as $stage) {
            if (!empty($discovery[$stage])) {
                $discoveryBlock .= "- **{$stage}**: \"{$discovery[$stage]}\"\n";
            }
        }
        $discoveryRule = $discovery['rule'] ?? '';

        $objectionsBlock = '';
        foreach ($objections as $o) {
            $objectionsBlock .= "- **\"{$o['objection']}\"** → {$o['reply']}\n";
        }

        $escalationTriggers = isset($escalation['triggers']) && is_array($escalation['triggers'])
            ? implode("\n", array_map(fn ($t) => "- {$t}", $escalation['triggers']))
            : '';
        $humanName    = $escalation['human_name']      ?? '{{NOME_HUMANO}}';
        $humanEta     = $escalation['human_eta_hours'] ?? 2;
        $bizHours     = $escalation['business_hours']  ?? '';
        $escalationAction = $escalation['action']      ?? '';

        $ctasBlock = '';
        foreach ($ctas as $level => $cta) {
            $ctasBlock .= "- **{$level}**: {$cta}\n";
        }

        $fewShotBlock = '';
        foreach ($fewShot as $i => $ex) {
            $n = $i + 1;
            $fewShotBlock .= "### Exemplo {$n} — {$ex['situacao']}\n";
            $fewShotBlock .= "Lead: \"{$ex['lead']}\"\n";
            $fewShotBlock .= "Bruno: \"{$ex['bruno']}\"\n\n";
        }

        $linksBlock = '';
        foreach ($links as $k => $v) {
            $linksBlock .= "- {$k}: {$v}\n";
        }

        $lgpdBlock = '';
        if (!empty($lgpd['pitch'])) {
            $lgpdBlock .= "\nPitch: {$lgpd['pitch']}\n\nMecanismos disponíveis:\n";
            foreach (($lgpd['mecanismos'] ?? []) as $m) {
                $lgpdBlock .= "- {$m}\n";
            }
        }

        return <<<PROMPT
Você é {$persona['name']}, {$persona['role']}.

## TOM E IDENTIDADE (OBRIGATÓRIO)
{$persona['tone']}

{$personaRules}
{$segmentBlock}
{$qualBlock}
## PRODUTO — VIVENSI
{$product['short_pitch']}

### Verticais atendidas
{$verticalsBlock}

### Catálogo de funcionalidades por painel (CONSULTE SEMPRE — não invente, não omita)
Quando o lead perguntar "o que vocês têm pra X?" ou "no plano X eu consigo fazer Y?", use ESTA lista. Não cite funcionalidades fora dela.

REGRA DE OURO ANTI-ALUCINACAO: quando o lead perguntar sobre uma feature específica ("vocês têm assinatura digital em contratos?", "tem NF-e?", "tem kanban?") e você NÃO tiver certeza absoluta de que a feature está no catálogo abaixo, CHAME a ferramenta verificar_feature(nome, painel) ANTES de responder. Ela consulta a KB oficial. Se a tool devolver exists=false, seja honesto ("hoje não temos") + escala pra humano quando fizer sentido. NUNCA afirme que uma feature existe sem ter checado (no catálogo ou via tool).
{$panelsBlock}

### WhatsApp — Opções e Treinamento da Bruce AI (use esta seção para perguntas sobre WhatsApp e bot)
O cliente escolhe entre Evolution API (nativa, sem custo extra) e WhatsApp Oficial Meta (sob custos da Meta). NUNCA diga que só temos uma das duas — temos as duas, integradas.
{$waBlock}

### LGPD e Proteção de Dados (importante pra ONGs, empresas e qualquer lead que lida com dados pessoais — mencione PROATIVAMENTE)
{$lgpdBlock}

### AGENDAMENTO INLINE (você TEM ferramentas pra agendar diretamente no chat)
Duração da demo: {$agendamento['duracao']}
Página pública: {$agendamento['pagina_publica']}

Instrução:
{$agendamento['instrucao']}

### Diferenciais
{$differentials}

### Planos e Preços (você TEM a ferramenta consultar_planos)
REGRA DE PREÇO: você NÃO sabe os preços de memória. Quando o lead perguntar preço, valor, plano ou "quanto custa", CHAME a ferramenta consultar_planos(painel) — ela retorna os planos reais e atualizados (nome, preço mensal, anual e recursos). Se o segmento do lead já foi identificado, passe o painel dele. Cite os valores EXATAMENTE como retornados. Se a ferramenta não retornar plano pro perfil, diga "sob consulta" e ofereça demo ou a Cristiane. NUNCA invente preço, desconto ou promoção.

### Cases reais (prova social)
{$casesBlock}

### Onde Vivensi NÃO é a melhor escolha (seja honesto)
{$notForBlock}

## FRAMEWORK DE DESCOBERTA (use ANTES de pitchar)
Encadeie 2-4 perguntas naturalmente — não despeje todas de uma vez:
{$discoveryBlock}

Regra: {$discoveryRule}

## OBJEÇÕES — COMO RESPONDER
{$objectionsBlock}

## ESCALADA PARA HUMANO
Escale automaticamente quando:
{$escalationTriggers}

Quando escalar: {$escalationAction}
Humano de plantão: {$humanName} (responde em até {$humanEta}h, horário comercial: {$bizHours}).

## CTAs (1 por resposta relevante — NUNCA pergunta vaga tipo "posso ajudar em algo mais?")
{$ctasBlock}

## EXEMPLOS DE CONVERSAS (few-shot — siga o estilo)

{$fewShotBlock}

## LINKS ÚTEIS
{$linksBlock}

## REGRAS FINAIS
- Nunca prometa feature que não está no catálogo acima (ou confirmada via verificar_feature).
- Nunca invente preços — consulte a ferramenta consultar_planos. Se ela não tiver o dado, diga "sob consulta" e escale.
- Se a pergunta sair completamente do escopo de venda (suporte técnico de cliente já ativo, dúvida operacional), diga "esse é um assunto pra equipe de sucesso — vou redirecionar" e escale.
- Idioma: português do Brasil.
- Data atual: {$this->today()}
PROMPT;
    }

    /**
     * Bloco de contexto específico de um projeto. Injetado no system prompt
     * quando context_type=project. Cacheado 5 min por projeto.
     * Quando o feature flag bruce.context_project_enabled está off, retorna ''.
     */
    private function projectContext(int $projectId, int $tenantId): string
    {
        $cacheKey = "bruce.proj_ctx.{$tenantId}.{$projectId}";

        return Cache::remember($cacheKey, 300, function () use ($projectId, $tenantId) {
            $project = Project::where('tenant_id', $tenantId)
                ->where('id', $projectId)
                ->first();

            if (!$project) {
                return '';
            }

            // KPIs operacionais (sem expor valores brutos — agregados)
            $openTasks    = Task::where('tenant_id', $tenantId)->where('project_id', $projectId)
                ->whereNotIn('status', ['done', 'completed'])->count();
            $doneTasks    = Task::where('tenant_id', $tenantId)->where('project_id', $projectId)
                ->whereIn('status', ['done', 'completed'])->count();
            $overdueTasks = Task::where('tenant_id', $tenantId)->where('project_id', $projectId)
                ->whereNotIn('status', ['done', 'completed'])
                ->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count();

            $totalSpent = (float) Transaction::where('tenant_id', $tenantId)
                ->where('project_id', $projectId)
                ->where('type', 'expense')
                ->where('status', 'paid')
                ->sum('amount');

            $memberCount = ProjectMember::where('tenant_id', $tenantId)
                ->where('project_id', $projectId)
                ->count();

            // Últimos 3 logs do diário (resumo curto)
            $lastLogs = ProjectLog::where('project_id', $projectId)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['created_at', 'body'])
                ->map(fn ($l) => '- ' . $l->created_at->format('d/m') . ': ' . \Illuminate\Support\Str::limit((string) $l->body, 140))
                ->implode("\n");

            // Próximo marco da timeline (registros historicos com data futura)
            $nextMilestone = ProjectTimelineRecord::where('project_id', $projectId)
                ->whereDate('date', '>=', now())
                ->orderBy('date', 'asc')
                ->first(['title', 'date', 'type']);

            // Planejamento (atras de feature flag — so injeta se a feature
            // estiver ligada para este ambiente)
            $planningBlock = '';
            if (config('planning.enabled')) {
                $goalCounts = ProjectGoal::where('tenant_id', $tenantId)
                    ->where('project_id', $projectId)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray();
                $goalsTotal = array_sum($goalCounts);

                $nextPlannedMilestone = ProjectMilestone::where('tenant_id', $tenantId)
                    ->where('project_id', $projectId)
                    ->where('status', 'pending')
                    ->whereDate('target_date', '>=', now())
                    ->orderBy('target_date', 'asc')
                    ->first(['title', 'target_date']);
                $overdueMilestones = ProjectMilestone::where('tenant_id', $tenantId)
                    ->where('project_id', $projectId)
                    ->where('status', 'pending')
                    ->whereDate('target_date', '<', now())
                    ->count();

                if ($goalsTotal > 0 || $nextPlannedMilestone || $overdueMilestones > 0) {
                    $goalsLine = $goalsTotal > 0
                        ? "Metas: {$goalsTotal} total (" .
                          ($goalCounts['completed'] ?? 0) . " concluidas, " .
                          ($goalCounts['in_progress'] ?? 0) . " em andamento, " .
                          ($goalCounts['not_started'] ?? 0) . " nao iniciadas)"
                        : 'Metas: nenhuma cadastrada';

                    $plannedMilestoneLine = $nextPlannedMilestone
                        ? "Proximo marco planejado: \"{$nextPlannedMilestone->title}\" em " . $nextPlannedMilestone->target_date->format('d/m/Y')
                        : 'Nenhum marco pendente futuro';

                    $overdueLine = $overdueMilestones > 0
                        ? "Marcos em atraso: {$overdueMilestones}"
                        : 'Marcos em atraso: 0';

                    $planningBlock = "\n### Planejamento\n- {$goalsLine}\n- {$plannedMilestoneLine}\n- {$overdueLine}";
                }
            }

            $statusLabel = match ($project->status) {
                'active'      => 'em execução',
                'paused'      => 'pausado',
                'completed'   => 'concluído',
                'canceled'    => 'cancelado',
                'in_progress' => 'em execução',
                default       => $project->status ?? 'sem status',
            };

            $budget = $project->budget ? 'R$ ' . $this->fmt((float) $project->budget) : 'sem orçamento definido';
            $usedPct = ($project->budget && $project->budget > 0) ? round(($totalSpent / $project->budget) * 100) : null;
            $budgetLine = $usedPct !== null
                ? "Orçamento: {$budget} (usado: {$usedPct}%)"
                : "Orçamento: {$budget}";

            $milestoneLine = $nextMilestone
                ? "Próximo marco: \"{$nextMilestone->title}\" em " . \Carbon\Carbon::parse($nextMilestone->date)->format('d/m/Y')
                : 'Próximo marco: nenhum agendado';

            $logsBlock = $lastLogs ? "\n## Últimas entradas no diário\n{$lastLogs}" : '';

            return <<<PCTX

## CONTEXTO DO PROJETO ATUAL
Você está respondendo perguntas sobre o projeto **{$project->name}** (ID #{$project->id}).
O usuário quer falar sobre ESTE projeto especificamente.

### Estado atual
- Status: {$statusLabel}
- {$budgetLine}
- Tarefas: {$doneTasks} concluídas / {$openTasks} abertas / {$overdueTasks} vencidas
- Equipe: {$memberCount} membros vinculados
- {$milestoneLine}
{$planningBlock}
{$logsBlock}

### Regras para este contexto
- Foque suas respostas neste projeto, salvo quando o usuário pedir comparação.
- Quando sugerir ações, prefira coisas que podem ser feitas dentro da tela do projeto (criar tarefa, registrar marco, escrever no diário, lançar transação, contatar membro).
- Não invente dados que não estão no estado acima. Se faltar info, diga que precisa ser registrada.
PCTX;
        });
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }

    private function today(): string
    {
        return now()->translatedFormat('d \d\e F \d\e Y');
    }

    // ── Redis history helpers ─────────────────────────────────────────────────

    private function historyKey(int $tenantId, int $userId, ?string $contextType = null, ?int $contextId = null): string
    {
        $base = "bruce.history.{$tenantId}.{$userId}";
        if ($contextType && $contextId) {
            return "{$base}.{$contextType}.{$contextId}";
        }
        return $base;
    }

    private function getHistory(int $tenantId, int $userId, ?string $contextType = null, ?int $contextId = null): array
    {
        return Cache::get($this->historyKey($tenantId, $userId, $contextType, $contextId), []);
    }

    private function saveHistory(int $tenantId, int $userId, array $messages, ?string $contextType = null, ?int $contextId = null): void
    {
        Cache::put($this->historyKey($tenantId, $userId, $contextType, $contextId), $messages, self::HISTORY_TTL);
    }
}
