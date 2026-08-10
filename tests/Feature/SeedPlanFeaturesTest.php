<?php

use App\Models\SubscriptionPlan;

/**
 * Comando plans:seed-features (2026-08-07) — popula features curados por
 * target_audience com tiers escalonados por preco.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedPlan(string $audience, string $name, float $price): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'            => $name,
        'target_audience' => $audience,
        'price'           => $price,
        'interval'        => 'monthly',
        'is_active'       => true,
        'is_courtesy'     => false,
    ]);
}

it('dry-run nao grava nada no banco', function () {
    seedPlan('ngo', 'Essencial', 79.90);

    $this->artisan('plans:seed-features', ['--dry-run' => true])->assertSuccessful();

    $plan = SubscriptionPlan::where('name', 'Essencial')->first();
    expect($plan->features)->toBeNull();
});

it('1 plano por audience recebe tier FULL', function () {
    seedPlan('ngo', 'NGO Unico', 100.00);

    $this->artisan('plans:seed-features')->assertSuccessful();

    $plan = SubscriptionPlan::where('name', 'NGO Unico')->first();
    expect($plan->features)->toBeArray();
    expect(count($plan->features))->toBeGreaterThanOrEqual(10);
    // Tier FULL comeca por 'Tudo do plano Pro +'
    expect($plan->features[0])->toContain('Tudo do plano Pro');
});

it('2 planos escalonam BASIC + FULL por preco crescente', function () {
    seedPlan('common', 'TopE Basico', 49.90);
    seedPlan('common', 'TopE Full',   149.00);

    $this->artisan('plans:seed-features')->assertSuccessful();

    $basico = SubscriptionPlan::where('name', 'TopE Basico')->first();
    $full   = SubscriptionPlan::where('name', 'TopE Full')->first();

    expect($basico->features[0])->not->toContain('Tudo');
    expect($full->features[0])->toContain('Tudo do plano Pro');
});

it('3 planos escalonam BASIC + PRO + FULL', function () {
    seedPlan('manager', 'Gestor Basico', 89.00);
    seedPlan('manager', 'Gestor Pro',    149.00);
    seedPlan('manager', 'Gestor Full',   249.00);

    $this->artisan('plans:seed-features')->assertSuccessful();

    $b = SubscriptionPlan::where('name', 'Gestor Basico')->first();
    $p = SubscriptionPlan::where('name', 'Gestor Pro')->first();
    $f = SubscriptionPlan::where('name', 'Gestor Full')->first();

    expect($b->features[0])->not->toContain('Tudo');
    expect($p->features[0])->toContain('Tudo do plano Essencial');
    expect($f->features[0])->toContain('Tudo do plano Pro');
});

it('sem --force pula planos com features ja preenchidas', function () {
    $p = seedPlan('ngo', 'Existente', 100.00);
    $p->features = ['Meu feature manual'];
    $p->save();

    $this->artisan('plans:seed-features')->assertSuccessful();

    expect($p->fresh()->features)->toBe(['Meu feature manual']);
});

it('com --force sobrescreve features existentes', function () {
    $p = seedPlan('ngo', 'Sobrescrito', 100.00);
    $p->features = ['Antigo'];
    $p->save();

    $this->artisan('plans:seed-features', ['--force' => true])->assertSuccessful();

    $fresh = $p->fresh()->features;
    expect($fresh)->not->toBe(['Antigo']);
    expect(count($fresh))->toBeGreaterThanOrEqual(10);
});
