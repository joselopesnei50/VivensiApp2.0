<?php

use App\Mail\SubscriptionCanceledMail;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Support\Facades\Mail;

/*
| Cancelamento pelo cliente: sem multa, acesso até o fim do período pago, sem
| novas faturas; em até 7 dias, devolução integral e acesso encerrado na hora.
*/

beforeEach(function () {
    Mail::fake();
    $this->plan = SubscriptionPlan::forceCreate(['name' => 'Avante', 'price' => 349.90, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'ngo']);
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'plan_id' => $this->plan->id, 'subscription_status' => 'active', 'name' => 'Instituto Ipê']);
    $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo', 'status' => 'active', 'email' => 'adm' . uniqid() . '@example.com']);
    $this->as = function (User $u) { $this->flushSession(); app('auth')->forgetGuards(); return $this->actingAs($u); };
    $this->paidFor = function (string $start, string $end, string $paidAt) {
        Invoice::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id, 'amount_cents' => 34990,
            'description' => 'x', 'status' => Invoice::STATUS_PAID, 'period_start' => $start, 'period_end' => $end, 'due_date' => $start, 'paid_at' => $paidAt]);
    };
});

it('depois de 7 dias: acesso até o fim do período pago, sem novas faturas, e dá para desistir', function () {
    ($this->paidFor)(now()->subDays(40)->toDateString(), now()->subDays(11)->toDateString(), now()->subDays(40));
    ($this->paidFor)(now()->subDays(10)->toDateString(), now()->addDays(19)->toDateString(), now()->subDays(10));
    $open = Invoice::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id, 'amount_cents' => 34990,
        'description' => 'próximo', 'status' => Invoice::STATUS_OPEN, 'period_start' => now()->addDays(20)->toDateString(),
        'period_end' => now()->addDays(49)->toDateString(), 'due_date' => now()->addDays(20)->toDateString()]);

    ($this->as)($this->admin)->get('/minha-conta/faturas')->assertOk()->assertSee('Cancelar assinatura')->assertSee(now()->addDays(19)->format('d/m/Y'));

    ($this->as)($this->admin)->post('/minha-conta/assinatura/cancelar', ['reason' => 'Vou usar outro sistema', 'confirm' => 1])
        ->assertRedirect(route('client.invoices.index'))->assertSessionHas('success');

    $t = $this->tenant->fresh();
    expect($t->subscription_status)->toBe('active')->and($t->access_until->toDateString())->toBe(now()->addDays(19)->toDateString())
        ->and($t->cancel_reason)->toBe('Vou usar outro sistema')
        ->and($open->fresh()->status)->toBe(Invoice::STATUS_CANCELED);
    expect(app(InvoiceService::class)->generateForTenant($t))->toBeNull();
    Mail::assertSent(SubscriptionCanceledMail::class, fn ($m) => $m->hasTo($this->admin->email) && !$m->refund);

    ($this->as)($this->admin)->get('/minha-conta/faturas')->assertSee('Cancelamento agendado')->assertSee('Manter minha assinatura');
    ($this->as)($this->admin)->post('/minha-conta/assinatura/manter')->assertSessionHas('success');
    expect($this->tenant->fresh()->cancel_requested_at)->toBeNull();
});

it('em até 7 dias da contratação: devolução integral e acesso encerrado na hora', function () {
    Transaction::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'amount' => 349.90, 'description' => 'Assinatura', 'type' => 'income',
        'status' => 'paid', 'date' => now()->subDays(3)->toDateString(), 'external_id' => 'VIVENSI_' . $this->tenant->id . '_1_abc', 'paid_at' => now()->subDays(3)]);

    ($this->as)($this->admin)->get('/minha-conta/faturas')->assertSee('devolução integral', false);
    ($this->as)($this->admin)->post('/minha-conta/assinatura/cancelar', ['confirm' => 1])->assertRedirect(route('login'));

    $t = $this->tenant->fresh();
    expect($t->subscription_status)->toBe('canceled')->and($t->canceled_at)->not->toBeNull();
    Mail::assertSent(SubscriptionCanceledMail::class, fn ($m) => $m->refund);
});

it('sem a confirmação não cancela; equipe não cancela', function () {
    ($this->as)($this->admin)->post('/minha-conta/assinatura/cancelar', [])->assertSessionHasErrors('confirm');
    expect($this->tenant->fresh()->cancel_requested_at)->toBeNull();

    $coord = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'manager', 'status' => 'active', 'email' => 'c' . uniqid() . '@example.com']);
    ($this->as)($coord)->post('/minha-conta/assinatura/cancelar', ['confirm' => 1])->assertForbidden();
});

it('a rotina diária encerra o acesso no fim do período e pagar de novo reativa', function () {
    $this->tenant->forceFill(['cancel_requested_at' => now()->subDays(20), 'access_until' => now()->subDay()->toDateString()])->save();
    $this->artisan('subscriptions:finish-canceled')->assertSuccessful();

    $t = $this->tenant->fresh();
    expect($t->subscription_status)->toBe('canceled')->and($t->canceled_at)->not->toBeNull();

    $t->update(['subscription_status' => 'active']);
    expect($t->fresh()->cancel_requested_at)->toBeNull()->and($t->fresh()->access_until)->toBeNull();
});
