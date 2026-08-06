<?php

use App\Models\SubscriptionPlan;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('landing / renderiza sem erro mesmo sem plano cadastrado', function () {
    $this->get('/')
        ->assertStatus(200)
        ->assertSee('Vivensi', false)
        ->assertSee('Planos personalizados por porte'); // fallback quando plans vazio
});

it('landing / mostra ancora de preco quando ha plano cadastrado', function () {
    SubscriptionPlan::create([
        'name'            => 'Essencial',
        'description'     => 'Plano de entrada',
        'price'           => 79.90,
        'is_active'       => true,
        'is_courtesy'     => false,
        'target_audience' => 'ngo',
    ]);

    $resp = $this->get('/')->assertStatus(200);
    $resp->assertSee('A partir de');
    $resp->assertSee('79,90');
    $resp->assertSee('Cancele quando quiser');
    // Nao mostra o fallback quando ha plano
    $resp->assertDontSee('Planos personalizados por porte');
});

it('landing / pega o MENOR plano quando ha varios', function () {
    SubscriptionPlan::create(['name' => 'Pro',       'price' => 299.00, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'ngo']);
    SubscriptionPlan::create(['name' => 'Essencial', 'price' => 89.00,  'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'ngo']);
    SubscriptionPlan::create(['name' => 'Enterprise','price' => 999.00, 'is_active' => true, 'is_courtesy' => false, 'target_audience' => 'ngo']);

    $resp = $this->get('/')->assertStatus(200);
    $resp->assertSee('89,00');
    $resp->assertDontSee('999,00 &nbsp;');
});
