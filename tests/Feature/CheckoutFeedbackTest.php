<?php

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;

/*
| Checkout: toda saída antes da AbacatePay precisa mostrar o motivo na tela
| (antes a página só recarregava, sem mensagem).
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['subscription_status' => 'trialing', 'type' => 'ngo', 'document' => null]);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo', 'email' => 'ck_' . uniqid() . '@example.com']);
    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
});

it('plano inexistente volta com a mensagem do erro', function () {
    $this->actingAs($this->user)->from('/checkout/999')
        ->post('/checkout/process', ['plan_id' => 999999, 'document' => '12345678909'])
        ->assertRedirect('/checkout/999')
        ->assertSessionHas('error');
});

it('documento com dígitos a menos volta com mensagem clara', function () {
    $plan = SubscriptionPlan::query()->first() ?? SubscriptionPlan::forceCreate(['name' => 'Plano T', 'price' => 99, 'is_active' => true, 'target_audience' => 'ngo']);
    $this->actingAs($this->user)->from('/checkout/' . $plan->id)
        ->post('/checkout/process', ['plan_id' => $plan->id, 'document' => '123'])
        ->assertRedirect('/checkout/' . $plan->id)
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'CPF (11 dígitos)'));
});
