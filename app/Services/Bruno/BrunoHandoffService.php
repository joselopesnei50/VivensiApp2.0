<?php

namespace App\Services\Bruno;

use App\Models\BrunoHandoff;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

/**
 * Gera briefing TL;DR + dados estruturados quando Bruno decide escalar
 * conversa pro atendimento humano. Persiste em bruno_handoffs pra o time
 * humano assumir sem re-ler o historico inteiro.
 *
 * Contrato:
 *   createHandoff(chat, ?reason)
 *     - Idempotente: se ja existe handoff pendente/assumido pro chat, retorna o existente.
 *     - Chama DeepSeek pra gerar briefing (falha silenciosa: registra sem briefing).
 *     - NAO desativa o bot — quem chama trata isso (WhatsappChat::update).
 */
class BrunoHandoffService
{
    public function __construct(private DeepSeekService $deepSeek)
    {
    }

    /**
     * Cria (ou retorna existente) handoff pra um chat. Idempotente.
     */
    public function createHandoff(WhatsappChat $chat, ?string $reason = null): BrunoHandoff
    {
        // Idempotencia: nao duplica se ja existe pendente ou assumido
        $existing = BrunoHandoff::where('whatsapp_chat_id', $chat->id)
            ->whereIn('status', [BrunoHandoff::STATUS_PENDING, BrunoHandoff::STATUS_ASSUMED])
            ->first();

        if ($existing) {
            return $existing;
        }

        $briefing = '';
        $structured = [];

        try {
            [$briefing, $structured] = $this->generateBriefing($chat);
        } catch (\Throwable $e) {
            Log::warning('BrunoHandoffService: falha ao gerar briefing, salvando sem IA', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
            $briefing = $reason
                ? "Bruno escalou — motivo: {$reason}. Briefing automatico indisponivel."
                : 'Bruno escalou. Briefing automatico indisponivel — leia historico completo abaixo.';
        }

        return BrunoHandoff::create([
            'tenant_id'        => $chat->tenant_id,
            'whatsapp_chat_id' => $chat->id,
            'briefing'         => $briefing,
            'structured_data'  => $structured,
            'status'           => BrunoHandoff::STATUS_PENDING,
        ]);
    }

    /**
     * Chama DeepSeek pra gerar briefing + dados estruturados.
     *
     * @return array{0: string, 1: array} [briefing, structured_data]
     */
    public function generateBriefing(WhatsappChat $chat): array
    {
        $messages = WhatsappMessage::where('chat_id', $chat->id)
            ->orderBy('created_at', 'desc')
            ->limit(40)
            ->get()
            ->reverse()
            ->values();

        if ($messages->isEmpty()) {
            return ['Conversa vazia — sem contexto.', []];
        }

        $historico = $messages->map(function ($m) {
            $ator = $m->direction === 'inbound' ? 'Lead' : 'Bruno';
            $conteudo = trim((string) $m->content);
            return "{$ator}: {$conteudo}";
        })->join("\n");

        $contatoNome = $chat->contact_name ?: 'desconhecido';
        $telefone    = $chat->contact_phone ?: $chat->wa_id;

        $systemPrompt = <<<PROMPT
Voce eh assistente que le conversa entre Bruno (vendedor Vivensi) e um lead do
Terceiro Setor e gera um briefing TL;DR pra o atendimento humano assumir
sem re-ler o historico inteiro.

O Vivensi eh um ECOSSISTEMA para o Terceiro Setor — nunca chame de ERP nem
CRM. Se voce for citar o produto no briefing, use "Vivensi" ou "ecossistema".

Sua resposta DEVE ser um JSON valido, exatamente neste formato:

{
  "briefing": "Texto de 2-3 paragrafos, corrido, tom profissional pra humano ler em 20s. Fala: (1) quem eh o lead + organizacao, (2) principal dor detectada, (3) onde parou o funil, (4) proxima acao sugerida.",
  "structured_data": {
    "organizacao": "Nome da ONG/associacao/instituto ou null se nao mencionado",
    "tipo_organizacao": "ong | associacao | instituto | fundacao | mei | pj | outro | null",
    "cidade": "Cidade/UF ou null",
    "area_atuacao": "Assistencia social | Saude | Educacao | Cultura | Meio ambiente | Direitos humanos | outro | null",
    "orcamento_mencionado": "Valor mencionado (ex: 'R\$ 429/mes ok', 'sem verba') ou null",
    "urgencia": "alta | media | baixa",
    "fase_funil": "descoberta | qualificacao | apresentacao | proposta | fechamento",
    "sinais_compra": ["lista curta dos sinais detectados"],
    "objecoes_levantadas": ["lista curta das objecoes"],
    "proxima_acao": "Uma frase — o que o humano deve fazer primeiro"
  }
}

NAO invente dados. Se algo nao aparece na conversa, use null. NAO adicione
texto fora do JSON. NAO use markdown.
PROMPT;

        $userPrompt = <<<PROMPT
Contato: {$contatoNome} ({$telefone})

Conversa (mais antigas em cima):

{$historico}

Gere o JSON conforme instrucao.
PROMPT;

        $response = $this->deepSeek->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ], null, null, $chat->tenant_id);

        if (isset($response['error'])) {
            throw new \RuntimeException("DeepSeek erro: {$response['error']}");
        }

        $raw = (string) ($response['choices'][0]['message']['content'] ?? '');

        // Remove markdown code fences se DeepSeek enfiar (as vezes acontece)
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/mi', '', trim($raw));

        $parsed = json_decode($raw, true);

        if (!is_array($parsed) || !isset($parsed['briefing'])) {
            throw new \RuntimeException('Resposta DeepSeek nao eh JSON valido');
        }

        return [
            (string) $parsed['briefing'],
            is_array($parsed['structured_data'] ?? null) ? $parsed['structured_data'] : [],
        ];
    }

    public function assume(BrunoHandoff $handoff, int $userId): BrunoHandoff
    {
        $handoff->update([
            'status'     => BrunoHandoff::STATUS_ASSUMED,
            'assumed_by' => $userId,
            'assumed_at' => now(),
        ]);
        return $handoff->fresh();
    }

    public function resolve(BrunoHandoff $handoff, ?string $note = null): BrunoHandoff
    {
        $handoff->update([
            'status'          => BrunoHandoff::STATUS_RESOLVED,
            'resolved_at'     => now(),
            'resolution_note' => $note,
        ]);
        return $handoff->fresh();
    }
}
