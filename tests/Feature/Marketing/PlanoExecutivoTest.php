<?php

use App\Models\MarketingPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MarketingPlanOutline;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const PLANO_MD = <<<MD
# Bazar Solidário
## 🎯 Posicionamento
### Proposta de valor
- Cada item doado vira aula de música
### Personas
- Dona Maria, 58 anos
- Júlia, 34 anos
## 📱 WhatsApp
- Item direto no tópico
### Grupos
- Grupo piloto com 20 contatos
#### Detalhe de quarto nível
MD;

it('cada sub-tópico mantém os próprios itens (antes todos viravam o último)', function () {
    $o = MarketingPlanOutline::parse(PLANO_MD);

    expect($o['root'])->toBe('Bazar Solidário');
    expect($o['sections'])->toHaveCount(2);
    expect(array_column($o['sections'][0]['h3s'], 'title'))->toBe(['Proposta de valor', 'Personas']);
    expect($o['sections'][0]['h3s'][0]['bullets'])->toBe(['Cada item doado vira aula de música']);
    expect($o['sections'][0]['h3s'][1]['bullets'])->toBe(['Dona Maria, 58 anos', 'Júlia, 34 anos']);
    expect(array_column($o['sections'][1]['h3s'], 'title'))->toBe(['Pontos-chave', 'Grupos']);
    expect($o['sections'][1]['h3s'][1]['bullets'])->toBe(['Grupo piloto com 20 contatos', 'Detalhe de quarto nível']);
});

it('a tela do plano mostra os sub-tópicos diferentes', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
    $plan = MarketingPlan::create([
        'tenant_id' => $tenant->id, 'user_id' => $user->id, 'title' => 'Bazar', 'objective' => 'Divulgar',
        'target_audience' => 'Bairro', 'scope' => 'online', 'tone' => 'friendly', 'status' => 'done',
        'mindmap_data' => ['markdown' => PLANO_MD],
    ]);

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($user)->get(route('marketing.show', $plan))->assertOk()
        ->assertSeeInOrder(['Proposta de valor', 'Cada item doado vira aula de música', 'Personas', 'Dona Maria, 58 anos']);
});
