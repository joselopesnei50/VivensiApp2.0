<?php

use App\Models\MarketingPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IBGEDataService;
use App\Services\MarketingAIService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/*
| Hub de Marketing IA e Inteligência Territorial: o plano falhava por tempo
| (60s) e o diagnóstico vinha vazio ("—") porque o raciocínio do modelo
| consumia os 400 tokens.
*/

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'sk-test', 'api');
    Cache::flush();
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
});

function novoPlano(Tenant $t, User $u): MarketingPlan
{
    return MarketingPlan::create([
        'tenant_id' => $t->id, 'user_id' => $u->id, 'title' => 'Bazar', 'objective' => 'Divulgar o bazar',
        'target_audience' => 'Moradores', 'scope' => 'online', 'tone' => 'friendly', 'status' => 'pending',
    ]);
}

it('plano de marketing: chamada única ao modelo pro, com tempo maior', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => "# Plano\n## Canais"]]]])]);
    $plan = novoPlano($this->tenant, $this->user);

    expect(app(MarketingAIService::class)->generate($plan))->toBeTrue();

    expect($plan->fresh()->status)->toBe('done');
    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $r) => $r['model'] === 'deepseek-v4-pro');
    expect(MarketingAIService::PLAN_TIMEOUT)->toBeGreaterThan(60);
});

it('plano de marketing: resposta vazia marca como falha', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => '']]]])]);
    $plan = novoPlano($this->tenant, $this->user);

    expect(app(MarketingAIService::class)->generate($plan))->toBeFalse();
    expect($plan->fresh()->status)->toBe('failed');
});

it('título do plano é cortado no fim de uma palavra', function () {
    Queue::fake();
    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($this->user)
        ->post(route('marketing.store'), [
            'objective' => 'Divulgar o Bazar Solidário (exemplo) e arrecadar recursos para as oficinas de música do bairro.',
            'target_audience' => 'Moradores do bairro', 'scope' => 'online', 'tone' => 'friendly',
        ]);

    $title = MarketingPlan::withoutGlobalScopes()->latest('id')->value('title');
    expect($title)->toBe('Divulgar o Bazar Solidário (exemplo) e arrecadar recursos para as oficinas de…');
});

it('diagnóstico territorial: pede tokens suficientes e não devolve texto vazio', function () {
    $this->mock(IBGEDataService::class, fn ($m) => $m->shouldReceive('getCityIndicators')->andReturn([
        'populacao' => ['label' => 'População', 'value' => '242228', 'unit' => 'hab.', 'year' => '2022'],
    ]));

    Http::fake(['api.deepseek.com/*' => Http::sequence()
        ->push(['choices' => [['message' => ['content' => '', 'reasoning_content' => '...'], 'finish_reason' => 'length']]])
        ->push(['choices' => [['message' => ['content' => 'Araraquara precisa de projetos de contraturno.']]]])]);

    $get = fn () => $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($this->user)->getJson(route('ibge.indicators', ['cityCode' => '3503208', 'city_name' => 'Araraquara']));

    expect($get()->json('analysis'))->toBe('Não foi possível gerar a análise no momento.');
    expect($get()->json('analysis'))->toBe('Araraquara precisa de projetos de contraturno.');
    Http::assertSent(fn (HttpRequest $r) => ($r['max_tokens'] ?? 0) >= 2000);
});
