<?php

namespace App\Services\Messaging;

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * AntiBanManager — Camada de proteção anti-ban para Evolution API (Baileys).
 *
 * Estratégias:
 *  - Janela de horário seguro por instância
 *  - Limite diário real com reset automático
 *  - Limite horário via RateLimiter (MAX_PER_HOUR)
 *  - Warming progressivo 14 dias com perfil de escalada gradual
 *  - Detecção de sinal de ban na resposta da API
 *  - Marcação de instância restrita no campo settings JSON
 *  - Simulação de digitação humana: "composing" + "paused"
 *  - Bloqueio de URLs encurtadas (gatilho de spam da Meta)
 *  - Detecção de mensagens de opt-out em PT-BR e EN
 */
class AntiBanManager
{
    protected EvolutionApiService $api;

    // URLs encurtadas sinalizadas como spam pela Meta
    public const BLOCKED_SHORTENERS = [
        'bit.ly', 'cutt.ly', 't.ly', 'tinyurl.com', 'is.gd', 'rebrand.ly',
        'ow.ly', 'buff.ly', 'dlvr.it', 'soo.gd', 'clk.im', 'shorte.st',
        'adf.ly', 'bc.vc', 'tiny.cc', 'mcaf.ee',
    ];

    // Palavras-chave de opt-out em PT-BR e EN
    /**
     * Palavras de opt-out que devem corresponder EXATAMENTE à mensagem normalizada.
     * São termos curtos que aparecem dentro de palavras comuns do PT-BR — usar
     * str_contains com elas causaria falso-positivo:
     *   - 'para' dentro de "parabéns"
     *   - 'pare' dentro de "parece"
     *   - 'sai'  dentro de "saia", "assai"
     *   - 'sair' dentro de "saíram"
     */
    public const OPTOUT_EXACT = [
        'para', 'pare', 'sai', 'sair', 'stop',
    ];

    /**
     * Termos inequívocos verificados com word-boundary (\b) — não com str_contains.
     * O \b impede que a palavra case dentro de outra (ex: 'parar' não casa em
     * "comparar", 'cancelar' não casa em "cancelaram", 'chega' não casa em
     * "chegamos"). Todas as entradas estão SEM acento porque a normalização
     * remove diacríticos antes da comparação.
     */
    public const OPTOUT_CONTAINS = [
        'parar', 'chega',
        'cancelar', 'remover', 'remove',
        'descadastrar', 'descadastro', 'desinscrever',
        'bloquear', 'unsubscribe',
        'nao quero', 'nao me mande',
    ];

    // Perfil de warming: dia => limite diário máximo (~30% de crescimento/dia)
    public const WARMING_PROFILE = [
        1  => 20,
        2  => 30,
        3  => 40,
        4  => 55,
        5  => 70,
        6  => 90,
        7  => 115,
        8  => 140,
        9  => 170,
        10 => 205,
        11 => 245,
        12 => 290,
        13 => 340,
        14 => 370,
    ];

    // Limite máximo por hora — acima disso o WhatsApp sinaliza como bot
    public const MAX_PER_HOUR = 55;

    // Horas de restrição padrão ao detectar sinal de ban
    public const DEFAULT_BAN_RESTRICTION_HOURS = 24;

    public function __construct(EvolutionApiService $api)
    {
        $this->api = $api;
    }

    // ── Verificação de permissão de envio ────────────────────────────────────

    /**
     * Verifica se a instância pode enviar uma mensagem agora.
     * Ordem: janela horária → restrição ban → limite diário → warming → limite horário.
     */
    public function canSendMessage(WhatsappInstance $instance): bool
    {
        if (!$instance->isWithinSafeWindow()) {
            Log::info("AntiBan: [{$instance->instance_name}] Fora da janela horária ({$instance->safe_window_start}–{$instance->safe_window_end}).");
            return false;
        }

        if ($this->isInstanceRestricted($instance)) {
            $until = $instance->settings['restricted_until'] ?? 'desconhecido';
            Log::warning("AntiBan: [{$instance->instance_name}] Instância RESTRITA até {$until}.");
            return false;
        }

        if ($instance->hasReachedDailyLimit()) {
            Log::info("AntiBan: [{$instance->instance_name}] Limite diário atingido ({$instance->messages_sent_today}/{$instance->daily_limit}).");
            return false;
        }

        if ($this->isWarming($instance)) {
            $warmingLimit = $this->getWarmingDailyLimit($instance);
            if ($instance->messages_sent_today >= $warmingLimit) {
                Log::info("AntiBan: [{$instance->instance_name}] Limite de warming atingido ({$instance->messages_sent_today}/{$warmingLimit}) — dia {$this->getWarmingDay($instance)}.");
                return false;
            }
        }

        if ($this->hasReachedHourlyLimit($instance)) {
            Log::info("AntiBan: [{$instance->instance_name}] Limite horário atingido (máx " . self::MAX_PER_HOUR . "/hora).");
            return false;
        }

        return true;
    }

    // ── Simulação de comportamento humano ─────────────────────────────────────

    /**
     * Simula digitação humana:
     * 1. Envia "composing" (digitando)
     * 2. Aguarda delay proporcional ao comprimento da mensagem (~12 chars/seg)
     * 3. Envia "paused" (pausou — mais orgânico que parar direto)
     * 4. Aguarda 1-2s antes do envio real
     *
     * Chamar ANTES de enviar a mensagem.
     */
    public function simulateHumanTyping(WhatsappInstance $instance, string $remoteJid, string $message = ''): void
    {
        try {
            $this->api->sendPresence($instance->instance_name, $remoteJid, 'composing');

            $chars = max(20, mb_strlen($message));
            $base  = (int) ($chars / 12); // ~12 chars/seg = velocidade média de digitação
            $delay = rand(max(3, $base), max(7, $base + 4));
            sleep($delay);

            $this->api->sendPresence($instance->instance_name, $remoteJid, 'paused');
            sleep(rand(1, 3));

        } catch (\Throwable $e) {
            Log::warning("AntiBan: Falha ao simular digitação para {$remoteJid}: " . $e->getMessage());
        }
    }

    /**
     * Delay randômico em ms para o payload da Evolution API.
     */
    public function getRandomDelayMs(): int
    {
        return rand(2500, 6000);
    }

    // ── Contabilização ────────────────────────────────────────────────────────

    /**
     * Registra envio: incrementa contador diário e hit no rate limiter horário.
     * Chamar APÓS envio confirmado.
     */
    public function recordSent(WhatsappInstance $instance): void
    {
        $instance->incrementDailyCount();
        RateLimiter::hit('wa:hourly:' . $instance->id, 3600);
    }

    /**
     * Verifica limite horário via Laravel RateLimiter.
     */
    public function hasReachedHourlyLimit(WhatsappInstance $instance): bool
    {
        return RateLimiter::tooManyAttempts('wa:hourly:' . $instance->id, self::MAX_PER_HOUR);
    }

    // ── Detecção de riscos ────────────────────────────────────────────────────

    /**
     * Verifica se a mensagem contém URL encurtada (gatilho de spam da Meta).
     */
    public function containsBlockedShortener(string $message): bool
    {
        foreach (self::BLOCKED_SHORTENERS as $shortener) {
            if (stripos($message, $shortener) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se a mensagem recebida é um pedido de opt-out.
     *
     * Estática porque é função pura — não depende do estado do AntiBanManager.
     * Pode ser chamada como `AntiBanManager::isOptOutMessage($text)` em
     * webhooks/handlers sem instanciar o serviço.
     *
     * Regra de match:
     *  1) OPTOUT_EXACT  — mensagem normalizada deve ser idêntica à keyword.
     *  2) OPTOUT_CONTAINS — keyword com word-boundary (\b), não substring.
     */
    public static function isOptOutMessage(string $text): bool
    {
        $normalized = self::normalizeForOptOut($text);

        if ($normalized === '') {
            return false;
        }

        foreach (self::OPTOUT_EXACT as $keyword) {
            if ($normalized === $keyword) {
                return true;
            }
        }

        foreach (self::OPTOUT_CONTAINS as $keyword) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/';
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza a mensagem para comparação: minúscula, sem tags, sem espaços
     * de borda, com diacríticos PT-BR convertidos para ASCII.
     *
     * Sem isso, "Não quero" não casaria 'nao quero' e teríamos que duplicar
     * cada keyword (com e sem acento).
     */
    private static function normalizeForOptOut(string $text): string
    {
        $clean = mb_strtolower(trim(strip_tags($text)));

        return strtr($clean, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
    }

    /**
     * Detecta sinal de ban, rate-limit ou suspensão na resposta da API.
     */
    public function isBanSignal(mixed $response): bool
    {
        $details = strtolower(is_string($response) ? $response : (json_encode($response) ?: ''));
        return str_contains($details, '429')
            || str_contains($details, 'rate limit')
            || str_contains($details, 'rate_limit')
            || str_contains($details, 'banned')
            || str_contains($details, 'suspended')
            || str_contains($details, 'blocked')
            || str_contains($details, 'spam')
            || str_contains($details, 'unauthorized');
    }

    // ── Gerenciamento de restrição ────────────────────────────────────────────

    /**
     * Marca a instância como RESTRITA por N horas.
     * Salva no campo settings JSON — sem migration necessária.
     */
    public function markAsRestricted(WhatsappInstance $instance, int $hours = self::DEFAULT_BAN_RESTRICTION_HOURS): void
    {
        $until    = now()->addHours($hours)->toIso8601String();
        $settings = $instance->settings ?? [];
        $settings['restricted_until']  = $until;
        $settings['restricted_reason'] = 'ban_signal_detected';
        $settings['restricted_at']     = now()->toIso8601String();
        $instance->update(['settings' => $settings]);

        Log::critical("AntiBan: [{$instance->instance_name}] RESTRITA por {$hours}h — até {$until}.");
    }

    /**
     * Verifica se a instância está em período de restrição.
     * Remove o flag automaticamente quando expirar.
     */
    public function isInstanceRestricted(WhatsappInstance $instance): bool
    {
        $restrictedUntil = $instance->settings['restricted_until'] ?? null;
        if (!$restrictedUntil) return false;

        if (now()->greaterThan(\Carbon\Carbon::parse($restrictedUntil))) {
            $settings = $instance->settings ?? [];
            unset($settings['restricted_until'], $settings['restricted_reason'], $settings['restricted_at']);
            $instance->update(['settings' => $settings]);
            Log::info("AntiBan: [{$instance->instance_name}] restrição expirada — liberada.");
            return false;
        }

        return true;
    }

    // ── Warming progressivo ───────────────────────────────────────────────────

    /**
     * Ativa o modo warming na instância (chamar ao criar instância nova).
     */
    public function startWarming(WhatsappInstance $instance): void
    {
        $settings = $instance->settings ?? [];
        $settings['warming_mode']       = true;
        $settings['warming_started_at'] = now()->toDateString();
        $instance->update(['settings' => $settings]);

        Log::info("AntiBan: warming iniciado para [{$instance->instance_name}] — perfil de 14 dias.");
    }

    /**
     * Verifica se a instância está em modo de warming.
     */
    public function isWarming(WhatsappInstance $instance): bool
    {
        return (bool) ($instance->settings['warming_mode'] ?? false);
    }

    /**
     * Retorna o dia atual do warming (1-based).
     */
    public function getWarmingDay(WhatsappInstance $instance): int
    {
        $started = $instance->settings['warming_started_at'] ?? null;
        if (!$started) return 1;

        return max(1, (int) now()->diffInDays(\Carbon\Carbon::parse($started)) + 1);
    }

    /**
     * Retorna o limite diário ajustado ao perfil de warming.
     * Após dia 14, desativa warming e usa daily_limit normal.
     */
    public function getWarmingDailyLimit(WhatsappInstance $instance): int
    {
        $day = $this->getWarmingDay($instance);

        if ($day > 14) {
            $settings = $instance->settings ?? [];
            $settings['warming_mode'] = false;
            $instance->update(['settings' => $settings]);
            Log::info("AntiBan: warming concluído para [{$instance->instance_name}] após {$day} dias.");
            return $instance->daily_limit;
        }

        return self::WARMING_PROFILE[$day] ?? 370;
    }

    /**
     * Retorna status resumido da instância para exibição no painel.
     */
    public function getInstanceStatus(WhatsappInstance $instance): array
    {
        $isWarming = $this->isWarming($instance);

        return [
            'can_send'         => $this->canSendMessage($instance),
            'is_restricted'    => $this->isInstanceRestricted($instance),
            'restricted_until' => $instance->settings['restricted_until'] ?? null,
            'is_warming'       => $isWarming,
            'warming_day'      => $isWarming ? $this->getWarmingDay($instance) : null,
            'warming_limit'    => $isWarming ? $this->getWarmingDailyLimit($instance) : null,
            'sent_today'       => $instance->messages_sent_today,
            'daily_limit'      => $instance->daily_limit,
            'within_window'    => $instance->isWithinSafeWindow(),
        ];
    }
}
