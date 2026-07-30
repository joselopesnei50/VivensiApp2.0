<?php

namespace App\Services\WhatsAppService;

use App\Models\WhatsappConversation;
use App\Models\WhatsappCreditBalance;
use App\Models\WhatsappCreditTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Motor de saldo pré-pago WhatsApp (Fase 1 do modelo comercial A).
 *
 * Regras invariantes:
 *   - balance_brl_micros NUNCA fica negativo (checagem no debit)
 *   - Toda mutação passa por transaction DB + lockForUpdate
 *   - Debit é idempotente por whatsapp_conversation_id (mesma conversation
 *     nunca cobra 2x, mesmo se webhook Meta reenviar)
 *   - Se prepaid_enabled_at é null, TODAS as operações viram no-op
 *     (retorna false/null silencioso — modelo B, sem cobrança)
 *
 * Cálculo de preço final ao cliente:
 *   customer_price_brl = (meta_cost_usd_micros * usd_brl_rate * (1 + markup_pct/100)) / 1_000_000
 */
class WhatsappCreditService
{
    /**
     * Retorna o balance do tenant (cria on-demand com saldo zero se não existe).
     */
    public function getBalance(int $tenantId): WhatsappCreditBalance
    {
        return WhatsappCreditBalance::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['balance_brl_micros' => 0]
        );
    }

    public function isPrepaidEnabled(int $tenantId): bool
    {
        return $this->getBalance($tenantId)->isPrepaidEnabled();
    }

    /**
     * Verifica se o tenant tem saldo pra enviar UMA conversation
     * (usa worst case: categoria marketing no país mais caro presente).
     */
    public function hasBalanceForSend(int $tenantId, string $countryCode = 'BR'): bool
    {
        $balance = $this->getBalance($tenantId);
        if (!$balance->isPrepaidEnabled()) return true; // modelo B: sempre libera
        $worstCase = $this->customerPriceBrlMicros($countryCode, 'marketing');
        return $balance->balance_brl_micros >= $worstCase;
    }

    /**
     * Ativa pré-pago pro tenant (sem creditar — chame credit() depois).
     * Idempotente: chamar 2x não muda nada.
     */
    public function enablePrepaid(int $tenantId, ?int $actorUserId = null): WhatsappCreditBalance
    {
        return DB::transaction(function () use ($tenantId, $actorUserId) {
            $balance = WhatsappCreditBalance::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstOrCreate(['tenant_id' => $tenantId], ['balance_brl_micros' => 0]);
            if (!$balance->isPrepaidEnabled()) {
                $balance->prepaid_enabled_at = now();
                $balance->save();
                Log::info('WhatsappCredit: pré-pago ativado', ['tenant_id' => $tenantId, 'actor' => $actorUserId]);
            }
            return $balance;
        });
    }

    /** Desativa pré-pago. Saldo é preservado, mas nada é debitado até reativar. */
    public function disablePrepaid(int $tenantId, ?int $actorUserId = null): WhatsappCreditBalance
    {
        return DB::transaction(function () use ($tenantId, $actorUserId) {
            $balance = WhatsappCreditBalance::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstOrCreate(['tenant_id' => $tenantId], ['balance_brl_micros' => 0]);
            if ($balance->isPrepaidEnabled()) {
                $balance->prepaid_enabled_at = null;
                $balance->save();
                Log::info('WhatsappCredit: pré-pago desativado', ['tenant_id' => $tenantId, 'actor' => $actorUserId]);
            }
            return $balance;
        });
    }

    /**
     * Recarga (topup) — soma no saldo + grava transaction.
     * Fase 1: chamada manualmente pelo admin após confirmar PIX/pagamento fora.
     * Fase 2: será chamada pelo webhook AbacatePay.
     */
    public function credit(int $tenantId, int $amountBrlMicros, string $description, ?int $actorUserId = null, string $type = WhatsappCreditTransaction::TYPE_TOPUP): WhatsappCreditTransaction
    {
        if ($amountBrlMicros <= 0) {
            throw new RuntimeException('Valor de crédito deve ser positivo.');
        }
        if ($type === WhatsappCreditTransaction::TYPE_DEBIT) {
            throw new RuntimeException('credit() não aceita type=debit. Use debitForConversation.');
        }

        return DB::transaction(function () use ($tenantId, $amountBrlMicros, $description, $actorUserId, $type) {
            $balance = WhatsappCreditBalance::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstOrCreate(['tenant_id' => $tenantId], ['balance_brl_micros' => 0]);

            $balance->balance_brl_micros += $amountBrlMicros;
            if ($type === WhatsappCreditTransaction::TYPE_TOPUP) {
                $balance->last_topup_brl_micros = $amountBrlMicros;
                $balance->low_balance_alerted_at = null; // reseta alerta pra próximo ciclo
            }
            $balance->save();

            return WhatsappCreditTransaction::create([
                'tenant_id'                => $tenantId,
                'type'                     => $type,
                'amount_brl_micros'        => $amountBrlMicros,
                'balance_after_brl_micros' => $balance->balance_brl_micros,
                'description'              => $description,
                'actor_user_id'            => $actorUserId,
            ]);
        });
    }

    /**
     * Débito automático quando uma conversation Meta billable é criada.
     * Idempotente por whatsapp_conversation_id — reenvio de webhook não cobra 2x.
     * Se saldo for insuficiente, DEBITA MESMO ASSIM e deixa negativo? NÃO.
     * Deixa transação com amount=0 + description "SEM SALDO" pra rastro (a
     * conversation já foi enviada — Meta já processou; só evitamos "saldo
     * negativo eterno"). Alerta o admin via log.
     */
    public function debitForConversation(WhatsappConversation $conversation): ?WhatsappCreditTransaction
    {
        if (!$conversation->is_billable) return null;

        $tenantId = $conversation->tenant_id;
        $balance  = $this->getBalance($tenantId);
        if (!$balance->isPrepaidEnabled()) return null;

        // Idempotência
        $existing = WhatsappCreditTransaction::where('whatsapp_conversation_id', $conversation->id)
            ->where('type', WhatsappCreditTransaction::TYPE_DEBIT)
            ->first();
        if ($existing) return $existing;

        $costMicros = $this->customerPriceBrlMicros(
            $conversation->country_code ?? 'default',
            $conversation->category ?? 'utility'
        );

        return DB::transaction(function () use ($tenantId, $conversation, $costMicros) {
            $bal = WhatsappCreditBalance::withoutGlobalScopes()
                ->lockForUpdate()
                ->firstWhere('tenant_id', $tenantId);

            $insufficient = $bal->balance_brl_micros < $costMicros;

            if ($insufficient) {
                // Não deixamos negativo — apenas registramos o "shortfall" pra
                // reconciliação futura. Conversa Meta já foi processada;
                // ideal seria bloquear no envio (WhatsAppService faz isso),
                // mas conversas service-window podem entrar sem passar por lá.
                Log::warning('WhatsappCredit: debit sem saldo suficiente', [
                    'tenant_id'      => $tenantId,
                    'conversation'   => $conversation->id,
                    'cost_micros'    => $costMicros,
                    'balance_micros' => $bal->balance_brl_micros,
                ]);
                return WhatsappCreditTransaction::create([
                    'tenant_id'                => $tenantId,
                    'type'                     => WhatsappCreditTransaction::TYPE_DEBIT,
                    'amount_brl_micros'        => 0,
                    'balance_after_brl_micros' => $bal->balance_brl_micros,
                    'description'              => "SEM SALDO: {$conversation->category} (custo esperado " . number_format($costMicros / 1_000_000, 4, ',', '.') . " BRL não cobrado)",
                    'whatsapp_conversation_id' => $conversation->id,
                ]);
            }

            $bal->balance_brl_micros -= $costMicros;
            $bal->save();

            return WhatsappCreditTransaction::create([
                'tenant_id'                => $tenantId,
                'type'                     => WhatsappCreditTransaction::TYPE_DEBIT,
                'amount_brl_micros'        => -$costMicros,
                'balance_after_brl_micros' => $bal->balance_brl_micros,
                'description'              => "WhatsApp {$conversation->category} ({$conversation->country_code})",
                'whatsapp_conversation_id' => $conversation->id,
            ]);
        });
    }

    /**
     * Preço final ao cliente em BRL micros = custo Meta USD * câmbio * (1 + markup).
     * Ex: marketing BR = 62_500 USD micros = $0.0625 * 5.50 = R$ 0.34375 * 1.5 (markup 50%) = R$ 0.5156 = 515_625 BRL micros
     */
    public function customerPriceBrlMicros(string $countryCode, string $category): int
    {
        $table = config('whatsapp_pricing.pricing', []);
        $country = isset($table[$countryCode]) ? $countryCode : 'default';
        $usdMicros = (int) ($table[$country][$category] ?? 0);

        if ($usdMicros === 0) return 0;

        $rate       = (float) config('whatsapp_pricing.usd_brl_rate', 5.50);
        $markupPct  = (float) config('whatsapp_pricing.default_markup_pct', 50);
        $multiplier = 1 + ($markupPct / 100);

        return (int) round($usdMicros * $rate * $multiplier);
    }
}
