<?php

use App\Mail\InvoiceNoticeMail;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AbacatePayService;
use App\Services\Billing\InvoiceNotifier;
use App\Services\Billing\InvoiceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/*
| Cobrança recorrente: ciclo pela data da assinatura, e-mails, bloqueio após
| 5 dias de atraso e pagamento pela tela de faturas.
*/

beforeEach(function () {
    Mail::fake();
    Cache::flush();
    Carbon::setTestNow('2026-11-20 10:00:00');
    Http::fake(['*checkouts/create*' => Http::response(['success' => true, 'data' => ['id' => 'bill_inv', 'url' => 'https://app.abacatepay.com/pay/bill_inv']])]);

    $this->plan = SubscriptionPlan::forceCreate([
        'name' => 'Plano Pro', 'price' => 100, 'is_active' => true, 'is_courtesy' => false,
        'target_audience' => 'ngo', 'abacatepay_product_id' => 'prod_x',
    ]);
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'plan_id' => $this->plan->id, 'subscription_status' => 'active']);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id, 'role' => 'ngo', 'status' => 'active', 'email' => 'f_' . uniqid() . '@example.com',
    ]);
    $this->svc = app(InvoiceService::class);
    $this->inv = fn (array $a) => Invoice::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id, 'amount_cents' => 10000,
        'description' => 'Assinatura', 'status' => 'open',
    ], $a));
});

afterEach(fn () => Carbon::setTestNow());

it('o 1º pagamento vira a fatura paga do 1º ciclo, contado da data da assinatura', function () {
    $i = $this->svc->recordInitialPayment($this->tenant, $this->plan, 100.0, 'bill_1');

    expect($i->status)->toBe('paid')
        ->and($i->period_start->toDateString())->toBe('2026-11-20')
        ->and($i->period_end->toDateString())->toBe('2026-12-19');

    // chamar de novo não duplica
    $this->svc->recordInitialPayment($this->tenant, $this->plan, 100.0, 'bill_1');
    expect(Invoice::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('quem assinou no fim do mês não recebe outra fatura no dia 1º', function () {
    Carbon::setTestNow('2026-11-28 10:00:00');
    $this->svc->recordInitialPayment($this->tenant, $this->plan, 100.0, 'bill_1');

    Carbon::setTestNow('2026-12-01 00:10:00');
    expect($this->svc->generateForTenant($this->tenant))->toBeNull();
    expect(Invoice::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('a próxima fatura sai 5 dias antes do aniversário e vence no aniversário', function () {
    $this->svc->recordInitialPayment($this->tenant, $this->plan, 100.0, 'bill_1'); // 20/11 a 19/12

    Carbon::setTestNow('2026-12-14 00:10:00');
    expect($this->svc->generateForTenant($this->tenant))->toBeNull(); // 6 dias antes: ainda não

    Carbon::setTestNow('2026-12-15 00:10:00');
    $next = $this->svc->generateForTenant($this->tenant);
    expect($next)->not->toBeNull()
        ->and($next->period_start->toDateString())->toBe('2026-12-20')
        ->and($next->period_end->toDateString())->toBe('2027-01-19')
        ->and($next->due_date->toDateString())->toBe('2026-12-20')
        ->and($next->abacatepay_billing_url)->toContain('abacatepay');

    // rodar de novo no mesmo dia não duplica
    $this->svc->generateForTenant($this->tenant);
    expect(Invoice::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(2);
});

it('conta ativa pelo checkout antes desta mudança: 1ª fatura um mês após o pagamento', function () {
    Transaction::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id, 'amount' => 100, 'description' => 'x',
        'type' => 'income', 'status' => 'paid', 'date' => '2026-11-10', 'paid_at' => '2026-11-10 02:35:00',
        'external_id' => 'VIVENSI_' . $this->tenant->id . '_1_abc',
    ]);
    expect($this->svc->nextPeriodStart($this->tenant)->toDateString())->toBe('2026-12-10');
});

it('e-mails: nova, véspera, vencida e bloqueio saem uma vez cada', function () {
    $nova = ($this->inv)(['period_start' => '2026-11-25', 'period_end' => '2026-12-24', 'due_date' => '2026-11-25']);
    $vespera = ($this->inv)(['period_start' => '2026-10-21', 'period_end' => '2026-11-20', 'due_date' => '2026-11-21']);
    $vencida = ($this->inv)(['period_start' => '2026-10-18', 'period_end' => '2026-11-17', 'due_date' => '2026-11-18']);
    $bloq = ($this->inv)(['period_start' => '2026-10-15', 'period_end' => '2026-11-14', 'due_date' => '2026-11-15']);

    $c = app(InvoiceNotifier::class)->runDaily();
    expect($c)->toBe(['nova' => 1, 'lembrete' => 1, 'vencida' => 1, 'bloqueio' => 1]);
    Mail::assertSent(InvoiceNoticeMail::class, 4);

    app(InvoiceNotifier::class)->runDaily();
    Mail::assertSent(InvoiceNoticeMail::class, 4); // não repete
    expect($bloq->fresh()->notifications_sent)->toHaveKey('bloqueio');
});

it('faturas antigas (antes da data de início das regras) não geram e-mail nem bloqueio', function () {
    ($this->inv)(['period_start' => '2026-09-01', 'period_end' => '2026-09-30', 'due_date' => '2026-09-06', 'status' => 'overdue']);

    expect(app(InvoiceNotifier::class)->runDaily())->toBe(['nova' => 0, 'lembrete' => 0, 'vencida' => 0, 'bloqueio' => 0])
        ->and($this->svc->isBlocked($this->tenant))->toBeFalse();
});

it('com 5 dias de atraso o painel só abre a tela de faturas; pagando, libera', function () {
    $inv = ($this->inv)(['period_start' => '2026-10-15', 'period_end' => '2026-11-14', 'due_date' => '2026-11-15', 'status' => 'overdue']);

    $this->actingAs($this->user)->get('/dashboard')->assertRedirect(route('client.invoices.index'));
    $this->actingAs($this->user)->get('/minha-conta/faturas')->assertOk();

    $this->svc->markAsPaid($inv, Invoice::PAID_VIA_MANUAL_ADMIN);
    expect($this->svc->isBlocked($this->tenant))->toBeFalse();
    Mail::assertSent(InvoiceNoticeMail::class, fn ($m) => $m->kind === 'paga');
});

it('4 dias de atraso ainda não bloqueia', function () {
    ($this->inv)(['period_start' => '2026-10-16', 'period_end' => '2026-11-15', 'due_date' => '2026-11-16', 'status' => 'overdue']);
    expect($this->svc->isBlocked($this->tenant))->toBeFalse();
});

it('botão Pagar gera link novo e leva para a AbacatePay', function () {
    $inv = ($this->inv)(['period_start' => '2026-11-20', 'period_end' => '2026-12-19', 'due_date' => '2026-11-20',
        'abacatepay_billing_url' => 'https://app.abacatepay.com/pay/expirado', 'abacatepay_charge_id' => 'bill_old']);

    $this->actingAs($this->user)->post(route('client.invoices.pay', $inv->id))
        ->assertRedirect('https://app.abacatepay.com/pay/bill_inv');
    expect($inv->fresh()->abacatepay_charge_id)->toBe('bill_inv');
});

it('a tela de faturas confere na AbacatePay e dá baixa se já foi paga', function () {
    Http::fake(['*checkouts/get*' => Http::response(['success' => true, 'data' => ['id' => 'bill_x', 'status' => 'PAID']])]);
    $inv = ($this->inv)(['period_start' => '2026-11-20', 'period_end' => '2026-12-19', 'due_date' => '2026-11-20', 'abacatepay_charge_id' => 'bill_x']);

    $this->actingAs($this->user)->get('/minha-conta/faturas')->assertOk();
    expect($inv->fresh()->status)->toBe('paid');
});

it('não dá para pagar fatura de outra organização', function () {
    $other = Tenant::factory()->create(['type' => 'ngo', 'plan_id' => $this->plan->id, 'subscription_status' => 'active']);
    $inv = Invoice::withoutGlobalScopes()->create(['tenant_id' => $other->id, 'plan_id' => $this->plan->id, 'amount_cents' => 100,
        'description' => 'x', 'status' => 'open', 'period_start' => '2026-11-20', 'period_end' => '2026-12-19', 'due_date' => '2026-11-20']);

    $this->actingAs($this->user)->post(route('client.invoices.pay', $inv->id))->assertNotFound();
});

it('vencida a partir do dia seguinte ao vencimento', function () {
    $hoje = ($this->inv)(['period_start' => '2026-10-21', 'period_end' => '2026-11-19', 'due_date' => '2026-11-20']);
    $ontem = ($this->inv)(['period_start' => '2026-10-20', 'period_end' => '2026-11-18', 'due_date' => '2026-11-19']);

    $this->svc->markOverdue();
    expect($hoje->fresh()->status)->toBe('open')->and($ontem->fresh()->status)->toBe('overdue');
});
