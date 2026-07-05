<?php

namespace App\Services\Messaging;

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Cache;
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

    /**
     * @deprecated Tarefa 3.2 — migrado para config('whatsapp.antiban.warming_profiles.default').
     * Mantido aqui apenas como fallback de último recurso (defesa contra config corrompido).
     * Use getWarmingProfile(\$instance) para leitura runtime.
     */
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

    /**
     * @deprecated Tarefa 3.2 — migrado para config('whatsapp.antiban.max_per_hour').
     * Use getMaxPerHour(\$instance) para leitura runtime (suporta override por instância).
     */
    public const MAX_PER_HOUR = 55;

    /**
     * @deprecated Tarefa 3.2 — migrado para config('whatsapp.antiban.default_ban_restriction_hours').
     * Use getBanRestrictionHours() para leitura runtime.
     */
    public const DEFAULT_BAN_RESTRICTION_HOURS = 24;

    // Fase 1 (Anti-Ban 2026): limite de envios do mesmo fingerprint de conteúdo
    // por instância por dia. Acima disso, o ML da Meta trata como spam mesmo
    // dentro dos limites de warming e horário — é o gatilho de detecção mais
    // sensível de 2026.
    public const MAX_SAME_CONTENT_PER_DAY = 40;

    // Fase 2 (Anti-Ban 2026): limiar de proporção de destinatários "novos" na
    // audiência. Acima disso o job entra em modo conservador (delay dobrado).
    // "Novo" = contato sem last_inbound_at, i.e. nunca respondeu à marca.
    public const NEW_RECIPIENT_RISK_THRESHOLD = 0.70;

    public function __construct(EvolutionApiService $api)
    {
        $this->api = $api;
    }

    // ── Resolução dinâmica de tuning (Tarefa 3.2) ──────────────────────────
    // Cada getter prefere override por instância (settings JSON), cai para
    // config global, e por último para o constant deprecated. Permite ajuste
    // por tenant sem migration.

    /**
     * Limite horário máximo de mensagens para uma instância.
     * Override por instância: $instance->settings['max_per_hour'] (int > 0).
     */
    public function getMaxPerHour(WhatsappInstance $instance): int
    {
        $override = $instance->settings['max_per_hour'] ?? null;
        if (is_int($override) && $override > 0) {
            return $override;
        }
        return (int) config('whatsapp.antiban.max_per_hour', self::MAX_PER_HOUR);
    }

    /**
     * Horas de restrição ao detectar sinal de ban (markAsRestricted default).
     * Sem override por instância — decisão de plataforma, não de produto.
     */
    public function getBanRestrictionHours(): int
    {
        return (int) config('whatsapp.antiban.default_ban_restriction_hours', self::DEFAULT_BAN_RESTRICTION_HOURS);
    }

    /**
     * Perfil de warming aplicável (mapa dia => limite diário).
     * Tenant escolhe via $instance->settings['warming_profile'] = 'default'|'conservative'.
     * Se o perfil indicado não existir, cai para 'default'. Se config corrompido,
     * cai para o constant deprecated WARMING_PROFILE.
     */
    public function getWarmingProfile(WhatsappInstance $instance): array
    {
        $profileName = $instance->settings['warming_profile'] ?? 'default';
        $profiles    = config('whatsapp.antiban.warming_profiles', []);

        if (isset($profiles[$profileName]) && is_array($profiles[$profileName])) {
            return $profiles[$profileName];
        }
        if (isset($profiles['default']) && is_array($profiles['default'])) {
            return $profiles['default'];
        }
        return self::WARMING_PROFILE;
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
            Log::info("AntiBan: [{$instance->instance_name}] Limite horário atingido (máx " . $this->getMaxPerHour($instance) . "/hora).");
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
     * Respeita override por instância (settings.max_per_hour) e config global.
     */
    public function hasReachedHourlyLimit(WhatsappInstance $instance): bool
    {
        return RateLimiter::tooManyAttempts('wa:hourly:' . $instance->id, $this->getMaxPerHour($instance));
    }

    // ── Detecção de riscos ────────────────────────────────────────────────────

    // ── Fingerprint de conteúdo (Fase 1 Anti-Ban 2026) ───────────────────────
    // Enviar a mesma mensagem repetida em massa dispara o ML da Meta mesmo
    // dentro dos limites de warming/horário. Contamos envios por hash SHA-256
    // do conteúdo normalizado, por instância, resetando à meia-noite.

    /**
     * Hash determinístico do conteúdo, normalizado (lowercase, whitespace
     * colapsado). Duas variações do mesmo texto — só espaços/quebras
     * diferentes — colidem no mesmo hash de propósito: o objetivo é
     * detectar "mesmo conteúdo", não "mesmos bytes".
     */
    private function fingerprintHash(string $content): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $content) ?? ''));
        return hash('sha256', $normalized);
    }

    private function fingerprintKey(WhatsappInstance $instance, string $content): string
    {
        return "wa:fingerprint:{$instance->id}:" . $this->fingerprintHash($content);
    }

    /**
     * Verifica se este fingerprint de conteúdo ainda pode ser enviado hoje
     * pela instância. Limite: MAX_SAME_CONTENT_PER_DAY. TTL da chave é até
     * o fim do dia — reset automático à meia-noite. Em prod usa Redis (via
     * Cache facade); em testes o driver array cobre sem infra externa.
     */
    public function contentFingerprintAllowed(WhatsappInstance $instance, string $content): bool
    {
        $count = (int) Cache::get($this->fingerprintKey($instance, $content), 0);
        return $count < self::MAX_SAME_CONTENT_PER_DAY;
    }

    /**
     * Registra 1 envio deste conteúdo. Chamar APÓS envio confirmado (mesma
     * lógica de recordSent). Cache::add é atômico e cria com TTL só se a
     * chave não existir; depois increment sobe o contador sem estender o TTL.
     */
    public function recordContentSent(WhatsappInstance $instance, string $content): void
    {
        $key = $this->fingerprintKey($instance, $content);
        $ttl = max(1, (int) now()->diffInSeconds(now()->endOfDay()));

        Cache::add($key, 0, $ttl);
        Cache::increment($key);
    }

    /**
     * Detecta padrão de spintax {a|b|c} — usado pelo controller para alertar
     * o gestor quando a campanha grande vai sem variação de conteúdo.
     */
    public static function hasSpintax(string $message): bool
    {
        return (bool) preg_match('/\{[^{}]*\|[^{}]*\}/', $message);
    }

    // ── Diversidade de destinatários (Fase 2 Anti-Ban 2026) ─────────────────
    // Disparo pra muitos contatos que NUNCA responderam é padrão de spam
    // clássico. Se o ratio ultrapassa NEW_RECIPIENT_RISK_THRESHOLD, o job
    // ativa modo conservador — dobra o delay entre envios sem alterar
    // permanentemente a instância.

    /**
     * Retorna a proporção de destinatários "novos" (sem inbound registrado)
     * num conjunto de wa_ids, no tenant da instância.
     *
     * "Novo" = chat sem `last_inbound_at`, i.e. o contato nunca respondeu
     * a uma mensagem nossa. Contato conhecido = ao menos 1 mensagem inbound
     * recebida no histórico.
     *
     * A escolha por `last_inbound_at` (coluna no próprio chat) em vez de
     * subquery em `whatsapp_messages` é deliberada: já está indexada e
     * atualizada em toda inbound; evita join pesado em broadcasts com
     * milhares de destinatários.
     *
     * Escopo por tenant (não por instance) porque `WhatsappChat` é
     * tenant-scoped: se o tenant tem múltiplas instâncias, o histórico
     * de relação com o contato é compartilhado.
     *
     * @param array<int,string> $waIds Lista de wa_ids (números) da audiência.
     * @return float Entre 0.0 (todos conhecidos) e 1.0 (todos novos).
     */
    public function getNewRecipientRatio(WhatsappInstance $instance, array $waIds): float
    {
        $unique = array_values(array_unique(array_filter($waIds, fn ($id) => $id !== null && $id !== '')));
        $total  = count($unique);

        if ($total === 0) {
            return 0.0;
        }

        $known = \App\Models\WhatsappChat::where('tenant_id', $instance->tenant_id)
            ->whereIn('wa_id', $unique)
            ->whereNotNull('last_inbound_at')
            ->count();

        return 1.0 - ($known / $total);
    }

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
    public function markAsRestricted(WhatsappInstance $instance, ?int $hours = null): void
    {
        // Tarefa 3.2: default antes era constant — agora resolvido via config.
        // Caller pode passar valor explícito para sobrescrever.
        $hours    = $hours ?? $this->getBanRestrictionHours();
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
     * Após o último dia do perfil ativo, desativa warming e usa daily_limit normal.
     *
     * Tarefa 3.2: tamanho do warming agora vem do perfil (config), não mais
     * hardcoded em 14 dias. Perfil 'default' = 14 dias (mesma duração de antes).
     * Perfil 'conservative' também = 14 dias, com limites menores por dia.
     */
    public function getWarmingDailyLimit(WhatsappInstance $instance): int
    {
        $day     = $this->getWarmingDay($instance);
        $profile = $this->getWarmingProfile($instance);
        $maxDay  = empty($profile) ? 14 : max(array_keys($profile));

        if ($day > $maxDay) {
            $settings = $instance->settings ?? [];
            $settings['warming_mode'] = false;
            $instance->update(['settings' => $settings]);
            Log::info("AntiBan: warming concluído para [{$instance->instance_name}] após {$day} dias.");
            return $instance->daily_limit;
        }

        // Caso o dia não esteja no perfil (configuração malformada), cai no
        // último valor disponível — comportamento conservador.
        return $profile[$day] ?? ($profile[$maxDay] ?? 370);
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
