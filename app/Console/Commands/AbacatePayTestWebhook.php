<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAbacatePayWebhook;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Console\Command;

/**
 * Simula um webhook AbacatePay disparando ProcessAbacatePayWebhook com payload
 * sintético. 3 casos de uso:
 *
 *  1. Dev local: testa pipeline sem ngrok + sandbox AbacatePay
 *  2. Prod: reprocessa webhook perdido quando reconcile não cobre (ex:
 *     subscription.cancelled que não tem Transaction pendente associada)
 *  3. Smoke test pós-deploy: confirma que handler está vivo, audit log
 *     funcionando, ativação de tenant OK
 *
 * Exemplos:
 *   php artisan abacatepay:test-webhook --transaction=142
 *   php artisan abacatepay:test-webhook --event=subscription.cancelled --tenant=5
 *   php artisan abacatepay:test-webhook --event=checkout.completed --transaction=142 --sync --dry-run
 */
class AbacatePayTestWebhook extends Command
{
    protected $signature = 'abacatepay:test-webhook
        {--event=checkout.completed : evento a simular (checkout.completed|checkout.refunded|subscription.completed|subscription.renewed|subscription.cancelled)}
        {--transaction=             : ID local da Transaction (preenche external_id, tenant e plan automaticamente)}
        {--external-id=             : sobrescreve external_id manualmente (em vez de usar --transaction)}
        {--tenant=                  : ID do tenant (preenche owner+email se --transaction não vier)}
        {--plan=                    : ID do SubscriptionPlan (sobrescreve do tenant atual)}
        {--email=                   : email do customer (sobrescreve do owner)}
        {--dry-run                  : só mostra o payload, sem disparar o job}
        {--sync                     : dispatchSync (sem queue) — bom pra ver erros imediatamente}';

    protected $description = 'Simula um webhook AbacatePay disparando ProcessAbacatePayWebhook com payload sintético';

    private const VALID_EVENTS = [
        'checkout.completed',
        'checkout.refunded',
        'subscription.completed',
        'subscription.renewed',
        'subscription.cancelled',
    ];

    public function handle(): int
    {
        $event = (string) $this->option('event');
        if (!in_array($event, self::VALID_EVENTS, true)) {
            $this->error("Evento inválido: {$event}. Use um destes: " . implode(', ', self::VALID_EVENTS));
            return self::FAILURE;
        }

        $context = $this->buildContext();
        if (!$context) {
            return self::FAILURE;
        }

        $payload = $this->buildPayloadForEvent($event, $context);

        $this->info("== Evento simulado: {$event} ==");
        $this->line('webhook_id:  ' . $payload['id']);
        $this->line('tenant_id:   ' . ($context['tenant']->id ?? '—'));
        $this->line('external_id: ' . ($context['externalId'] ?? '—'));
        $this->line('plan_id:     ' . ($context['planId'] ?? '—'));
        $this->line('email:       ' . ($context['email'] ?? '—'));
        $this->newLine();
        $this->line('Payload completo:');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] Job não disparado.');
            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $this->info('Dispatch síncrono — qualquer exception aparece aqui:');
            ProcessAbacatePayWebhook::dispatchSync($event, $payload);
            $this->info('✅ Handler executou sem exception.');
        } else {
            ProcessAbacatePayWebhook::dispatch($event, $payload);
            $this->info('✅ Job enfileirado na queue "default". Veja o resultado no Horizon ou nos logs.');
        }

        return self::SUCCESS;
    }

    /**
     * Resolve contexto (tenant, externalId, planId, email) a partir das flags.
     * Devolve null se não consegue determinar.
     *
     * @return array{tenant: \App\Models\Tenant, externalId: string, planId: ?int, email: ?string}|null
     */
    private function buildContext(): ?array
    {
        $txId = $this->option('transaction');
        $extId = $this->option('external-id');
        $tenantId = $this->option('tenant');
        $planId = $this->option('plan');
        $email = $this->option('email');

        $tenant = null;

        if ($txId) {
            $tx = Transaction::withoutGlobalScopes()->find((int) $txId);
            if (!$tx) {
                $this->error("Transaction #{$txId} não encontrada.");
                return null;
            }
            $extId = $extId ?: $tx->external_id;
            $planId = $planId ?: $tx->plan_id;
            $tenant = Tenant::find($tx->tenant_id);
            if (!$tenant) {
                $this->error("Tenant da Transaction #{$txId} não encontrado.");
                return null;
            }
        } elseif ($tenantId) {
            $tenant = Tenant::find((int) $tenantId);
            if (!$tenant) {
                $this->error("Tenant #{$tenantId} não encontrado.");
                return null;
            }
            $extId = $extId ?: ('VIVENSI_' . $tenant->id . '_' . time());
        } else {
            $this->error('Precisa de --transaction=ID ou --tenant=ID (ou ambos) pra resolver o contexto.');
            return null;
        }

        if (!$email) {
            $owner = $tenant->owner ?? null;
            $email = $owner->email ?? 'teste@vivensi.app.br';
        }

        // Valida planId se foi passado
        if ($planId && !SubscriptionPlan::find((int) $planId)) {
            $this->warn("SubscriptionPlan #{$planId} não existe — payload vai disparar mesmo assim.");
        }

        return [
            'tenant'     => $tenant,
            'externalId' => $extId,
            'planId'     => $planId ? (int) $planId : null,
            'email'      => $email,
        ];
    }

    /**
     * Monta payload realista por tipo de evento. Mimica o que a AbacatePay
     * envia no webhook real (data.checkout vs data.subscription + data.customer).
     */
    private function buildPayloadForEvent(string $event, array $ctx): array
    {
        $webhookId = 'test_' . uniqid();
        $base = [
            'id'        => $webhookId,
            'event'     => $event,
            'devMode'   => true,
            'timestamp' => now()->toIso8601String(),
        ];

        $checkoutBlock = [
            'id'         => 'chk_test_' . uniqid(),
            'externalId' => $ctx['externalId'],
            'status'     => 'PAID',
            'amount'     => 9990,
            'metadata'   => [
                'tenant_id' => $ctx['tenant']->id,
                'plan_id'   => $ctx['planId'],
            ],
        ];

        $customerBlock = [
            'id'       => 'cust_test_' . uniqid(),
            'email'    => $ctx['email'],
            'name'     => $ctx['tenant']->name ?? 'Tenant teste',
            'metadata' => ['tenant_id' => $ctx['tenant']->id],
        ];

        $subscriptionBlock = [
            'id'         => 'sub_test_' . uniqid(),
            'externalId' => $ctx['externalId'],
            'status'     => str_starts_with($event, 'subscription.cancel') ? 'CANCELLED' : 'ACTIVE',
            'metadata'   => [
                'tenant_id' => $ctx['tenant']->id,
                'plan_id'   => $ctx['planId'],
            ],
        ];

        return match (true) {
            str_starts_with($event, 'checkout.') => $base + [
                'data' => ['checkout' => $checkoutBlock],
            ],
            str_starts_with($event, 'subscription.') => $base + [
                'data' => [
                    'subscription' => $subscriptionBlock,
                    'customer'     => $customerBlock,
                ],
            ],
            default => $base + ['data' => []],
        };
    }
}
