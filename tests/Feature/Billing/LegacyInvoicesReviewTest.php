<?php

use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/*
| Faturas antigas: geradas antes do contrato ou para contas cortesia/teste não
| valem; o 1º pagamento depois do contrato tem de virar uma fatura paga.
*/

beforeEach(function () {
    Mail::fake();
    Cache::flush();
    Carbon::setTestNow('2026-11-19 10:00:00');
    Http::fake(['*checkouts/create*' => Http::response(['success' => true, 'data' => ['id' => 'bill_x', 'url' => 'https://app.abacatepay.com/pay/bill_x']])]);

    $this->paid = SubscriptionPlan::forceCreate([
        'name' => 'Avante', 'price' => 349.90, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'ngo',
    ]);
    $this->free = SubscriptionPlan::forceCreate([
        'name' => 'Cortesia', 'price' => 0, 'is_active' => true, 'is_courtesy' => true, 'target_audience' => 'ngo',
    ]);
    $this->svc = app(InvoiceService::class);
    $this->inv = fn (Tenant $t, array $a) => Invoice::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => $t->id, 'plan_id' => $t->plan_id, 'amount_cents' => 49790,
        'description' => 'Assinatura Plano Terceiro Setor — November/2026', 'status' => 'overdue',
    ], $a));
    // Cliente que assinou ontem, com faturas antigas geradas antes do contrato.
    $this->cliente = Tenant::factory()->create([
        'type' => 'ngo', 'plan_id' => $this->paid->id, 'subscription_status' => 'active',
        'contract_signed_at' => '2026-11-18 14:20:00',
    ]);
});

afterEach(fn () => Carbon::setTestNow());

it('1º pagamento após o contrato cancela as faturas antigas e cria a fatura paga', function () {
    $antiga = ($this->inv)($this->cliente, ['period_start' => '2026-11-01', 'period_end' => '2026-11-30', 'due_date' => '2026-11-06']);

    $nova = $this->svc->recordInitialPayment($this->cliente, $this->paid, 349.90, 'bill_1', Carbon::parse('2026-11-18 14:30'));

    expect($antiga->fresh()->status)->toBe(Invoice::STATUS_CANCELED);
    expect($nova->status)->toBe(Invoice::STATUS_PAID);
    expect($nova->amount_cents)->toBe(34990);
    expect($nova->period_start->toDateString())->toBe('2026-11-18');
    expect($nova->period_end->toDateString())->toBe('2026-12-17');
});

it('fatura do ciclo atual em aberto (depois do contrato) é a que fica paga', function () {
    $atual = ($this->inv)($this->cliente, [
        'status' => 'open', 'amount_cents' => 34990,
        'period_start' => '2026-11-19', 'period_end' => '2026-12-18', 'due_date' => '2026-11-24',
    ]);

    $paga = $this->svc->recordInitialPayment($this->cliente, $this->paid, 349.90, 'bill_2');

    expect($paga->id)->toBe($atual->id);
    expect($atual->fresh()->status)->toBe(Invoice::STATUS_PAID);
    expect(Invoice::withoutGlobalScopes()->where('tenant_id', $this->cliente->id)->count())->toBe(1);
});

it('invoices:revisar só mostra; com --aplicar corrige cortesia, pré-contrato e pagamento sem fatura', function () {
    $teste = Tenant::factory()->create(['type' => 'ngo', 'plan_id' => $this->free->id, 'subscription_status' => 'active']);
    $daTeste = ($this->inv)($teste, ['period_start' => '2026-10-01', 'period_end' => '2026-10-31', 'due_date' => '2026-10-06']);
    $antiga  = ($this->inv)($this->cliente, ['period_start' => '2026-11-01', 'period_end' => '2026-11-30', 'due_date' => '2026-11-06']);
    Transaction::withoutGlobalScopes()->create([
        'tenant_id' => $this->cliente->id, 'plan_id' => $this->paid->id, 'amount' => 349.90, 'description' => 'Assinatura',
        'type' => 'income', 'status' => 'paid', 'date' => '2026-11-18', 'paid_at' => '2026-11-18 14:30:00',
        'external_id' => 'VIVENSI_' . $this->cliente->id . '_1_abc',
    ]);

    $this->artisan('invoices:revisar')->assertSuccessful();
    expect($daTeste->fresh()->status)->toBe('overdue');
    expect($antiga->fresh()->status)->toBe('overdue');

    $this->artisan('invoices:revisar --aplicar')->assertSuccessful();
    expect($daTeste->fresh()->status)->toBe(Invoice::STATUS_CANCELED);
    expect($antiga->fresh()->status)->toBe(Invoice::STATUS_CANCELED);
    $paga = Invoice::withoutGlobalScopes()->where('tenant_id', $this->cliente->id)->where('status', 'paid')->first();
    expect($paga)->not->toBeNull();
    expect($paga->amount_cents)->toBe(34990);
    expect($paga->period_start->toDateString())->toBe('2026-11-18');

    // Rodar de novo não duplica nada.
    $this->artisan('invoices:revisar --aplicar')->assertSuccessful();
    expect(Invoice::withoutGlobalScopes()->where('tenant_id', $this->cliente->id)->where('status', 'paid')->count())->toBe(1);
});

it('tela de faturas: sem canceladas por padrão e totais só de planos pagos', function () {
    $teste = Tenant::factory()->create(['type' => 'ngo', 'plan_id' => $this->free->id, 'subscription_status' => 'active']);
    ($this->inv)($teste, ['period_start' => '2026-10-01', 'period_end' => '2026-10-31', 'due_date' => '2026-10-06', 'amount_cents' => 99900]);
    ($this->inv)($this->cliente, ['status' => 'canceled', 'period_start' => '2026-09-01', 'period_end' => '2026-09-30', 'due_date' => '2026-09-06', 'description' => 'Cancelada antiga']);
    ($this->inv)($this->cliente, ['status' => 'open', 'amount_cents' => 34990, 'period_start' => '2026-12-18', 'period_end' => '2027-01-17', 'due_date' => '2026-12-18']);

    $own = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin = User::factory()->create(['tenant_id' => $own->id, 'role' => 'super_admin']);

    $resp = $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)->get(route('admin.invoices.index'));

    $resp->assertOk()
        ->assertDontSee('Cancelada antiga')
        ->assertSee('Novembro/2026')
        ->assertSee('Cortesia / teste');
    $stats = $resp->viewData('stats');
    expect($stats['sum_open_brl'])->toEqual(349.90);
    expect($stats['total_overdue'])->toBe(0);
});

it('mudar a organização para plano cortesia cancela as faturas em aberto', function () {
    $aberta = ($this->inv)($this->cliente, ['status' => 'open', 'period_start' => '2026-12-18', 'period_end' => '2027-01-17', 'due_date' => '2026-12-18']);
    $own = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin = User::factory()->create(['tenant_id' => $own->id, 'role' => 'super_admin']);

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)
        ->post(route('admin.tenants.change_plan', $this->cliente->id), ['plan_id' => $this->free->id]);

    expect($aberta->fresh()->status)->toBe(Invoice::STATUS_CANCELED);
});
