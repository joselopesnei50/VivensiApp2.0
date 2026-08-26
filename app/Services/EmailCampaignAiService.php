<?php

namespace App\Services;

use App\Models\EmailCampaign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Bruce IA aplicada a campanhas de email.
 *
 * Dois metodos publicos:
 *  - analyzeBeforeSend(): guard-rail pre-envio (score + riscos + sugestoes)
 *  - analyzePerformance(): insight pos-envio (interpretacao + proxima acao)
 *
 * Ambos combinam heuristicas deterministicas (regex + regras) com chamada
 * DeepSeek pra contextualizar. Cache defensivo pra nao gerar toda vez.
 */
class EmailCampaignAiService
{
    /** Palavras-gatilho de spam em portugues/ingles — evita queimar reputacao. */
    private const SPAM_TRIGGERS = [
        'gratis', 'grátis', 'gratuito', 'free', '100% gratis',
        'urgente', 'urgent', 'aja agora', 'act now', 'ultima chance',
        'garantido', '100% garantido', 'sem risco', 'risk free',
        'ganhe dinheiro', 'renda extra', 'faca dinheiro', 'lucro certo',
        'clique aqui', 'click here',
        'oferta imperdivel', 'oportunidade unica', 'so hoje',
        'compre agora', 'buy now',
    ];

    public function __construct(
        private DeepSeekService $deepSeek,
    ) {}

    // ── F1: Análise pré-envio ─────────────────────────────────────────────────

    /**
     * Analisa um rascunho de campanha e devolve score, riscos e sugestoes.
     *
     * @param  array{subject:string, html_content:string, preheader?:string} $draft
     * @return array{score:int, level:string, risks:array, suggestions:array, ai_notes:?string}
     */
    public function analyzeBeforeSend(array $draft, int $tenantId): array
    {
        $subject = (string) ($draft['subject'] ?? '');
        $html    = (string) ($draft['html_content'] ?? '');

        $risks       = [];
        $penaltySum  = 0;

        // ── Regras deterministicas (rapidas, sem custo AI) ───────────────
        // 1. Assunto
        $subjLen = mb_strlen(trim($subject));
        if ($subjLen === 0) {
            $risks[] = ['severity' => 'high', 'msg' => 'Assunto vazio.'];
            $penaltySum += 40;
        } elseif ($subjLen > 60) {
            $risks[] = ['severity' => 'medium', 'msg' => "Assunto muito longo ({$subjLen} chars). Ideal ate 50 pra nao cortar no mobile."];
            $penaltySum += 10;
        }
        if ($subject !== '' && mb_strtoupper($subject) === $subject && $subjLen > 5) {
            $risks[] = ['severity' => 'high', 'msg' => 'Assunto TODO EM MAIUSCULAS — Gmail marca como spam.'];
            $penaltySum += 15;
        }
        $emojiCount = preg_match_all('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $subject);
        if ($emojiCount > 2) {
            $risks[] = ['severity' => 'medium', 'msg' => "Assunto tem {$emojiCount} emojis — reduza pra 1 no maximo."];
            $penaltySum += 8;
        }

        // 2. Palavras-gatilho de spam (assunto + corpo)
        $textOnly = mb_strtolower($subject . ' ' . strip_tags($html));
        $foundTriggers = [];
        foreach (self::SPAM_TRIGGERS as $trigger) {
            if (str_contains($textOnly, $trigger)) {
                $foundTriggers[] = $trigger;
            }
        }
        if (!empty($foundTriggers)) {
            $risks[] = ['severity' => 'high', 'msg' => 'Palavras-gatilho de spam encontradas: "' . implode('", "', array_slice($foundTriggers, 0, 5)) . '".'];
            $penaltySum += min(count($foundTriggers) * 5, 25);
        }

        // 3. HTML
        if ($html === '') {
            $risks[] = ['severity' => 'high', 'msg' => 'Conteudo HTML vazio.'];
            $penaltySum += 50;
        } else {
            // Unsubscribe link
            if (!preg_match('/\{\{\s*unsubscribe\s*\}\}|href=["\']?\{\{\s*unsubscribe/i', $html)
                && !str_contains(mb_strtolower(strip_tags($html)), 'descadastr')
                && !str_contains(mb_strtolower(strip_tags($html)), 'unsubscribe')) {
                $risks[] = ['severity' => 'high', 'msg' => 'Nao encontrei link de descadastro no conteudo. Brevo adiciona automatico mas confirme com {{unsubscribe}} no template.'];
                $penaltySum += 15;
            }

            // Imagens sem alt
            $totalImgs = preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
            $imgsSemAlt = 0;
            foreach ($imgs[0] ?? [] as $img) {
                if (!preg_match('/\balt\s*=\s*["\'][^"\']+["\']/i', $img)) {
                    $imgsSemAlt++;
                }
            }
            if ($imgsSemAlt > 0) {
                $risks[] = ['severity' => 'low', 'msg' => "{$imgsSemAlt} imagem(ns) sem atributo alt — leitores acessiveis nao entendem, e Gmail penaliza."];
                $penaltySum += $imgsSemAlt * 2;
            }

            // Ratio imagem/texto (muita imagem, pouco texto = spam classico)
            $textLen  = mb_strlen(trim(strip_tags($html)));
            $imgCount = $totalImgs;
            if ($textLen < 200 && $imgCount > 3) {
                $risks[] = ['severity' => 'medium', 'msg' => "Muita imagem ({$imgCount}) e pouco texto ({$textLen} chars). Provedores marcam como spam."];
                $penaltySum += 10;
            }
        }

        // ── Score final (100 - penalidade, min 0) ────────────────────────
        $score = max(0, 100 - $penaltySum);
        $level = match (true) {
            $score >= 85 => 'excelente',
            $score >= 70 => 'bom',
            $score >= 50 => 'atencao',
            default      => 'ruim',
        };

        // ── DeepSeek pra sugestoes contextualizadas ──────────────────────
        // So chama IA se ha risco relevante — economiza cota.
        $aiNotes    = null;
        $suggestions = $this->baseSuggestions($risks);

        if (!empty($risks) && $penaltySum >= 10) {
            $aiResult = $this->askAiForImprovements($subject, $html, $risks, $tenantId);
            if (!isset($aiResult['error'])) {
                $aiNotes = $aiResult['notes'] ?? null;
                if (!empty($aiResult['suggestions']) && is_array($aiResult['suggestions'])) {
                    $suggestions = array_slice(
                        array_merge($suggestions, $aiResult['suggestions']),
                        0, 6
                    );
                }
            }
        }

        return [
            'score'       => $score,
            'level'       => $level,
            'risks'       => $risks,
            'suggestions' => array_values(array_unique($suggestions)),
            'ai_notes'    => $aiNotes,
        ];
    }

    /**
     * Sugestoes heuristicas base — sempre disponiveis mesmo se AI falhar.
     * @param  array<int,array{severity:string,msg:string}>  $risks
     * @return array<int,string>
     */
    private function baseSuggestions(array $risks): array
    {
        $out = [];
        foreach ($risks as $r) {
            $m = mb_strtolower($r['msg']);
            if (str_contains($m, 'assunto muito longo')) $out[] = 'Reescreva o assunto em ate 50 chars.';
            if (str_contains($m, 'maiusculas'))          $out[] = 'Use case normal no assunto (nao tudo em CAPS).';
            if (str_contains($m, 'palavras-gatilho'))    $out[] = 'Substitua gatilhos de spam por sinonimos neutros ("gratis" -> "sem custo", "urgente" -> "importante").';
            if (str_contains($m, 'descadastro'))         $out[] = 'Adicione um link de descadastro visivel no rodape do email.';
            if (str_contains($m, 'sem atributo alt'))    $out[] = 'Adicione alt="descricao" nas imagens.';
            if (str_contains($m, 'muita imagem'))        $out[] = 'Balanceie: pelo menos 200 chars de texto pra cada 2-3 imagens.';
        }
        return $out;
    }

    /**
     * Chama DeepSeek pra dar sugestoes contextualizadas ao rascunho especifico.
     * Retorna ['notes' => string, 'suggestions' => string[]] ou ['error' => ...].
     */
    private function askAiForImprovements(string $subject, string $html, array $risks, int $tenantId): array
    {
        $textPreview = mb_substr(strip_tags($html), 0, 800);
        $risksStr    = collect($risks)->pluck('msg')->implode('; ');

        $prompt = "Você é um consultor de email marketing. Analise este rascunho de campanha e devolva JSON com 'notes' (2-3 frases interpretando os riscos) e 'suggestions' (array de 2-3 sugestoes praticas, cada uma frase curta em portugues).\n\n"
                . "Assunto: {$subject}\n\n"
                . "Corpo (preview): {$textPreview}\n\n"
                . "Riscos ja detectados: {$risksStr}\n\n"
                . "Responda APENAS JSON valido: {\"notes\": \"...\", \"suggestions\": [\"...\", \"...\"]}";

        $response = $this->deepSeek->chat(
            [['role' => 'user', 'content' => $prompt]],
            null,
            null,
            $tenantId
        );

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        $content = data_get($response, 'choices.0.message.content', '');
        $content = trim($content);
        // Remove wrapper ```json se veio
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content);

        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            Log::warning('EmailCampaignAiService: DeepSeek JSON invalido', [
                'raw' => mb_substr($content, 0, 300),
            ]);
            return ['error' => 'JSON invalido'];
        }

        return [
            'notes'       => (string) ($parsed['notes'] ?? ''),
            'suggestions' => array_map('strval', (array) ($parsed['suggestions'] ?? [])),
        ];
    }

    // ── F2: Análise pós-envio ─────────────────────────────────────────────────

    /**
     * Insight sobre uma campanha ja disparada — interpretacao das metricas +
     * proxima acao recomendada. Cache 1h por campanha pra nao gerar toda vez.
     *
     * @return array{insight:string, next_action:string, benchmark:array}
     */
    public function analyzePerformance(EmailCampaign $campaign): array
    {
        $cacheKey = "bruce.email_insight.{$campaign->id}";
        $cached   = Cache::get($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        // Metricas da campanha
        $sent      = max(1, (int) ($campaign->recipient_count ?? 0));
        $delivered = (int) ($campaign->stat_delivered ?? 0);
        $opens     = (int) ($campaign->stat_opens ?? 0);
        $clicks    = (int) ($campaign->stat_clicks ?? 0);
        $bounces   = (int) ($campaign->stat_bounces ?? 0);

        $openRate   = $delivered > 0 ? round(($opens   / $delivered) * 100, 1) : 0.0;
        $clickRate  = $delivered > 0 ? round(($clicks  / $delivered) * 100, 1) : 0.0;
        $bounceRate = $sent      > 0 ? round(($bounces / $sent)      * 100, 1) : 0.0;

        // Benchmark historico do tenant (media das ultimas 5 campanhas fora essa)
        $historico = EmailCampaign::where('tenant_id', $campaign->tenant_id)
            ->where('id', '!=', $campaign->id)
            ->where('status', 'sent')
            ->whereNotNull('stat_opens')
            ->orderByDesc('sent_at')
            ->limit(5)
            ->get(['stat_delivered', 'stat_opens', 'stat_clicks', 'stat_bounces', 'recipient_count']);

        $avgOpen = 0.0; $avgClick = 0.0; $avgBounce = 0.0;
        if ($historico->isNotEmpty()) {
            $sums = ['open' => 0, 'click' => 0, 'bounce' => 0, 'n' => 0];
            foreach ($historico as $c) {
                $del = max(1, (int) $c->stat_delivered);
                $snt = max(1, (int) ($c->recipient_count ?? $del));
                $sums['open']   += ($c->stat_opens   / $del) * 100;
                $sums['click']  += ($c->stat_clicks  / $del) * 100;
                $sums['bounce'] += ($c->stat_bounces / $snt) * 100;
                $sums['n']++;
            }
            $avgOpen   = round($sums['open']   / $sums['n'], 1);
            $avgClick  = round($sums['click']  / $sums['n'], 1);
            $avgBounce = round($sums['bounce'] / $sums['n'], 1);
        }

        $benchmark = [
            'this_campaign' => compact('openRate', 'clickRate', 'bounceRate'),
            'tenant_avg'    => ['open' => $avgOpen, 'click' => $avgClick, 'bounce' => $avgBounce],
            'sample_size'   => $historico->count(),
        ];

        // Insight via IA
        $result = $this->askAiForInsight($campaign, $benchmark);

        if (isset($result['error'])) {
            return [
                'insight'     => 'Não foi possível gerar interpretação agora. Métricas: ' . "abertura {$openRate}%, clique {$clickRate}%, bounce {$bounceRate}%.",
                'next_action' => $bounceRate > 5
                    ? 'Bounce alto (>5%) — limpe a lista removendo os emails que rejeitaram antes da proxima campanha.'
                    : 'Repita o padrao do que funcionou aqui na proxima campanha.',
                'benchmark'   => $benchmark,
            ];
        }

        $out = [
            'insight'     => $result['insight']     ?? '',
            'next_action' => $result['next_action'] ?? '',
            'benchmark'   => $benchmark,
        ];

        Cache::put($cacheKey, $out, 3600); // 1h
        return $out;
    }

    private function askAiForInsight(EmailCampaign $c, array $benchmark): array
    {
        $me = $benchmark['this_campaign'];
        $avg = $benchmark['tenant_avg'];
        $sample = $benchmark['sample_size'];

        $ctx = "Campanha atual: assunto '{$c->subject}', {$c->recipient_count} enviados.\n"
             . "Taxas ATUAL: abertura {$me['openRate']}%, clique {$me['clickRate']}%, bounce {$me['bounceRate']}%.\n"
             . ($sample > 0
                ? "Media das ultimas {$sample} campanhas do mesmo tenant: abertura {$avg['open']}%, clique {$avg['click']}%, bounce {$avg['bounce']}%.\n"
                : "Sem historico anterior desse tenant pra comparar.\n");

        $prompt = "Você é um consultor de email marketing. Interprete as metricas dessa campanha e devolva JSON com 'insight' (2-3 frases interpretando resultado, comparando com historico) e 'next_action' (1-2 frases com recomendacao pratica pra proxima campanha).\n\n"
                . $ctx
                . "\nBenchmarks de mercado: email marketing brasileiro tipicamente tem abertura 20-25%, clique 2-4%, bounce <3%.\n\n"
                . "Responda APENAS JSON valido: {\"insight\": \"...\", \"next_action\": \"...\"}";

        $response = $this->deepSeek->chat(
            [['role' => 'user', 'content' => $prompt]],
            null,
            null,
            $c->tenant_id
        );

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        $content = trim(data_get($response, 'choices.0.message.content', ''));
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content);
        $parsed  = json_decode($content, true);

        if (!is_array($parsed)) {
            Log::warning('EmailCampaignAiService: insight JSON invalido', ['raw' => mb_substr($content, 0, 300)]);
            return ['error' => 'JSON invalido'];
        }

        return $parsed;
    }
}
