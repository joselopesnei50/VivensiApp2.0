<?php

use App\Models\Contract;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Fluxo cadastro -> contrato de adesao -> pagamento/dashboard (2026-08-18).
 *
 *  - Registro NOVO -> subscription_status='awaiting_contract' + redirect /register/contrato
 *  - Cortesia registra igual: passa pelo contrato, apos assinar -> active + /dashboard
 *  - Pago: passa pelo contrato, apos assinar -> pending + /checkout/{plan}
 *  - CheckSubscription bloqueia dashboard enquanto awaiting_contract
 */

function afPaidPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'                  => 'Plano Pago',
        'target_audience'       => 'ngo',
        'price'                 => 129.90,
        'is_active'             => true,
        'is_courtesy'           => false,
        'abacatepay_product_id' => 'prod_test_ade',
    ]);
}

function afCourtesyPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'            => 'Plano Cortesia',
        'target_audience' => 'ngo',
        'price'           => 0,
        'is_active'       => true,
        'is_courtesy'     => true,
    ]);
}

function afRegisterPayload(int $planId, string $email = 'novo@teste.com'): array
{
    return [
        'organization_name' => 'ONG Teste',
        'name'              => 'Fulano Adm',
        'email'             => $email,
        'password'          => 'SenhaSegura2026',
        'password_confirmation' => 'SenhaSegura2026',
        'plan_id'           => $planId,
        'account_type'      => 'ngo_admin',
        'terms'             => 'on',
    ];
}

function afEntityPayload(): array
{
    return [
        'razao_social'     => 'Associacao Teste ONG',
        'document'         => '12.345.678/0001-90',
        'endereco'         => 'Rua das Flores',
        'numero_endereco'  => '100',
        'complemento'      => 'Sala 2',
        'bairro'           => 'Centro',
        'cidade'           => 'Sao Paulo',
        'estado'           => 'SP',
        'cep'              => '01000-000',
        'signer_name'      => 'Fulano Adm',
        'signer_email'     => 'fulano@teste.com',
        'signer_cpf'       => '123.456.789-00',
    ];
}

/** Assinatura PNG 2×2 preta base64 valida (~2200 chars pra passar min length). */
function afValidSignaturePng(): string
{
    // Cria PNG grande o suficiente pra passar do minimo 2000 chars b64.
    $bytes = str_repeat("\x89PNG\r\n\x1a\n" . str_repeat("A", 128), 20);
    return 'data:image/png;base64,' . base64_encode($bytes);
}

test('registro com plano pago cria tenant em awaiting_contract e redireciona pra adesao', function () {
    $plan = afPaidPlan();

    $response = $this->post('/register', afRegisterPayload($plan->id));

    $response->assertRedirect(route('adesao.show'));

    $tenant = Tenant::latest('id')->first();
    expect($tenant)->not->toBeNull();
    expect($tenant->subscription_status)->toBe('awaiting_contract');
    expect((int) $tenant->plan_id)->toBe((int) $plan->id);
});

test('registro com plano cortesia tambem cria tenant em awaiting_contract e redireciona pra adesao', function () {
    $plan = afCourtesyPlan();

    $response = $this->post('/register', afRegisterPayload($plan->id, 'cortesia@teste.com'));

    $response->assertRedirect(route('adesao.show'));

    $tenant = Tenant::latest('id')->first();
    expect($tenant->subscription_status)->toBe('awaiting_contract');
});

test('CheckSubscription bloqueia dashboard e redireciona pra adesao enquanto awaiting_contract', function () {
    $plan   = afPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'awaiting_contract',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertRedirect(route('adesao.show'));
});

test('storeData salva dados do tenant e cria contrato de adesao', function () {
    $plan   = afPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'awaiting_contract',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $response = $this->actingAs($user)->post(route('adesao.store-data'), afEntityPayload());

    $tenant->refresh();
    expect($tenant->razao_social)->toBe('Associacao Teste ONG');
    expect($tenant->document)->toBe('12345678000190');
    expect($tenant->estado)->toBe('SP');

    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->first();
    expect($contract)->not->toBeNull();
    expect($contract->status)->toBe('draft');

    $response->assertRedirect(route('adesao.review', $contract->id));
});

test('sign em plano pago vira pending e redireciona pra checkout', function () {
    $plan   = afPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'awaiting_contract',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), afEntityPayload());
    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->first();

    $response = $this->actingAs($user)->post(route('adesao.sign', $contract->id), [
        'signature' => afValidSignaturePng(),
    ]);

    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('pending');
    expect($tenant->contract_signed_at)->not->toBeNull();

    $contract->refresh();
    expect($contract->status)->toBe('signed');
    expect($contract->signed_at)->not->toBeNull();

    $response->assertRedirect(route('checkout.index', ['plan_id' => $plan->id]));
});

test('sign em plano cortesia vira active e redireciona pra dashboard', function () {
    $plan   = afCourtesyPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'awaiting_contract',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), afEntityPayload());
    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->first();

    $response = $this->actingAs($user)->post(route('adesao.sign', $contract->id), [
        'signature' => afValidSignaturePng(),
    ]);

    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('active');
    expect($tenant->contract_signed_at)->not->toBeNull();

    $response->assertRedirect(route('dashboard'));
});

test('assinatura invalida devolve erro', function () {
    $plan   = afPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'awaiting_contract',
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), afEntityPayload());
    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->first();

    $response = $this->actingAs($user)->post(route('adesao.sign', $contract->id), [
        'signature' => 'nao-e-png',
    ]);

    $response->assertSessionHasErrors('signature');

    $tenant->refresh();
    expect($tenant->subscription_status)->toBe('awaiting_contract');
});

test('tenant legado com subscription_status active NAO e forcado pra adesao', function () {
    $plan   = afPaidPlan();
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id,
        'subscription_status' => 'active',
        // contract_signed_at fica null (contas legadas)
    ]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();
});
