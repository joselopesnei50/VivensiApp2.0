<?php

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;

/*
| Cadastro público: só Terceiro Setor (ONG).
*/

beforeEach(function () {
    $this->ngo = SubscriptionPlan::forceCreate(['name' => 'ONG Essencial', 'price' => 149.90, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'ngo']);
    $this->manager = SubscriptionPlan::forceCreate(['name' => 'Gestor', 'price' => 99, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'manager']);
    $this->common = SubscriptionPlan::forceCreate(['name' => 'Pessoal', 'price' => 29, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'common']);
});

function regPayload(array $over = []): array
{
    return array_merge([
        'organization_name' => 'Instituto Semente', 'name' => 'Ana Lima',
        'email' => 'ana_' . uniqid() . '@example.com',
        'password' => 'SenhaForte2026', 'password_confirmation' => 'SenhaForte2026', 'terms' => '1',
    ], $over);
}

it('a página não mostra mais as opções de gestor e pessoa física', function () {
    $this->get('/register')->assertOk()
        ->assertSee('Cadastre sua organização')
        ->assertDontSee('project_manager')
        ->assertDontSee('name="account_type"', false)
        ->assertSee('ONG Essencial')
        ->assertDontSee('Gestor');
});

it('com um único plano de ONG ele já vem escolhido', function () {
    $this->get('/register')->assertOk()->assertSee('name="plan_id" value="' . $this->ngo->id . '"', false);
});

it('plano de gestor ou pessoal pelo link cai na escolha de planos de ONG', function () {
    $this->get('/register?plan_id=' . $this->manager->id)->assertOk()
        ->assertSee('ONG Essencial')->assertDontSee('Gestor');
});

it('recusa cadastro com plano que não é do Terceiro Setor', function () {
    $this->post('/register', regPayload(['plan_id' => $this->manager->id]))->assertSessionHasErrors('plan_id');
    $this->post('/register', regPayload(['plan_id' => $this->common->id]))->assertSessionHasErrors('plan_id');
    expect(Tenant::where('name', 'Instituto Semente')->exists())->toBeFalse();
});

it('cria sempre organização do tipo ONG, mesmo se mandarem account_type', function () {
    $p = regPayload(['plan_id' => $this->ngo->id, 'account_type' => 'project_manager']);
    $this->post('/register', $p)->assertRedirect();

    $user = User::where('email', $p['email'])->sole();
    expect($user->role)->toBe('ngo')
        ->and(Tenant::find($user->tenant_id)->type)->toBe('ngo');
});
