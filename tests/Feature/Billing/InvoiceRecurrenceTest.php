<?php

use App\Console\Commands\AbacatePayReconcile;
use App\Jobs\ProcessAbacatePayWebhook;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\AbacatePayService;
use App\Services\Billing\InvoiceService;
use Illuminate\Support\Facades\Queue;

/**
 * Regressão do religamento de faturas recorrentes (2026-08-04):
 *  - InvoiceService.tryGenerateAbacateCheckout usa createCheckout (PIX-only)
 *    com externalId invoice_<id>_<ts>
 *  - abacatepay:reconcile varre invoices open/overdue com charge_id setado
 *  - ProcessAbacatePayWebhook resolve tenant a partir do prefixo invoice_
 */

// ── Helpers ───────────────────────────────────────────────────────────────────

function irtPaidPlan(array $overrides = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name'                  => 'Plano Teste',
        'target_audience'       => 'ngo',
        'price'                 => 129.90,
        'is_active'             => true,
        'is_courtesy'           => false,
        'abacatepay_product_id' => 'prod_test_123',
    ], $overrides));
}

function irtActiveTenant(SubscriptionPlan $plan): Tenant
{
    return Tenant::factory()->create([
        'subscription_status' => 'active',
        'plan_id'             => $plan->id,
    ]);
}

// ── InvoiceService: cria invoice + checkout hospedado ────────────────────────

it('gera invoice e salva billing_url + charge_id via createCheckout', function () {
    $plan   = irtPaidPlan();
    $tenant = irtActiveTenant($plan);

    $svc = Mockery::mock(AbacatePayService::class);
    $svc->shouldReceive('createCheckout')
        ->once()
        ->withArgs(function (
            array $items,
            string $externalId,
            string $returnUrl,
            string $completionUrl,
            array $methods,
            array $metadata
        ) use ($plan, $tenant) {
            expect($items)->toBe([['id' => 'prod_test_123', 'quantity' => 1]]);
            expect($externalId)->toStartWith('invoice_');
            expect($methods)->toBe(['PIX']);
            expect($metadata['tenant_id'])->toBe($tenant->id);
            expect($metadata['plan_id'])->toBe($plan->id);
            return true;
        })
        ->andReturn([
            'id'  => 'chk_abcdef',
            'url' => 'https://pay.abacate.io/chk_abcdef',
        ]);
    app()->instance(AbacatePayService::class, $svc);

    $invoice = app(InvoiceService::class)->generateForTenant($tenant->fresh('plan'));

    expect($invoice)->not->toBeNull();
    expect($invoice->status)->toBe(Invoice::STATUS_OPEN);
    expect($invoice->abacatepay_charge_id)->toBe('chk_abcdef');
    expect($invoice->abacatepay_billing_url)->toBe('https://pay.abacate.io/chk_abcdef');
});

it('não chama createCheckout quando plano não tem abacatepay_product_id', function () {
    $plan   = irtPaidPlan(['abacatepay_product_id' => null]);
    $tenant = irtActiveTenant($plan);

    $svc = Mockery::mock(AbacatePayService::class);
    $svc->shouldNotReceive('createCheckout');
    app()->instance(AbacatePayService::class, $svc);

    $invoice = app(InvoiceService::class)->generateForTenant($tenant->fresh('plan'));

    expect($invoice)->not->toBeNull();
    expect($invoice->abacatepay_charge_id)->toBeNull();
    expect($invoice->abacatepay_billing_url)->toBeNull();
});

it('mantém invoice mesmo se createCheckout falhar (não bloqueante)', function () {
    $plan   = irtPaidPlan();
    $tenant = irtActiveTenant($plan);

    $svc = Mockery::mock(AbacatePayService::class);
    $svc->shouldReceive('createCheckout')->once()->andReturn(null);
    app()->instance(AbacatePayService::class, $svc);

    $invoice = app(InvoiceService::class)->generateForTenant($tenant->fresh('plan'));

    expect($invoice)->not->toBeNull();
    expect($invoice->status)->toBe(Invoice::STATUS_OPEN);
    expect($invoice->abacatepay_charge_id)->toBeNull();
});


// ── Webhook: resolve tenant a partir de invoice_<id> ─────────────────────────

it('marca invoice como paga quando webhook checkout.completed chega com externalId invoice_', function () {
    $plan    = irtPaidPlan();
    $tenant  = irtActiveTenant($plan);
    $invoice = Invoice::create([
        'tenant_id'    => $tenant->id,
        'plan_id'      => $plan->id,
        'amount_cents' => 12990,
        'description'  => 'Teste',
        'status'       => Invoice::STATUS_OPEN,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end'   => now()->endOfMonth()->toDateString(),
        'due_date'     => now()->addDays(5)->toDateString(),
        'abacatepay_charge_id' => 'chk_wh_1',
    ]);

    (new ProcessAbacatePayWebhook('checkout.completed', [
        'id'   => 'wh_' . uniqid(),
        'data' => ['checkout' => [
            'id'         => 'chk_wh_1',
            'externalId' => 'invoice_' . $invoice->id . '_' . time(),
            'status'     => 'PAID',
        ]],
    ]))->handle();

    $invoice->refresh();
    expect($invoice->status)->toBe(Invoice::STATUS_PAID);
    expect($invoice->paid_via)->toBe(Invoice::PAID_VIA_ABACATEPAY);
    expect($invoice->abacatepay_charge_id)->toBe('chk_wh_1');
});

// ── Reconcile: cobre invoices abertas com charge_id ──────────────────────────

it('abacatepay:reconcile dispara job sintético quando checkout de invoice está PAGO', function () {
    Queue::fake();

    $plan    = irtPaidPlan();
    $tenant  = irtActiveTenant($plan);
    $invoice = Invoice::create([
        'tenant_id'    => $tenant->id,
        'plan_id'      => $plan->id,
        'amount_cents' => 12990,
        'description'  => 'Teste',
        'status'       => Invoice::STATUS_OPEN,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end'   => now()->endOfMonth()->toDateString(),
        'due_date'     => now()->addDays(5)->toDateString(),
        'abacatepay_charge_id' => 'chk_reconcile_1',
    ]);
    // Eloquent sobrescreve created_at no create() — força pra bater o cutoff de 5 min.
    Invoice::withoutGlobalScopes()->where('id', $invoice->id)->update(['created_at' => now()->subMinutes(10)]);

    $svc = Mockery::mock(AbacatePayService::class);
    $svc->shouldReceive('getCheckout')
        ->with('chk_reconcile_1')
        ->andReturn([
            'id'         => 'chk_reconcile_1',
            'status'     => 'PAID',
            'externalId' => 'invoice_' . $invoice->id . '_1',
        ]);
    app()->instance(AbacatePayService::class, $svc);

    $this->artisan('abacatepay:reconcile --minutes-old=5')
        ->expectsOutputToContain('Invoices abertas com checkout AbacatePay')
        ->assertSuccessful();

    Queue::assertPushed(ProcessAbacatePayWebhook::class);
});

it('abacatepay:reconcile ignora invoice paga', function () {
    Queue::fake();

    $plan    = irtPaidPlan();
    $tenant  = irtActiveTenant($plan);
    $paid = Invoice::create([
        'tenant_id'    => $tenant->id,
        'plan_id'      => $plan->id,
        'amount_cents' => 12990,
        'description'  => 'Teste',
        'status'       => Invoice::STATUS_PAID,
        'paid_at'      => now(),
        'paid_via'     => Invoice::PAID_VIA_ABACATEPAY,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end'   => now()->endOfMonth()->toDateString(),
        'due_date'     => now()->addDays(5)->toDateString(),
        'abacatepay_charge_id' => 'chk_already_paid',
    ]);
    Invoice::withoutGlobalScopes()->where('id', $paid->id)->update(['created_at' => now()->subMinutes(10)]);

    $svc = Mockery::mock(AbacatePayService::class);
    $svc->shouldNotReceive('getCheckout');
    app()->instance(AbacatePayService::class, $svc);

    $this->artisan('abacatepay:reconcile --minutes-old=5')->assertSuccessful();
    Queue::assertNotPushed(ProcessAbacatePayWebhook::class);
});
