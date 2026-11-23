<?php

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Plano ativo pode ficar fora da home (2026-11-22): continua à venda pelo
 * link direto, mas não aparece no site, no cadastro nem no Bruno.
 */
uses(RefreshDatabase::class);

function sohPlan(string $name, bool $show): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => $name, 'target_audience' => 'ngo', 'price' => 349.90, 'interval' => 'monthly',
        'is_active' => true, 'is_courtesy' => false, 'show_on_home' => $show,
    ]);
}

it('plano fora da home não aparece no site nem na lista do cadastro', function () {
    sohPlan('Plano Visível Exemplo', true);
    sohPlan('Plano Escondido Exemplo', false);

    $this->get('/')->assertOk()->assertSee('Plano Visível Exemplo')->assertDontSee('Plano Escondido Exemplo');
    $this->get('/register')->assertOk()->assertSee('Plano Visível Exemplo')->assertDontSee('Plano Escondido Exemplo');
});

it('plano fora da home continua contratável pelo link direto', function () {
    sohPlan('Plano Visível Exemplo', true);
    $hidden = sohPlan('Plano Escondido Exemplo', false);

    $this->get('/register?plan_id='.$hidden->id)->assertOk()->assertSee('Plano Escondido Exemplo');
});

it('plano novo e antigo aparecem na home por padrão', function () {
    $p = SubscriptionPlan::create(['name' => 'Padrão Exemplo', 'target_audience' => 'ngo', 'price' => 10, 'interval' => 'monthly', 'is_active' => true]);
    expect($p->fresh()->show_on_home)->toBeTrue();
});

it('formulário do admin tem a opção de mostrar na home', function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    expect(view('admin.plans.create')->render())->toContain('name="show_on_home"')->toContain('Mostrar na home do site');
    $plan = sohPlan('Editar Exemplo', false);
    $html = view('admin.plans.edit', ['plan' => $plan])->render();
    expect($html)->toContain('id="showOnHome"')->not->toMatch('/name="show_on_home" value="1" checked/');
});
