<?php

namespace App\Services\Bruno;

use App\Jobs\SendMeetingEmailsJob;
use App\Models\MeetingBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ferramentas que o Bot Vendedor "Bruno" pode executar via function calling
 * do DeepSeek. Por enquanto: consulta de slots e criação de agendamento.
 *
 * Convenção: cada tool retorna array com 'success' bool + payload OU 'error' string.
 * O LLM recebe esse payload serializado e formula a próxima resposta.
 */
class BrunoTools
{
    /**
     * Definições no formato OpenAI / DeepSeek tools — vão direto no payload.
     */
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_slots',
                    'description' => 'Consulta os horários livres na agenda do Vivensi para uma data específica. Use quando o lead mencionar uma data que quer marcar reunião. Retorna lista de horários no formato HH:MM. Se a lista vier vazia, sugira outra data.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'string',
                                'description' => 'Data no formato YYYY-MM-DD (exemplo: 2026-07-03 para 03 de julho de 2026). Não pode ser data passada. Se o lead disser "amanhã", "quinta", "semana que vem" — converta voce mesmo pra YYYY-MM-DD considerando a data de hoje informada no system prompt.',
                            ],
                        ],
                        'required' => ['data'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'verificar_feature',
                    'description' => 'Consulta o catálogo de features do Vivensi para saber se determinada funcionalidade existe (e em qual painel/grupo). Use SEMPRE antes de afirmar "temos X" ou "não temos Y" — evita alucinação. Não use pra features óbvias já cobertas no system prompt (ex: "WhatsApp", "Bruce AI"). Retorna se a feature existe, o painel onde está e sugestões similares se não encontrar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nome' => [
                                'type'        => 'string',
                                'description' => 'Nome ou descrição curta da feature perguntada pelo lead. Ex: "assinatura digital em contratos", "emissão de NF-e", "kanban", "prospecção IA".',
                            ],
                            'painel' => [
                                'type'        => 'string',
                                'enum'        => ['terceiro_setor', 'mei', 'gestor'],
                                'description' => 'Opcional. Filtra a busca a um painel específico. Se o segmento do lead já foi identificado, passe o painel dele pra resposta ser mais precisa. Se omitido, busca em todos os painéis.',
                            ],
                        ],
                        'required' => ['nome'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'agendar_reuniao',
                    'description' => 'Confirma o agendamento de uma reunião de 20 minutos. SÓ chame depois que: (1) o lead escolheu data e hora dentre os slots disponíveis retornados por consultar_slots, e (2) você coletou nome completo, e-mail e telefone/WhatsApp do lead. Retorna token de cancelamento e link.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nome'        => ['type' => 'string', 'description' => 'Nome completo do lead'],
                            'email'       => ['type' => 'string', 'description' => 'E-mail válido do lead'],
                            'telefone'    => ['type' => 'string', 'description' => 'Telefone ou WhatsApp do lead (com DDD)'],
                            'data'        => ['type' => 'string', 'description' => 'Data no formato YYYY-MM-DD'],
                            'hora'        => ['type' => 'string', 'description' => 'Horário no formato HH:MM (24h, ex: 14:00)'],
                            'observacoes' => ['type' => 'string', 'description' => 'Opcional. Notas sobre o interesse do lead (vertical, dor, plano de interesse).'],
                        ],
                        'required' => ['nome', 'email', 'telefone', 'data', 'hora'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Executa uma tool por nome com argumentos do LLM.
     */
    public static function execute(string $name, array $args): array
    {
        Log::info('BrunoTools: execute', ['name' => $name, 'args' => $args]);

        return match ($name) {
            'consultar_slots'   => self::consultarSlots($args),
            'agendar_reuniao'   => self::agendarReuniao($args),
            'verificar_feature' => self::verificarFeature($args),
            default             => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    /**
     * Consulta a KB (config/bot-vendedor.php product.panels) por uma feature.
     * Retorna:
     *   - success + exists=true + matches [{painel, grupo, item, confidence}] se encontrou
     *   - success + exists=false + sugestoes [...] se não encontrou (top 5 mais próximos)
     *
     * Estratégia de match:
     *   1) Substring — se o termo aparece no item (ou vice-versa) após normalização
     *   2) Similaridade — similar_text >= 60% pra sugestões
     */
    private static function verificarFeature(array $args): array
    {
        $nome   = trim((string) ($args['nome'] ?? ''));
        $painel = $args['painel'] ?? null;

        if ($nome === '') {
            return ['error' => 'Campo nome é obrigatório.'];
        }

        $panels = config('bot-vendedor.product.panels', []);
        if (empty($panels)) {
            return ['error' => 'KB não carregada — config/bot-vendedor.php product.panels vazio.'];
        }

        if ($painel && !isset($panels[$painel])) {
            return ['error' => "Painel inválido: {$painel}. Use: terceiro_setor, mei ou gestor."];
        }

        $target  = self::normalizeFeatureName($nome);
        if ($target === '') {
            return ['error' => 'Nome muito curto após normalização.'];
        }
        $matches = [];
        $ranked  = []; // [{sim, painel, grupo, item}] pra sugestões

        $scope = $painel ? [$painel => $panels[$painel]] : $panels;

        foreach ($scope as $panelKey => $panelData) {
            $titulo = $panelData['titulo'] ?? $panelKey;
            foreach (($panelData['grupos'] ?? []) as $grupo => $itens) {
                foreach ($itens as $item) {
                    $normItem = self::normalizeFeatureName($item);
                    if ($normItem === '') continue;

                    $score = self::matchScore($target, $normItem);
                    if ($score['confianca'] === 'alta') {
                        $matches[] = [
                            'painel'         => $panelKey,
                            'painel_titulo'  => $titulo,
                            'grupo'          => $grupo,
                            'item'           => $item,
                            'confianca'      => 'alta',
                        ];
                    } elseif ($score['confianca'] === 'sugestao') {
                        $ranked[] = [
                            'sim'    => $score['score'],
                            'painel' => $panelKey,
                            'grupo'  => $grupo,
                            'item'   => $item,
                        ];
                    }
                }
            }
        }

        if (!empty($matches)) {
            return [
                'success'  => true,
                'exists'   => true,
                'consulta' => $nome,
                'escopo'   => $painel ?? 'todos os painéis',
                'total'    => count($matches),
                'matches'  => $matches,
                'instrucao_llm' => 'Confirme ao lead que a feature existe. Cite o grupo/painel se ajudar. NAO invente detalhes técnicos além do nome retornado.',
            ];
        }

        // Sem match forte — ordena sugestões e devolve top 5.
        usort($ranked, fn ($a, $b) => $b['sim'] <=> $a['sim']);
        $sugestoes = array_slice(array_map(fn ($r) => [
            'painel' => $r['painel'],
            'grupo'  => $r['grupo'],
            'item'   => $r['item'],
        ], $ranked), 0, 5);

        return [
            'success'  => true,
            'exists'   => false,
            'consulta' => $nome,
            'escopo'   => $painel ?? 'todos os painéis',
            'sugestoes' => $sugestoes,
            'instrucao_llm' => empty($sugestoes)
                ? 'Feature nao existe no catalogo. Seja honesto com o lead ("hoje nao temos"), pergunte pra que ele precisaria e ofereca escalar pra humano (Cristiane) se for critico.'
                : 'Feature exata NAO existe. Ofereca as sugestoes se forem relacionadas ("Temos X e Y, resolveria seu caso?"). Se nada bater, seja honesto: nao temos + escale pra humano.',
        ];
    }

    /**
     * Normaliza pra comparação. Preserva hifens INTERNOS ("e-mail", "nf-e") — só remove
     * como separador quando cercado de espaços. Cuidado: strip agressivo de "-" corta
     * "e-mail" em "e" e gera falso positivo em qualquer coisa com a letra "e".
     */
    private static function normalizeFeatureName(string $s): string
    {
        $s = mb_strtolower($s);
        $s = strtr($s, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
        $s = preg_replace('/\s*\(.*?\)\s*/u', ' ', $s);           // "(...)"
        $s = preg_replace('/\s+[—–\-]\s+.*$/u', ' ', $s);         // sufixo " — ..." (dash cercado de espaço)
        $s = preg_replace('/\s*&\s*/u', ' e ', $s);
        $s = preg_replace('/[^a-z0-9\- ]/u', ' ', $s);            // preserva hifens internos
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /**
     * Devolve ['confianca' => 'alta'|'sugestao'|null, 'score' => 0..100].
     * Regras:
     *   - Substring bidirecional só se ambos os lados têm >= 4 chars (evita falso positivo)
     *   - Word-match: quantas palavras >=3 chars do target aparecem no item
     *   - Fallback: similar_text
     */
    private static function matchScore(string $target, string $item): array
    {
        if (strlen($target) >= 4 && strlen($item) >= 4) {
            if (str_contains($item, $target) || str_contains($target, $item)) {
                return ['confianca' => 'alta', 'score' => 100];
            }
        }

        $targetWords = array_values(array_filter(
            explode(' ', $target),
            fn ($w) => strlen($w) >= 3 && !in_array($w, ['com', 'para', 'por', 'dos', 'das', 'que', 'uma', 'seu', 'sua'], true)
        ));
        if (empty($targetWords)) {
            similar_text($target, $item, $sim);
            return ['confianca' => $sim >= 70 ? 'sugestao' : null, 'score' => $sim];
        }

        $itemWords = explode(' ', $item);
        $hits = 0;
        foreach ($targetWords as $tw) {
            foreach ($itemWords as $iw) {
                if ($tw === $iw) { $hits++; break; }
                if (strlen($tw) >= 4 && strlen($iw) >= 4 && (str_contains($iw, $tw) || str_contains($tw, $iw))) {
                    $hits++; break;
                }
            }
        }
        $wordScore = ($hits / count($targetWords)) * 100;

        if ($wordScore >= 66) return ['confianca' => 'alta',     'score' => $wordScore];
        if ($wordScore >= 40) return ['confianca' => 'sugestao', 'score' => $wordScore];

        similar_text($target, $item, $sim);
        return ['confianca' => $sim >= 65 ? 'sugestao' : null, 'score' => $sim];
    }

    private static function consultarSlots(array $args): array
    {
        $data = $args['data'] ?? null;
        if (!$data) {
            return ['error' => 'Campo data é obrigatório (formato YYYY-MM-DD)'];
        }

        try {
            $dia = Carbon::parse($data);
        } catch (\Throwable $e) {
            return ['error' => 'Data inválida. Use formato YYYY-MM-DD.'];
        }

        if ($dia->isPast() && !$dia->isToday()) {
            return ['error' => 'Não é possível consultar data no passado. Escolha outra data.'];
        }

        $slots = MeetingBooking::availableSlotsFor($data);

        return [
            'success'        => true,
            'data'           => $data,
            'data_formatada' => $dia->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'slots'          => $slots,
            'total'          => count($slots),
        ];
    }

    private static function agendarReuniao(array $args): array
    {
        foreach (['nome', 'email', 'telefone', 'data', 'hora'] as $f) {
            if (empty($args[$f])) {
                return ['error' => "Campo obrigatório ausente: {$f}"];
            }
        }

        if (!filter_var($args['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'E-mail inválido. Peça novamente ao lead.'];
        }

        $available = MeetingBooking::availableSlotsFor($args['data']);
        if (!in_array($args['hora'], $available, true)) {
            return [
                'error'    => 'Horário não está mais disponível',
                'available' => $available,
            ];
        }

        // Prefixa notes com marca de origem pra dashboard de metricas saber
        // distinguir agendamentos via Bruno dos via pagina publica /agendar.
        $obs = trim((string) ($args['observacoes'] ?? ''));
        $notes = '[via Bruno]' . ($obs !== '' ? ' ' . $obs : '');

        try {
            $booking = MeetingBooking::create([
                'name'         => $args['nome'],
                'email'        => $args['email'],
                'phone'        => $args['telefone'],
                'notes'        => $notes,
                'meeting_date' => $args['data'],
                'meeting_time' => $args['hora'],
                'status'       => 'confirmed',
            ]);
        } catch (\Throwable $e) {
            Log::error('BrunoTools: agendar_reuniao falhou', ['err' => $e->getMessage()]);
            return ['error' => 'Não foi possível salvar o agendamento. Tente novamente.'];
        }

        SendMeetingEmailsJob::dispatch($booking->id);
        MeetingBooking::notifyAdmins($booking, 'via Bruno');

        return [
            'success'        => true,
            'token'          => $booking->confirmation_token,
            'data_formatada' => Carbon::parse($booking->meeting_date)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
            'hora'           => $args['hora'],
            'link_cancelar'  => url('/agendar/cancelar/' . $booking->confirmation_token),
        ];
    }
}
