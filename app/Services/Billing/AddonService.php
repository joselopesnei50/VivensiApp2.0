<?php

namespace App\Services\Billing;

use App\Models\BillingAddon;
use App\Models\Notification;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantAddon;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Adicionais contratados à parte do plano (número extra no bot, pacote de
 * e-mails por dia).
 *
 * Regras (decididas em 2026-11-24):
 *  - o cliente contrata sozinho, a liberação é na hora e a cobrança entra na
 *    PRÓXIMA fatura com o mês cheio (sem proporcional);
 *  - a fatura usa a quantidade contratada no momento em que é gerada;
 *  - plano anual: o adicional entra ×12 na fatura anual;
 *  - cada conta tem 1 número do bot incluso no plano.
 */
class AddonService
{
    /** Números do bot inclusos em qualquer plano. */
    public const BOT_INCLUDED = 1;

    /** @return Collection<int, BillingAddon> */
    public function catalog(): Collection
    {
        return BillingAddon::where('is_active', true)->orderBy('id')->get();
    }

    public function addon(string $code): ?BillingAddon
    {
        return BillingAddon::where('code', $code)->first();
    }

    /** Unidades contratadas de um adicional (0 se nada). */
    public function quantity(Tenant $tenant, string $code): int
    {
        return (int) TenantAddon::forTenantUnscoped($tenant->id)
            ->join('billing_addons', 'billing_addons.id', '=', 'tenant_addons.billing_addon_id')
            ->where('billing_addons.code', $code)
            ->value('tenant_addons.quantity');
    }

    /** @return array<string,int> code => quantidade */
    public function quantities(Tenant $tenant): array
    {
        return TenantAddon::forTenantUnscoped($tenant->id)
            ->join('billing_addons', 'billing_addons.id', '=', 'tenant_addons.billing_addon_id')
            ->where('tenant_addons.quantity', '>', 0)
            ->pluck('tenant_addons.quantity', 'billing_addons.code')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /** E-mails/dia a mais vindos dos pacotes contratados. */
    public function extraEmailsPerDay(Tenant $tenant): int
    {
        $addon = $this->addon(BillingAddon::EMAIL_PACK);
        return $addon ? $this->quantity($tenant, BillingAddon::EMAIL_PACK) * max(0, $addon->unit_amount) : 0;
    }

    /** Quantas pessoas da organização podem usar o bot ao mesmo tempo. */
    public function botSeatLimit(Tenant $tenant): int
    {
        $addon = $this->addon(BillingAddon::BOT_NUMBER);
        $extra = $addon ? $this->quantity($tenant, BillingAddon::BOT_NUMBER) * max(0, $addon->unit_amount) : 0;
        return self::BOT_INCLUDED + $extra;
    }

    public function botSeatsUsed(Tenant $tenant): int
    {
        return User::where('tenant_id', $tenant->id)->where('bot_access', true)->count();
    }

    /** Pessoas que podem receber acesso ao bot (sem credenciado e admin da plataforma). */
    public function botCandidates(Tenant $tenant): Collection
    {
        return User::where('tenant_id', $tenant->id)
            ->whereNotIn('role', ['super_admin', 'credenciado'])
            ->orderByDesc('bot_access')->orderBy('name')
            ->get();
    }

    /** Só conta com plano pago ativo contrata (cortesia e teste não geram fatura). */
    public function canContract(Tenant $tenant): bool
    {
        $plan = $tenant->plan;
        return $tenant->subscription_status === 'active'
            && $plan && !$plan->is_courtesy && (float) $plan->price > 0
            && !$tenant->cancel_requested_at;
    }

    /**
     * Define quanto a organização tem de um adicional (contratar, aumentar,
     * diminuir ou cancelar com 0). Devolve a mensagem de erro ou null.
     */
    public function setQuantity(Tenant $tenant, BillingAddon $addon, int $quantity, ?User $actor = null): ?string
    {
        if (!$addon->is_active && $quantity > 0) {
            return 'Este adicional não está disponível no momento.';
        }
        if ($quantity < 0 || $quantity > $addon->max_quantity) {
            return "Escolha de 0 a {$addon->max_quantity}.";
        }
        $current = $this->quantity($tenant, $addon->code);
        if ($quantity > $current && !$this->canContract($tenant)) {
            return 'Adicionais só podem ser contratados com um plano pago ativo. Fale com o nosso suporte.';
        }

        $error = DB::transaction(function () use ($tenant, $addon, $quantity, $actor, $current) {
            // Trava a linha da organização: dois cliques ao mesmo tempo não
            // deixam o bot com mais pessoas que o limite.
            $row = TenantAddon::forTenantUnscoped($tenant->id)
                ->where('billing_addon_id', $addon->id)->lockForUpdate()->first();

            if ($addon->code === BillingAddon::BOT_NUMBER) {
                $limit = self::BOT_INCLUDED + $quantity * max(0, $addon->unit_amount);
                $used  = $this->botSeatsUsed($tenant);
                if ($used > $limit) {
                    $tirar = $used - $limit;
                    return "Hoje {$used} pessoas usam o bot. Tire o acesso de {$tirar} " . ($tirar === 1 ? 'pessoa' : 'pessoas') . ' antes de diminuir.';
                }
            }

            if ($row) {
                $row->quantity   = $quantity;
                $row->updated_by = $actor?->id;
                $row->save();
            } else {
                TenantAddon::create([
                    'tenant_id'        => $tenant->id,
                    'billing_addon_id' => $addon->id,
                    'quantity'         => $quantity,
                    'updated_by'       => $actor?->id,
                ]);
            }
            return null;
        });

        if ($error === null && $quantity !== $current) {
            $this->notifyChange($tenant, $addon, $current, $quantity, $actor);
        }
        return $error;
    }

    /** Liga ou desliga o bot para uma pessoa da organização. Devolve erro ou null. */
    public function setBotAccess(Tenant $tenant, User $member, bool $on): ?string
    {
        if ((int) $member->tenant_id !== (int) $tenant->id || in_array($member->role, ['super_admin', 'credenciado'], true)) {
            return 'Esta pessoa não pode usar o bot.';
        }
        if (!$on) {
            $member->forceFill(['bot_access' => false])->save();
            return null;
        }
        if ($member->bot_access) {
            return null;
        }
        if (($member->status ?? 'active') === 'suspended') {
            return 'O acesso desta pessoa está suspenso.';
        }

        return DB::transaction(function () use ($tenant, $member) {
            // Serializa com a contratação (mesma trava) para respeitar o limite.
            User::where('tenant_id', $tenant->id)->where('bot_access', true)->lockForUpdate()->get(['id']);
            if ($this->botSeatsUsed($tenant) >= $this->botSeatLimit($tenant)) {
                return 'Todos os números do bot estão em uso. Contrate um número extra ou tire o acesso de alguém.';
            }
            $member->forceFill(['bot_access' => true])->save();
            return null;
        });
    }

    /**
     * Linhas da fatura: plano + adicionais contratados. No plano anual o
     * adicional (preço mensal) entra ×12.
     *
     * @return array<int, array{type:string,code:?string,name:string,product_id:?string,quantity:int,unit_cents:int,total_cents:int}>
     */
    public function invoiceLines(Tenant $tenant, SubscriptionPlan $plan): array
    {
        $planCents = (int) round(((float) $plan->price) * 100);
        $lines = [[
            'type'        => 'plan',
            'code'        => null,
            'name'        => 'Plano ' . $plan->name,
            'product_id'  => $plan->abacatepay_product_id ?: null,
            'quantity'    => 1,
            'unit_cents'  => $planCents,
            'total_cents' => $planCents,
        ]];

        $months = ($plan->interval ?? 'monthly') === 'yearly' ? 12 : 1;
        $rows = TenantAddon::forTenantUnscoped($tenant->id)
            ->where('quantity', '>', 0)->with('addon')->orderBy('billing_addon_id')->get();
        foreach ($rows as $row) {
            if (!$row->addon) {
                continue;
            }
            $qty  = $row->quantity * $months;
            $unit = $row->addon->unitCents();
            $lines[] = [
                'type'        => 'addon',
                'code'        => $row->addon->code,
                'name'        => $row->addon->name . ($months > 1 ? " ({$row->quantity} × 12 meses)" : ''),
                'product_id'  => $row->addon->abacatepay_product_id ?: null,
                'quantity'    => $qty,
                'unit_cents'  => $unit,
                'total_cents' => $unit * $qty,
            ];
        }
        return $lines;
    }

    private function notifyChange(Tenant $tenant, BillingAddon $addon, int $from, int $to, ?User $actor): void
    {
        Log::info('Adicional alterado', ['tenant_id' => $tenant->id, 'addon' => $addon->code, 'de' => $from, 'para' => $to, 'user_id' => $actor?->id]);
        try {
            $acao = $to > $from ? 'contratou' : ($to === 0 ? 'cancelou' : 'reduziu');
            foreach (User::where('role', 'super_admin')->pluck('id') as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'title'   => 'Adicional ' . $acao,
                    'message' => "{$tenant->name} {$acao}: {$addon->name} — de {$from} para {$to}.",
                    'type'    => 'info',
                    'link'    => '/admin/adicionais',
                    'read_at' => null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Adicional: falha ao avisar admins', ['error' => $e->getMessage()]);
        }
    }
}
