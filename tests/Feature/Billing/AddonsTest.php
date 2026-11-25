<?php

use App\Models\BillingAddon;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AbacatePayService;
use App\Services\Billing\AddonService;
use App\Services\Billing\InvoiceService;
use App\Services\EmailQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Adicionais (2026-11-25): número extra no bot e pacote de e-mails/dia,
 * cobrados na mesma fatura do plano.
 */
uses(RefreshDatabase::class);

function adPlan(array $o = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name' => 'Plano Teste', 'target_audience' => 'ngo', 'price' => 349.90,
        'is_active' => true, 'is_courtesy' => false, 'abacatepay_product_id' => 'prod_plano',
    ], $o));
}

function adTenant(?SubscriptionPlan $plan = null, array $o = []): Tenant
{
    $plan ??= adPlan();
    return Tenant::factory()->create(array_merge(['type' => 'ngo', 'subscription_status' => 'active', 'plan_id' => $plan->id], $o));
}

function adUser(Tenant $t, string $role = 'ngo', array $o = []): User
{
    return User::factory()->create(array_merge(['tenant_id' => $t->id, 'role' => $role, 'status' => 'active'], $o));
}

function adAddon(string $code): BillingAddon
{
    return BillingAddon::where('code', $code)->firstOrFail();
}

it('catálogo vem com os 2 produtos avulsos da AbacatePay', function () {
    expect(adAddon('bot_number')->abacatepay_product_id)->toBe('prod_GyBj0phnbrskDxPWaXTSssYU');
    expect((float) adAddon('bot_number')->unit_price)->toBe(19.90);
    expect(adAddon('email_pack')->abacatepay_product_id)->toBe('prod_6WtM3C4LNX4d4RefEhGXEMEB');
    expect(adAddon('email_pack')->unit_amount)->toBe(25);
});

it('o primeiro administrador ganha o número incluso; o resto da equipe não', function () {
    $t = adTenant();
    $admin = adUser($t);
    $outroAdmin = adUser($t);
    $colab = adUser($t, 'employee');

    expect($admin->fresh()->bot_access)->toBeTrue();
    expect($outroAdmin->fresh()->bot_access)->toBeFalse();
    expect($colab->fresh()->bot_access)->toBeFalse();
});

it('bot_access não entra por mass assignment', function () {
    $t = adTenant();
    adUser($t);
    $u = adUser($t, 'employee');
    $u->update(['bot_access' => true]);
    expect($u->fresh()->bot_access)->toBeFalse();
});

it('número extra libera mais uma pessoa no bot, e não deixa passar do limite', function () {
    $t = adTenant();
    adUser($t);
    $b = adUser($t, 'employee');
    $c = adUser($t, 'manager');
    $svc = app(AddonService::class);

    expect($svc->setBotAccess($t, $b, true))->toContain('Todos os números do bot estão em uso');

    expect($svc->setQuantity($t, adAddon('bot_number'), 1))->toBeNull();
    expect($svc->botSeatLimit($t))->toBe(2);
    expect($svc->setBotAccess($t, $b, true))->toBeNull();
    expect($b->fresh()->bot_access)->toBeTrue();
    expect($svc->setBotAccess($t, $c, true))->not->toBeNull();

    // Diminuir abaixo do que está em uso pede para tirar alguém antes.
    expect($svc->setQuantity($t, adAddon('bot_number'), 0))->toContain('Tire o acesso de 1 pessoa');
    $svc->setBotAccess($t, $b, false);
    expect($svc->setQuantity($t, adAddon('bot_number'), 0))->toBeNull();
});

it('não libera bot para gente de outra organização', function () {
    $t = adTenant();
    adUser($t);
    $outro = adUser(adTenant(), 'employee');
    app(AddonService::class)->setQuantity($t, adAddon('bot_number'), 1);
    expect(app(AddonService::class)->setBotAccess($t, $outro, true))->toBe('Esta pessoa não pode usar o bot.');
});

it('plano cortesia não contrata adicional', function () {
    $t = adTenant(adPlan(['is_courtesy' => true, 'price' => 0]));
    expect(app(AddonService::class)->setQuantity($t, adAddon('email_pack'), 1))->toContain('plano pago ativo');
});

it('pacote de e-mail soma na cota do dia sem mexer na base', function () {
    $t = adTenant(null, ['daily_email_quota' => 25]);
    app(AddonService::class)->setQuantity($t, adAddon('email_pack'), 2);
    $q = app(EmailQuotaService::class);
    expect($q->getBaseQuota($t))->toBe(25);
    expect($q->getExtraQuota($t))->toBe(50);
    expect($q->getQuota($t))->toBe(75);
});

it('fatura junta plano + adicionais numa cobrança só', function () {
    $plan = adPlan();
    $t = adTenant($plan);
    $svc = app(AddonService::class);
    $svc->setQuantity($t, adAddon('bot_number'), 2);
    $svc->setQuantity($t, adAddon('email_pack'), 1);

    $abacate = Mockery::mock(AbacatePayService::class);
    $abacate->shouldReceive('createCheckout')->once()
        ->withArgs(function (array $items) {
            expect($items)->toBe([
                ['id' => 'prod_plano', 'quantity' => 1],
                ['id' => 'prod_GyBj0phnbrskDxPWaXTSssYU', 'quantity' => 2],
                ['id' => 'prod_6WtM3C4LNX4d4RefEhGXEMEB', 'quantity' => 1],
            ]);
            return true;
        })
        ->andReturn(['id' => 'chk_1', 'url' => 'https://pay/chk_1']);
    app()->instance(AbacatePayService::class, $abacate);

    $inv = app(InvoiceService::class)->generateForTenant($t->fresh('plan'));

    expect($inv->amount_cents)->toBe(34990 + 2 * 1990 + 990);
    expect($inv->description)->toContain('+ adicionais');
    expect($inv->items)->toHaveCount(3);
    expect($inv->abacatepay_billing_url)->toBe('https://pay/chk_1');
});

it('sem adicionais a fatura continua só com o plano', function () {
    $plan = adPlan();
    $t = adTenant($plan);
    $abacate = Mockery::mock(AbacatePayService::class);
    $abacate->shouldReceive('createCheckout')->once()
        ->withArgs(fn (array $items) => $items === [['id' => 'prod_plano', 'quantity' => 1]])
        ->andReturn(['id' => 'chk_2', 'url' => 'https://pay/chk_2']);
    app()->instance(AbacatePayService::class, $abacate);

    $inv = app(InvoiceService::class)->generateForTenant($t->fresh('plan'));
    expect($inv->amount_cents)->toBe(34990);
    expect($inv->description)->not->toContain('adicionais');
});

it('plano anual gera fatura de 1 ano, com adicional ×12', function () {
    $plan = adPlan(['price' => 3499.00, 'interval' => 'yearly']);
    $t = adTenant($plan);
    app(AddonService::class)->setQuantity($t, adAddon('bot_number'), 1);
    $abacate = Mockery::mock(AbacatePayService::class);
    $abacate->shouldReceive('createCheckout')->once()
        ->withArgs(fn (array $items) => $items[1] === ['id' => 'prod_GyBj0phnbrskDxPWaXTSssYU', 'quantity' => 12])
        ->andReturn(['id' => 'chk_3', 'url' => 'https://pay/chk_3']);
    app()->instance(AbacatePayService::class, $abacate);

    $inv = app(InvoiceService::class)->generateForTenant($t->fresh('plan'));
    expect($inv->amount_cents)->toBe(349900 + 12 * 1990);
    expect($inv->period_start->copy()->addYear()->subDay()->toDateString())->toBe($inv->period_end->toDateString());

    // O próximo ciclo começa 1 ano depois — não gera outra fatura no mês seguinte.
    $this->travelTo($inv->period_start->copy()->addMonth());
    app(InvoiceService::class)->generateForTenant($t->fresh('plan'));
    expect(Invoice::withoutGlobalScopes()->where('tenant_id', $t->id)->count())->toBe(1);
});

it('item sem produto na AbacatePay não gera link (não cobra valor errado)', function () {
    $plan = adPlan();
    $t = adTenant($plan);
    app(AddonService::class)->setQuantity($t, adAddon('email_pack'), 1);
    adAddon('email_pack')->update(['abacatepay_product_id' => null]);
    $abacate = Mockery::mock(AbacatePayService::class);
    $abacate->shouldNotReceive('createCheckout');
    app()->instance(AbacatePayService::class, $abacate);

    $inv = app(InvoiceService::class)->generateForTenant($t->fresh('plan'));
    expect($inv->amount_cents)->toBe(34990 + 990);
    expect($inv->abacatepay_billing_url)->toBeNull();
});

it('bot recusa quem tem telefone mas não tem acesso', function () {
    \Illuminate\Support\Facades\Queue::fake();
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 200)]);
    SystemSetting::setValue('bot_enabled', '1');
    config(['services.whatsapp.bot_secret' => 'segredo-123']);
    $payload = fn ($n) => ['event' => 'messages.upsert', 'data' => ['key' => ['remoteJid' => "{$n}@s.whatsapp.net", 'fromMe' => false, 'id' => 'X'], 'message' => ['conversation' => 'oi']]];

    $t = adTenant();
    $admin = adUser($t);
    $admin->update(['phone' => '(16) 99999-1111']);
    $colab = adUser($t, 'employee');
    $colab->update(['phone' => '(16) 99999-2222']);

    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload('5516999991111'))->assertJson(['status' => 'queued']);
    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload('5516999992222'))->assertJson(['status' => 'no_bot_access']);

    app(AddonService::class)->setQuantity($t, adAddon('bot_number'), 1);
    app(AddonService::class)->setBotAccess($t, $colab->fresh(), true);
    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload('5516999992222'))->assertJson(['status' => 'queued']);
});

it('mesmo número em duas contas: vale a que liberou o bot', function () {
    \Illuminate\Support\Facades\Queue::fake();
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 200)]);
    SystemSetting::setValue('bot_enabled', '1');
    config(['services.whatsapp.bot_secret' => 'segredo-123']);
    $payload = ['event' => 'messages.upsert', 'data' => ['key' => ['remoteJid' => '5516999993333@s.whatsapp.net', 'fromMe' => false, 'id' => 'X'], 'message' => ['conversation' => 'oi']]];

    $t1 = adTenant();
    adUser($t1);
    adUser($t1, 'employee')->update(['phone' => '(16) 99999-3333']); // sem acesso
    adUser(adTenant())->update(['phone' => '16999993333']);          // admin, com acesso

    $this->postJson('/api/whatsapp/bot?bot_token=segredo-123', $payload)->assertJson(['status' => 'queued']);
});

it('tela de adicionais: administrador contrata; colaborador e coordenação não entram', function () {
    $t = adTenant();
    $admin = adUser($t);
    $colab = adUser($t, 'employee');
    $coord = adUser($t, 'manager');

    $this->actingAs($colab)->get('/minha-conta/adicionais')->assertStatus(403);
    $this->actingAs($coord)->get('/minha-conta/adicionais')->assertStatus(403);

    $this->actingAs($admin)->get('/minha-conta/adicionais')->assertOk()->assertSee('Adicionais')->assertSee('1 de 1 em uso');
    $this->actingAs($admin)->post('/minha-conta/adicionais/email_pack', ['quantity' => 2])->assertSessionHas('success');
    expect(app(AddonService::class)->quantity($t, 'email_pack'))->toBe(2);

    $this->actingAs($admin)->post('/minha-conta/adicionais/bot_number', ['quantity' => 1]);
    $this->actingAs($admin)->post("/minha-conta/adicionais/bot/{$colab->id}", ['on' => 1])->assertSessionHas('success');
    expect($colab->fresh()->bot_access)->toBeTrue();
});

it('admin da plataforma ajusta preço e vê quem contratou', function () {
    $own = Tenant::factory()->create(['subscription_status' => 'active']);
    $sa = User::factory()->create(['tenant_id' => $own->id, 'role' => 'super_admin', 'status' => 'active']);
    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
    $t = adTenant();
    app(AddonService::class)->setQuantity($t, adAddon('email_pack'), 3);

    $this->actingAs($sa)->get('/admin/adicionais')->assertOk()->assertSee($t->name)->assertSee('R$ 29,70');
    $this->actingAs($sa)->post('/admin/adicionais/catalogo/' . adAddon('email_pack')->id, [
        'name' => 'Pacote +25', 'unit_price' => 12.90, 'unit_amount' => 25, 'max_quantity' => 20,
        'abacatepay_product_id' => 'prod_6WtM3C4LNX4d4RefEhGXEMEB', 'is_active' => 1,
    ])->assertSessionHas('success');
    expect((float) adAddon('email_pack')->unit_price)->toBe(12.90);
});
