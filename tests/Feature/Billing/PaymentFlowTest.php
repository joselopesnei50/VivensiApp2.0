<?php

use App\Mail\AdesaoSignedMail;
use App\Mail\SubscriptionActivatedMail;
use App\Models\Contract;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/*
| Fluxo completo de pagamento: contrato assinado → checkout → PIX pago →
| webhook (ou consulta de status) libera a conta → e-mails.
*/

beforeEach(function () {
    Mail::fake();
    $this->secret = 'abacate-secret-test';
    SystemSetting::setValue('abacatepay_webhook_secret', $this->secret);
    config(['services.abacatepay.hmac_key' => null]);

    $this->plan = SubscriptionPlan::forceCreate([
        'name' => 'Plano Pro', 'price' => 149.90, 'is_active' => true, 'is_courtesy' => false,
        'target_audience' => 'ngo', 'abacatepay_product_id' => 'prod_test',
    ]);
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'plan_id' => $this->plan->id, 'subscription_status' => 'pending', 'document' => '12345678000199']);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id, 'role' => 'ngo', 'status' => 'active',
        'email' => 'dono_' . uniqid() . '@example.com', 'name' => 'Ana Dona',
    ]);
    $this->externalId = 'VIVENSI_' . $this->tenant->id . '_' . time() . '_abc123';
    $this->tx = Transaction::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id, 'amount' => 149.90,
        'description' => 'Assinatura', 'type' => 'income', 'status' => 'pending',
        'date' => now()->toDateString(), 'external_id' => $this->externalId, 'gateway_id' => 'bill_test1',
    ]);
    $this->payload = fn (string $id = 'evt_1') => [
        'id' => $id, 'event' => 'checkout.completed', 'devMode' => false,
        'data' => ['checkout' => ['id' => 'bill_test1', 'status' => 'PAID', 'externalId' => $this->externalId,
            'metadata' => ['tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id]]],
    ];
});

it('webhook com assinatura e sem chave HMAC configurada não dá mais 500 e libera a conta', function () {
    config(['queue.default' => 'sync']);
    $this->postJson('/api/abacatepay/webhook', ($this->payload)(), [
        'X-Webhook-Secret' => $this->secret, 'X-Webhook-Signature' => 'qualquer',
    ])->assertOk();

    expect($this->tenant->fresh()->subscription_status)->toBe('active')
        ->and($this->tx->fresh()->status)->toBe('paid');
    Mail::assertSent(SubscriptionActivatedMail::class, fn ($m) => $m->hasTo($this->user->email));
});

it('webhook repetido não manda o e-mail de novo', function () {
    config(['queue.default' => 'sync']);
    $this->postJson('/api/abacatepay/webhook', ($this->payload)('evt_1'), ['X-Webhook-Secret' => $this->secret])->assertOk();
    $this->postJson('/api/abacatepay/webhook', ($this->payload)('evt_2'), ['X-Webhook-Secret' => $this->secret])->assertOk();

    Mail::assertSent(SubscriptionActivatedMail::class, 1);
});

it('a tela consulta o status e libera quando a AbacatePay diz que está pago', function () {
    Http::fake(['*checkouts/get*' => Http::response(['success' => true, 'data' => ['id' => 'bill_test1', 'status' => 'PAID',
        'metadata' => ['tenant_id' => $this->tenant->id, 'plan_id' => $this->plan->id]]])]);

    $this->actingAs($this->user)->getJson('/checkout/status')
        ->assertOk()->assertJson(['active' => true]);

    expect($this->tenant->fresh()->subscription_status)->toBe('active')
        ->and($this->tx->fresh()->status)->toBe('paid');
    Mail::assertSent(SubscriptionActivatedMail::class);
});

it('status ainda pendente na AbacatePay mantém a conta pendente', function () {
    Http::fake(['*checkouts/get*' => Http::response(['success' => true, 'data' => ['id' => 'bill_test1', 'status' => 'PENDING']])]);

    $this->actingAs($this->user)->getJson('/checkout/status')
        ->assertOk()->assertJson(['active' => false, 'pending_payment' => true]);
    expect($this->tenant->fresh()->subscription_status)->toBe('pending');
});

it('a página de checkout mostra a conferência automática quando há PIX em aberto', function () {
    Cache::flush();
    $this->actingAs($this->user)->get('/checkout/' . $this->plan->id)
        ->assertOk()->assertSee('id="pixPending"', false)->assertSee('Verificar agora');
});

it('assinar o contrato de adesão envia e-mail de confirmação com o próximo passo', function () {
    $this->tenant->update(['subscription_status' => 'awaiting_contract']);
    Cache::flush();
    $contract = Contract::forceCreate([
        'tenant_id' => $this->tenant->id, 'token' => \Illuminate\Support\Str::random(40), 'kind' => 'adesao', 'title' => 'Contrato de Adesão', 'status' => 'pending',
        'signer_name' => 'Ana Dona', 'signer_email' => 'ana.assinante@example.com', 'content' => '<p>x</p>',
    ]);

    $img = imagecreatetruecolor(240, 90);
    for ($i = 0; $i < 4000; $i++) imagesetpixel($img, random_int(0, 239), random_int(0, 89), random_int(0, 0xFFFFFF));
    ob_start(); imagepng($img); $png = ob_get_clean();

    $this->actingAs($this->user)
        ->post(route('adesao.sign', $contract->id), ['signature' => 'data:image/png;base64,' . base64_encode($png)])
        ->assertRedirect();

    expect($this->tenant->fresh()->subscription_status)->toBe('pending');
    Mail::assertSent(AdesaoSignedMail::class, fn ($m) => $m->hasTo('ana.assinante@example.com'));
    Mail::assertSent(AdesaoSignedMail::class, fn ($m) => $m->hasTo($this->user->email));
});
