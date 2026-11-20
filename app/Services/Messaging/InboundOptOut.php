<?php

namespace App\Services\Messaging;

use App\Models\WhatsappAuditLog;
use App\Models\WhatsappBlacklist;
use App\Models\WhatsappChat;

/**
 * Pedido de "sair da lista" vindo numa mensagem recebida — regra única para
 * todos os webhooks (Evolution, Cloud API e o caminho antigo).
 *
 * Antes cada webhook procurava as palavras DENTRO do texto ("pare" em
 * "parece", "sair" em "saíram"): o contato era bloqueado, ia para a lista
 * negra, a mensagem nem era salva e nenhuma resposta saía mais.
 */
class InboundOptOut
{
    /** Motivo gravado quando o bloqueio vem de palavra-chave (e pode ser desfeito). */
    public const REASON_PREFIX = 'STOP keyword';
    public const BLACKLIST_PREFIX = 'Opt-out via keyword';

    public static function isOptOut(?string $text): bool
    {
        return $text !== null && trim($text) !== '' && AntiBanManager::isOptOutMessage($text);
    }

    /** Número na lista negra só por palavra-chave (não por bloqueio manual da equipe). */
    public static function blacklistedByKeyword(int $tenantId, string $phone): bool
    {
        return WhatsappBlacklist::where('tenant_id', $tenantId)->where('phone', $phone)
            ->where('reason', 'like', self::BLACKLIST_PREFIX . '%')->exists();
    }

    public static function apply(WhatsappChat $chat, string $source, string $text): void
    {
        $chat->forceFill([
            'opt_out_at'     => now(),
            'blocked_at'     => now(),
            'blocked_reason' => self::REASON_PREFIX . " via {$source}",
            'status'         => 'closed',
        ])->save();

        WhatsappBlacklist::updateOrCreate(
            ['tenant_id' => $chat->tenant_id, 'phone' => $chat->wa_id],
            ['reason' => self::BLACKLIST_PREFIX . " ({$source})"]
        );

        WhatsappAuditLog::create([
            'tenant_id'  => $chat->tenant_id,
            'chat_id'    => $chat->id,
            'actor_type' => 'webhook_' . $source,
            'event'      => 'compliance_action',
            'details'    => ['action' => 'opt_out', 'reason' => 'STOP keyword', 'content_len' => mb_strlen($text)],
        ]);
    }

    /**
     * O contato voltou a escrever (mensagem comum, não "sair"): o bloqueio por
     * palavra-chave é desfeito — ele mesmo retomou a conversa. Bloqueio manual
     * da equipe continua.
     */
    public static function liftIfReengaged(WhatsappChat $chat, string $source): bool
    {
        $byKeyword = $chat->blocked_at && str_starts_with((string) $chat->blocked_reason, self::REASON_PREFIX);
        $listed    = self::blacklistedByKeyword((int) $chat->tenant_id, (string) $chat->wa_id);
        if (!$byKeyword && !$listed) {
            return false;
        }

        if ($byKeyword) {
            $chat->forceFill([
                'opt_out_at' => null, 'blocked_at' => null, 'blocked_reason' => null,
                'status' => 'open', 'opt_in_at' => $chat->opt_in_at ?? now(),
            ])->save();
        }
        WhatsappBlacklist::where('tenant_id', $chat->tenant_id)->where('phone', $chat->wa_id)
            ->where('reason', 'like', self::BLACKLIST_PREFIX . '%')->delete();

        WhatsappAuditLog::create([
            'tenant_id'  => $chat->tenant_id,
            'chat_id'    => $chat->id,
            'actor_type' => 'webhook_' . $source,
            'event'      => 'compliance_action',
            'details'    => ['action' => 'opt_in_again', 'reason' => 'contato voltou a escrever'],
        ]);
        return true;
    }
}
