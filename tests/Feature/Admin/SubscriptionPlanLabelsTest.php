<?php

use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * Entrega 1 dos planos (2026-08-07): rotulos oficiais do admin usam a
 * constante SubscriptionPlan::AUDIENCE_LABELS. Terceiro Setor primeiro
 * (produto principal), TopEmpresas substitui 'Pessoa Comum'.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function planLabelsAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'two_factor_confirmed_at' => now()]);
}

it('constante AUDIENCE_LABELS tem os 3 slugs com labels novos', function () {
    expect(SubscriptionPlan::AUDIENCE_LABELS)
        ->toHaveKey('ngo')
        ->toHaveKey('manager')
        ->toHaveKey('common');

    expect(SubscriptionPlan::AUDIENCE_LABELS['ngo'])->toContain('Terceiro Setor');
    expect(SubscriptionPlan::AUDIENCE_LABELS['ngo'])->toContain('Principal');
    expect(SubscriptionPlan::AUDIENCE_LABELS['manager'])->toBe('Gestor de Projetos');
    expect(SubscriptionPlan::AUDIENCE_LABELS['common'])->toBe('TopEmpresas');
});

it('ordem da constante coloca Terceiro Setor primeiro', function () {
    $keys = array_keys(SubscriptionPlan::AUDIENCE_LABELS);
    expect($keys[0])->toBe('ngo');
});

it('render direto da view create tem os labels novos (sem middleware)', function () {
    // Rende so a view — evita middleware super_admin + 2FA de sessao.
    // Compartilha $errors pra nao explodir em @error do layout.
    view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    $html = view('admin.plans.create')->render();

    expect($html)->toContain('TopEmpresas');
    expect($html)->toContain('Gestor de Projetos');
    expect($html)->not->toContain('Pessoa Comum');
    expect($html)->not->toContain('Gestor de Empresas');
});

it('render direto da view edit tem os labels novos', function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    $plan = SubscriptionPlan::create([
        'name'            => 'X',
        'target_audience' => 'common',
        'price'           => 10.00,
        'interval'        => 'monthly',
        'is_active'       => true,
        'is_courtesy'     => false,
    ]);
    $html = view('admin.plans.edit', ['plan' => $plan])->render();

    expect($html)->toContain('TopEmpresas');
    expect($html)->toContain('Gestor de Projetos');
    expect($html)->not->toContain('Pessoa Comum');
});

it('welcome mostra so planos do target_audience=ngo', function () {
    SubscriptionPlan::create(['name' => 'ONG Basico', 'target_audience' => 'ngo',     'price' => 79.90, 'is_active' => true, 'is_courtesy' => false]);
    SubscriptionPlan::create(['name' => 'Gestor Pro', 'target_audience' => 'manager', 'price' => 149.00,'is_active' => true, 'is_courtesy' => false]);
    SubscriptionPlan::create(['name' => 'TopE Basico','target_audience' => 'common',  'price' => 89.00, 'is_active' => true, 'is_courtesy' => false]);

    $resp = $this->get('/')->assertStatus(200);

    // Ancora de preco puxa o mais barato APENAS dos NGO (79,90)
    $resp->assertSee('79,90');
    $resp->assertDontSee('149,00');
    $resp->assertDontSee('89,00');
});
