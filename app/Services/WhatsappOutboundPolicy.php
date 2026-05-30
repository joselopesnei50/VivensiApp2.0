<?php

namespace App\Services;

use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\RateLimiter;

class WhatsappOutboundPolicy
{
    /**
     * Verifica APENAS as regras de conformidade LGPD/anti-spam.
     * Fonte única de verdade compartilhada entre chat individual (canSend)
     * e broadcast em massa (ProcessBroadcastCampaignJob).
     *
     * @param  bool   $requireOptIn     Config do tenant: exige opt-in explícito
     * @param  mixed  $optInAt          WhatsappChat::opt_in_at (null = sem consentimento)
     * @param  mixed  $optOutAt         WhatsappChat::opt_out_at (null = não pediu saída)
     * @param  mixed  $blockedAt        WhatsappChat::blocked_at (null = não bloqueado)
     * @param  bool   $isBlacklisted    Número está na WhatsappBlacklist do tenant
     * @return string|null              Código do bloqueio, ou null se liberado
     */
    public function complianceStatus(
        bool  $requireOptIn,
        mixed $optInAt,
        mixed $optOutAt,
        mixed $blockedAt,
        bool  $isBlacklisted
    ): ?string {
        if ($blockedAt)                 return 'CONTACT_BLOCKED';
        if ($optOutAt)                  return 'CONTACT_OPTOUT';
        if ($isBlacklisted)             return 'CONTACT_BLACKLISTED';
        if ($requireOptIn && !$optInAt) return 'OPTIN_REQUIRED';
        return null;
    }

    /**
     * Decide if we can send an outbound message right now.
     *
     * Rules (anti-ban & compliance):
     * - Respect blocks/opt-out (via complianceStatus — mesma lógica do broadcast)
     * - Require opt-in (configurable)
     * - Throttle per-tenant and per-recipient
     * - Enforce minimum delay between outbound messages per chat
     */
    public function canSend(WhatsappConfig $config, WhatsappChat $chat, bool $isTemplate = false, ?string &$reason = null, ?string &$code = null, bool $isAi = false): bool
    {
        if (!$config->outbound_enabled && !$isAi) {
            $reason = 'Envio de mensagens está desativado nas configurações.';
            $code   = 'OUTBOUND_DISABLED';
            return false;
        }

        // ── Compliance LGPD — delega a complianceStatus (fonte única de verdade) ──
        $isBlacklisted = \App\Models\WhatsappBlacklist::where('tenant_id', $config->tenant_id)
            ->where('phone', $chat->wa_id)
            ->exists();

        $blockCode = $this->complianceStatus(
            (bool) ($config->require_opt_in ?? false),
            $chat->opt_in_at,
            $chat->opt_out_at,
            $chat->blocked_at,
            $isBlacklisted
        );

        if ($blockCode) {
            $reason = match ($blockCode) {
                'CONTACT_BLOCKED'     => 'Contato bloqueado.',
                'CONTACT_OPTOUT'      => 'Contato opt-out (STOP).',
                'CONTACT_BLACKLISTED' => 'Número bloqueado globalmente (Blacklist).',
                'OPTIN_REQUIRED'      => 'Sem opt-in/consentimento registrado para este contato.',
                default               => 'Bloqueado por política de compliance.',
            };
            $code = $blockCode;
            return false;
        }

        // 24h window: allow only replies within 24h of last inbound, unless template is allowed.
        if (($config->enforce_24h_window ?? true) && !$isAi) {
            $lastInbound = $chat->last_inbound_at;
            $windowOpen  = $lastInbound ? $lastInbound->gt(now()->subHours(24)) : false;

            if (!$windowOpen) {
                $allowTemplate = $isTemplate && (bool) ($config->allow_templates_outside_window ?? true);
                if (!$allowTemplate) {
                    $reason = 'Janela de 24h fechada. Use um template (mensagem aprovada) ou aguarde o cliente iniciar.';
                    $code   = 'OUTSIDE_24H_WINDOW';
                    return false;
                }
            }
        }

        $minDelay = max(0, (int) ($config->min_outbound_delay_seconds ?? 0));
        if ($minDelay > 0 && $chat->last_outbound_at) {
            $since = now()->diffInSeconds($chat->last_outbound_at);
            if ($since < $minDelay) {
                $reason = 'Aguarde alguns segundos antes de enviar outra mensagem (cadência).';
                $code   = 'MIN_DELAY';
                return false;
            }
        }

        $perMinute = max(1, (int) ($config->max_outbound_per_minute ?? 12));

        $tenantKey = 'wa:out:tenant:' . (int) $config->tenant_id;
        if (RateLimiter::tooManyAttempts($tenantKey, $perMinute)) {
            $reason = 'Limite de envios por minuto atingido (tenant).';
            $code   = 'TENANT_THROTTLE';
            return false;
        }

        $recipientKey = 'wa:out:to:' . (int) $config->tenant_id . ':' . (string) $chat->wa_id;
        if (RateLimiter::tooManyAttempts($recipientKey, max(1, (int) ceil($perMinute / 2)))) {
            $reason = 'Limite de envios por minuto atingido (contato).';
            $code   = 'RECIPIENT_THROTTLE';
            return false;
        }

        return true;
    }

    public function recordSend(WhatsappConfig $config, WhatsappChat $chat): void
    {
        RateLimiter::hit('wa:out:tenant:' . (int) $config->tenant_id, 60);
        RateLimiter::hit('wa:out:to:' . (int) $config->tenant_id . ':' . (string) $chat->wa_id, 60);

        $chat->last_outbound_at = now();
        $chat->save();
    }
}
