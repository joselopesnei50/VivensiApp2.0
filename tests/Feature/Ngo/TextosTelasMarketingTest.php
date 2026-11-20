<?php

use App\Models\LandingPage;
use App\Models\Raffle;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Volunteer;
use App\Services\DeepSeekService;
use App\Services\StrategyRoom\ProgramsAgentService;
use App\Services\StrategyRoom\PromptRules;
use App\Http\Controllers\StrategyRoomController;

/*
| Textos mostrados ao usuário: nome da organização nos rodapés públicos,
| disponibilidade em português, ícones e idioma dos agentes.
*/

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'type' => 'ngo', 'name' => 'Associação Bem Viver', 'subscription_status' => 'active', 'trial_ends_at' => now()->addDays(10),
    ]);
});

it('rodapé da landing page mostra o nome da organização', function () {
    $page = LandingPage::create(['tenant_id' => $this->tenant->id, 'title' => 'Bazar', 'slug' => 'bazar-' . uniqid(), 'status' => 'published']);

    $this->get('/lp/' . $page->slug)->assertOk()
        ->assertSee('Associação Bem Viver')
        ->assertDontSee('Mantido por Organização Social');
});

it('página pública da rifa mostra a organização, não "Vivensi Solidário"', function () {
    $raffle = Raffle::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'active']);

    $this->get(route('public.raffle.show', $raffle->slug))->assertOk()
        ->assertSee('Associação Bem Viver')
        ->assertDontSee('Vivensi Solidário');
});

it('disponibilidade do voluntário aparece em português e o layout carrega os ícones', function () {
    Volunteer::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => 'ana@ex.com', 'status' => 'active', 'availability' => 'weekends']);
    $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);

    $this->actingAs($user)->get('/ngo/hr')->assertOk()
        ->assertSee('Finais de semana')
        ->assertDontSee('Weekends')
        ->assertSee('bootstrap-icons', false);
});

it('agentes da Sala de Estratégia recebem a regra de acentuação', function () {
    $sent = null;
    $this->mock(DeepSeekService::class, function ($m) use (&$sent) {
        $m->shouldReceive('chat')->andReturnUsing(function ($messages) use (&$sent) {
            $sent = $messages;
            return ['choices' => [['message' => ['content' => '{"fala":"Ação prioritária"}']]]];
        });
    });

    app(ProgramsAgentService::class)->speak($this->tenant->id);

    expect($sent[0]['content'])->toContain(PromptRules::LANGUAGE);
    expect(StrategyRoomController::agentsMeta()['estrategista_chefe']['bio'])->toContain('outros quatro');
});
