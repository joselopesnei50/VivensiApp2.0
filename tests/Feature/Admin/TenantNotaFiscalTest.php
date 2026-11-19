<?php

use App\Models\Contract;
use App\Models\Notification;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Dados para nota fiscal na ficha da organização (super admin) + aviso
 * ao super admin quando um contrato de adesão é assinado.
 */

function nfTenantAssinado(): array
{
    $plan = SubscriptionPlan::create([
        'name' => 'Plano NF', 'target_audience' => 'ngo', 'price' => 129.90,
        'is_active' => true, 'is_courtesy' => false,
    ]);
    $tenant = Tenant::factory()->create([
        'plan_id' => $plan->id, 'subscription_status' => 'active',
        'razao_social' => 'Associacao Exemplo NF', 'document' => '12345678000190',
        'endereco' => 'Rua das Flores', 'numero_endereco' => '100', 'bairro' => 'Centro',
        'cidade' => 'Araraquara', 'estado' => 'SP', 'cep' => '14800000',
    ]);
    $contract = Contract::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id, 'kind' => 'adesao', 'token' => Str::random(64),
        'status' => 'signed', 'signed_at' => now(), 'version' => '2.1',
        'title' => 'Contrato de Adesao Vivensi SaaS', 'content' => '<p>contrato</p>',
        'signer_name' => 'Fulana Responsavel', 'signer_email' => 'nota@exemplo.org',
        'signer_cpf' => '12345678900',
    ]);
    return [$tenant, $contract];
}

function nfSuperAdmin(): User
{
    $own = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $own->id, 'role' => 'super_admin']);
}

test('super admin ve os dados para nota fiscal na ficha da organizacao', function () {
    [$tenant] = nfTenantAssinado();

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs(nfSuperAdmin())
        ->get(route('admin.tenants.show', $tenant->id))
        ->assertOk()
        ->assertSee('Dados para Nota Fiscal')
        ->assertSee('Associacao Exemplo NF')
        ->assertSee('12.345.678/0001-90')
        ->assertSee('14800-000')
        ->assertSee('nota@exemplo.org')
        ->assertSee('Fulana Responsavel')
        ->assertSee(route('admin.tenants.adesao-pdf', $tenant->id), false);
});

test('pdf do contrato assinado: super admin baixa, cliente nao', function () {
    [$tenant] = nfTenantAssinado();

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs(nfSuperAdmin())
        ->get(route('admin.tenants.adesao-pdf', $tenant->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $cliente = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);
    $resp = $this->actingAs($cliente)->get(route('admin.tenants.adesao-pdf', $tenant->id));
    expect($resp->status())->not->toBe(200);
});

test('assinar o contrato avisa o super admin no sininho', function () {
    $admin = nfSuperAdmin();
    $plan = SubscriptionPlan::create([
        'name' => 'Plano Pago NF', 'target_audience' => 'ngo', 'price' => 99,
        'is_active' => true, 'is_courtesy' => false,
    ]);
    $tenant = Tenant::factory()->create(['plan_id' => $plan->id, 'subscription_status' => 'awaiting_contract']);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->actingAs($user)->post(route('adesao.store-data'), [
        'razao_social' => 'Associacao Teste', 'document' => '12.345.678/0001-90',
        'endereco' => 'Rua A', 'numero_endereco' => '1', 'bairro' => 'Centro',
        'cidade' => 'Araraquara', 'estado' => 'SP', 'cep' => '14800-000',
        'signer_name' => 'Fulano', 'signer_email' => 'fulano@teste.com', 'signer_cpf' => '123.456.789-00',
    ]);
    $contract = Contract::where('tenant_id', $tenant->id)->where('kind', 'adesao')->first();
    $bytes = str_repeat("\x89PNG\r\n\x1a\n" . str_repeat('A', 128), 20);
    $this->actingAs($user)->post(route('adesao.sign', $contract->id), [
        'signature' => 'data:image/png;base64,' . base64_encode($bytes),
    ]);

    $aviso = Notification::withoutGlobalScopes()->where('user_id', $admin->id)->first();
    expect($aviso)->not->toBeNull();
    expect($aviso->title)->toContain('nota fiscal');
    expect($aviso->link)->toBe(route('admin.tenants.show', $tenant->id));
});
