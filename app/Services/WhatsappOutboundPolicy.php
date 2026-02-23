<?php

namespace App\Services;

use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\RateLimiter;

class WhatsappOutboundPolicy
{
    /**
     * Decide if we can send an outbound message right now.
     *
     * Rules (anti-ban & compliance):
     * - Respect blocks/opt-out
     * - Require opt-in (configurable)
     * - Throttle per-tenant and per-recipient
     * - Enforce minimum delay between outbound messages per chat
     */
    public function canSend(WhatsappConfig $config, WhatsappChat $chat, bool $isTemplate = false, ?string &$reason = null, ?string &$code = null): bool
    {
        if (!$config->outbound_enabled) {
            $reason = 'Envio de mensagens está desativado nas configurações.';
            $code = 'OUTBOUND_DISABLED';
            return false;
        }

        if ($chat->blocked_at) {
            $reason = 'Contato bloqueado.';
            $code = 'CONTACT_BLOCKED';
            return false;
        }

        if ($chat->opt_out_at) {
            $reason = 'Contato opt-out (STOP).';
            $code = 'CONTACT_OPTOUT';
            return false;
        }

        // Global SaaS Blacklist check
        $isBlacklisted = \App\Models\WhatsappBlacklist::where('tenant_id', $config->tenant_id)
            ->where('phone', $chat->wa_id)
            ->exists();

        if ($isBlacklisted) {
            $reason = 'Número bloqueado globalmente (Blacklist).';
            $code = 'CONTACT_BLACKLISTED';
            return false;
        }

        if ($config->require_opt_in && !$chat->opt_in_at) {
            $reason = 'Sem opt-in/consentimento registrado para este contato.';
            $code = 'OPTIN_REQUIRED';
            return false;
        }

        // 24h window: allow only replies within 24h of last inbound, unless template is allowed.
        if (($config->enforce_24h_window ?? true)) {
            $lastInbound = $chat->last_inbound_at;
            $windowOpen = $lastInbound ? $lastInbound->gt(now()->subHours(24)) : false;

            if (!$windowOpen) {
                $allowTemplate = $isTemplate && (bool) ($config->allow_templates_outside_window ?? true);
                if (!$allowTemplate) {
                    $reason = 'Janela de 24h fechada. Use um template (mensagem aprovada) ou aguarde o cliente iniciar.';
                    $code = 'OUTSIDE_24H_WINDOW';
                    return false;
                }
            }
        }

        $minDelay = max(0, (int) ($config->min_outbound_delay_seconds ?? 0));
        if ($minDelay > 0 && $chat->last_outbound_at) {
            $since = now()->diffInSeconds($chat->last_outbound_at);
            if ($since < $minDelay) {
                $reason = 'Aguarde alguns segundos antes de enviar outra mensagem (cadência).';
                $code = 'MIN_DELAY';
                return false;
            }
        }

        $perMinute = max(1, (int) ($config->max_outbound_per_minute ?? 12));

        // Per-tenant throttle
        $tenantKey = 'wa:out:tenant:' . (int) $config->tenant_id;
        if (RateLimiter::tooManyAttempts($tenantKey, $perMinute)) {
            $reason = 'Limite de envios por minuto atingido (tenant).';
            $code = 'TENANT_THROTTLE';
            return false;
        }

        // Per-recipient throttle (more strict)
        $recipientKey = 'wa:out:to:' . (int) $config->tenant_id . ':' . (string) $chat->wa_id;
        if (RateLimiter::tooManyAttempts($recipientKey, max(1, (int) ceil($perMinute / 2)))) {
            $reason = 'Limite de envios por minuto atingido (contato).';
            $code = 'RECIPIENT_THROTTLE';
            return false;
        }

        return true;
    }

    public function recordSend(WhatsappConfig $config, WhatsappChat $chat): void
    {
        $perMinute = max(1, (int) ($config->max_outbound_per_minute ?? 12));
        RateLimiter::hit('wa:out:tenant:' . (int) $config->tenant_id, 60);
        RateLimiter::hit('wa:out:to:' . (int) $config->tenant_id . ':' . (string) $chat->wa_id, 60);

        $chat->last_outbound_at = now();
        $chat->save();
    }
}

