<?php

use App\Models\SubscriptionPlan;
use App\Models\User;

beforeEach(function () {
    $this->plan = SubscriptionPlan::forceCreate(['name' => 'Terceiro Setor - Avante', 'price' => 349.90, 'is_active' => true,
        'target_audience' => 'ngo', 'interval' => 'monthly', 'features' => ['Antigo 1', 'Antigo 2']]);
    $this->payload = fn (array $extra = []) => array_merge(['name' => 'Terceiro Setor - Avante', 'target_audience' => 'ngo',
        'price' => 349.90, 'interval' => 'monthly', 'is_active' => 1], $extra);
});

function planTestSuperAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'email' => 'sa_' . uniqid() . '@example.com']);
}

it('removendo todos os itens e salvando, a lista fica vazia', function () {
    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)->actingAs(planTestSuperAdmin())->put(route('admin.plans.update', $this->plan), ($this->payload)())->assertRedirect(route('admin.plans.index'));
    expect($this->plan->fresh()->features)->toBe([]);
});

it('ignora linhas em branco', function () {
    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)->actingAs(planTestSuperAdmin())->put(route('admin.plans.update', $this->plan), ($this->payload)(['features' => [' Novo ', '', null, 'Outro']]));
    expect($this->plan->fresh()->features)->toBe(['Novo', 'Outro']);
});

it('a migração substitui a lista do plano Avante', function () {
    (require database_path('migrations/2026_11_11_030000_replace_features_plano_avante.php'))->up();
    expect($this->plan->fresh()->features)->toHaveCount(13)->not->toContain('Antigo 1');
});

it('acrescenta marketing, API da Meta e Academy ao Avante sem duplicar', function () {
    $this->plan->update(['features' => ['Beneficiários', 'Campanhas de e-mail e atendimento por WhatsApp', 'Assistente de IA (Bruce)']]);
    $m = require database_path('migrations/2026_11_11_040000_add_marketing_academy_meta_features_avante.php');
    $m->up();
    $m->up();

    expect($this->plan->fresh()->features)->toBe([
        'Beneficiários',
        'Campanhas de e-mail e atendimento por WhatsApp',
        'Hub de marketing: gestão do Instagram, gerador de posts com IA e calendário editorial',
        'WhatsApp pela API oficial da Meta',
        'Vivensi Academy: trilhas de capacitação para a equipe',
        'Assistente de IA (Bruce)',
    ]);
});
