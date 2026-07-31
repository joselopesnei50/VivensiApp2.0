<?php

namespace App\Services\WhatsAppService;

use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappQuotaEvent;
use App\Models\WhatsappQuotaUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor de cota mensal WhatsApp (Modelo Comercial C).
 *
 * Regras invariantes:
 *   - Tudo passa por transaction + lockForUpdate (concorrência de webhook)
 *   - consumeConversation é idempotente por whatsapp_conversation_id
 *   - Nunca subtrai extra_pack_conversations pra baixo de 0
 *   - Se plan_included_snapshot == 0 no primeiro consumo, faz snapshot do
 *     plano atual do tenant (protege se admin trocar de plano no meio do mês)
 *   - Cada categoria (marketing/utility/authentication) conta 1 unidade —
 *     simplicidade pro cliente entender
 */
class WhatsappQuotaService
{
    /**
     * Retorna a linha de uso do tenant. Cria on-demand com snapshot do
     * plano atual + period_start no primeiro dia do mês corrente.
     */
    public function getUsage(int $tenantId): WhatsappQuotaUsage
    {
        return DB::transaction(function () use ($tenantId) {
            $usage = WhatsappQuotaUsage::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['tenant_id' => $tenantId],
                    [
                        'conversations_used_month' => 0,
                        'extra_pack_conversations' => 0,
                        'plan_included_snapshot'   => $this->planIncluded($tenantId),
                        'period_start'             => now()->startOfMonth()->toDateString(),
                    ]
                );

            // Se plan_included_snapshot é 0 (edge case pra registros criados
            // antes de virar pré-pago), atualiza com o valor atual do plano.
            if ($usage->plan_included_snapshot === 0) {
                $current = $this->planIncluded($tenantId);
                if ($current > 0) {
                    $usage->plan_included_snapshot = $current;
                    $usage->save();
                }
            }

            return $usage;
        });
    }

    /** Quanto o plano do tenant inclui de conversas WhatsApp por mês. */
    public function planIncluded(int $tenantId): int
    {
        $tenant = Tenant::find($tenantId);
        return (int) ($tenant?->plan?->whatsapp_conversations_included ?? 0);
    }

    /** Cliente tem cota disponível pra enviar pelo menos 1 conversation? */
    public function hasQuota(int $tenantId): bool
    {
        return $this->getUsage($tenantId)->hasQuota();
    }

    /**
     * Consome 1 unidade da cota quando uma conversation Meta billable é criada.
     * Idempotente — reenvio de webhook Meta não cobra 2x.
     * Se cota estava esgotada, registra evento consume com delta=0 pra rastro.
     */
    public function consumeConversation(WhatsappConversation $conversation): ?WhatsappQuotaEvent
    {
        if (!$conversation->is_billable) return null;

        $tenantId = $conversation->tenant_id;

        // Idempotência: se já tem evento consume pra essa conversation, retorna.
        $existing = WhatsappQuotaEvent::where('whatsapp_conversation_id', $conversation->id)
            ->where('type', WhatsappQuotaEvent::TYPE_CONSUME)
            ->first();
        if ($existing) return $existing;

        return DB::transaction(function () use ($tenantId, $conversation) {
            $usage = WhatsappQuotaUsage::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['tenant_id' => $tenantId],
                    [
                        'conversations_used_month' => 0,
                        'extra_pack_conversations' => 0,
                        'plan_included_snapshot'   => $this->planIncluded($tenantId),
                        'period_start'             => now()->startOfMonth()->toDateString(),
                    ]
                );

            $overQuota = $usage->isOverQuota();

            if ($overQuota) {
                // Cota já estava estourada. Não incrementa contador (evita
                // ficar com números absurdos), mas registra o evento pra
                // rastreabilidade + trigger de alerta admin no log.
                Log::warning('WhatsappQuota: consumo além da cota', [
                    'tenant_id'       => $tenantId,
                    'conversation_id' => $conversation->id,
                    'used'            => $usage->conversations_used_month,
                    'available'       => $usage->totalAvailable(),
                ]);
                return WhatsappQuotaEvent::create([
                    'tenant_id'                => $tenantId,
                    'type'                     => WhatsappQuotaEvent::TYPE_CONSUME,
                    'conversations_delta'      => 0,
                    'used_after'               => $usage->conversations_used_month,
                    'extra_pack_after'         => $usage->extra_pack_conversations,
                    'description'              => "COTA ESGOTADA: {$conversation->category} ({$conversation->country_code}) — envio não contabilizado",
                    'whatsapp_conversation_id' => $conversation->id,
                ]);
            }

            // Incrementa. Se tem pack extra, prefere consumir dele primeiro
            // pra dar visibilidade que os packs são "descontados" antes.
            // Mas conversations_used_month sempre sobe — a lógica de
            // "quanto sobra" é remaining() = total_available - used.
            $usage->conversations_used_month += 1;
            $usage->save();

            return WhatsappQuotaEvent::create([
                'tenant_id'                => $tenantId,
                'type'                     => WhatsappQuotaEvent::TYPE_CONSUME,
                'conversations_delta'      => -1,
                'used_after'               => $usage->conversations_used_month,
                'extra_pack_after'         => $usage->extra_pack_conversations,
                'description'              => "WhatsApp {$conversation->category} ({$conversation->country_code})",
                'whatsapp_conversation_id' => $conversation->id,
            ]);
        });
    }

    /**
     * Adiciona pack extra pro tenant (admin lança manualmente após confirmar
     * pagamento fora — Fase 2 vira webhook AbacatePay).
     */
    public function addExtraPack(int $tenantId, int $conversations, string $description, ?int $actorUserId = null): WhatsappQuotaEvent
    {
        if ($conversations <= 0) {
            throw new \RuntimeException('Quantidade de pack extra deve ser positiva.');
        }

        return DB::transaction(function () use ($tenantId, $conversations, $description, $actorUserId) {
            $usage = WhatsappQuotaUsage::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['tenant_id' => $tenantId],
                    [
                        'conversations_used_month' => 0,
                        'extra_pack_conversations' => 0,
                        'plan_included_snapshot'   => $this->planIncluded($tenantId),
                        'period_start'             => now()->startOfMonth()->toDateString(),
                    ]
                );

            $usage->extra_pack_conversations += $conversations;
            $usage->over_quota_alerted_at = null; // reseta alerta pra próximo ciclo
            $usage->save();

            return WhatsappQuotaEvent::create([
                'tenant_id'           => $tenantId,
                'type'                => WhatsappQuotaEvent::TYPE_EXTRA_PACK,
                'conversations_delta' => $conversations,
                'used_after'          => $usage->conversations_used_month,
                'extra_pack_after'    => $usage->extra_pack_conversations,
                'description'         => $description,
                'actor_user_id'       => $actorUserId,
            ]);
        });
    }

    /**
     * Ajuste manual do admin (correção de bug, cortesia, remoção). Pode ser
     * positivo (crédito extra) ou negativo (débito).
     */
    public function adjustment(int $tenantId, int $conversationsDelta, string $description, int $actorUserId): WhatsappQuotaEvent
    {
        return DB::transaction(function () use ($tenantId, $conversationsDelta, $description, $actorUserId) {
            $usage = $this->getUsage($tenantId);
            $usage->refresh();

            // Ajuste positivo vira pack extra; negativo desconta do usage
            if ($conversationsDelta > 0) {
                $usage->extra_pack_conversations += $conversationsDelta;
            } else {
                $usage->conversations_used_month = max(0, $usage->conversations_used_month + $conversationsDelta);
            }
            $usage->save();

            return WhatsappQuotaEvent::create([
                'tenant_id'           => $tenantId,
                'type'                => WhatsappQuotaEvent::TYPE_ADJUSTMENT,
                'conversations_delta' => $conversationsDelta,
                'used_after'          => $usage->conversations_used_month,
                'extra_pack_after'    => $usage->extra_pack_conversations,
                'description'         => $description,
                'actor_user_id'       => $actorUserId,
            ]);
        });
    }

    /**
     * Reset mensal — chamado pelo command whatsapp:reset-monthly-quotas
     * agendado no scheduler dia 1 às 00:05. Zera contador + pack extra e
     * atualiza period_start + plan_included_snapshot.
     * Retorna qtd de tenants resetados.
     */
    public function resetAllMonthlyQuotas(): int
    {
        $count = 0;
        WhatsappQuotaUsage::withoutGlobalScopes()->orderBy('id')->chunk(200, function ($usages) use (&$count) {
            foreach ($usages as $usage) {
                DB::transaction(function () use ($usage, &$count) {
                    $usage = WhatsappQuotaUsage::withoutGlobalScopes()
                        ->lockForUpdate()
                        ->find($usage->id);

                    // Log snapshot antes de resetar
                    WhatsappQuotaEvent::create([
                        'tenant_id'           => $usage->tenant_id,
                        'type'                => WhatsappQuotaEvent::TYPE_RESET,
                        'conversations_delta' => 0,
                        'used_after'          => 0,
                        'extra_pack_after'    => 0,
                        'description'         => sprintf(
                            'Reset mensal — fim do período %s (usadas %d, pack extra remanescente %d, plano %d)',
                            $usage->period_start->format('m/Y'),
                            $usage->conversations_used_month,
                            $usage->extra_pack_conversations,
                            $usage->plan_included_snapshot,
                        ),
                    ]);

                    $usage->conversations_used_month = 0;
                    $usage->extra_pack_conversations = 0; // pack extra também zera
                    $usage->plan_included_snapshot   = $this->planIncluded($usage->tenant_id);
                    $usage->period_start             = now()->startOfMonth()->toDateString();
                    $usage->over_quota_alerted_at    = null;
                    $usage->save();

                    $count++;
                });
            }
        });
        return $count;
    }
}
