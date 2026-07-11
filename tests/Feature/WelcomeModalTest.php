<?php

use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user   = User::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'role'                 => 'common',
        'welcome_dismissed_at' => null,
    ]);
});

it('POST /welcome/dismiss marca welcome_dismissed_at pra user autenticado', function () {
    expect($this->user->welcome_dismissed_at)->toBeNull();

    $this->actingAs($this->user)
        ->postJson('/welcome/dismiss')
        ->assertStatus(200)
        ->assertJson(['ok' => true]);

    expect($this->user->fresh()->welcome_dismissed_at)->not->toBeNull();
});

it('POST /welcome/dismiss e idempotente (chamada duas vezes nao muda timestamp original)', function () {
    $this->actingAs($this->user)->postJson('/welcome/dismiss')->assertStatus(200);
    $primeiroDismiss = $this->user->fresh()->welcome_dismissed_at;

    // Aguarda um pouco pra garantir que now() ficaria diferente se sobrescrevesse
    sleep(1);

    $this->actingAs($this->user)->postJson('/welcome/dismiss')->assertStatus(200);
    $segundoDismiss = $this->user->fresh()->welcome_dismissed_at;

    expect($primeiroDismiss->timestamp)->toBe($segundoDismiss->timestamp);
});

it('POST /welcome/dismiss exige autenticacao (401 sem login)', function () {
    $this->postJson('/welcome/dismiss')->assertStatus(401);
});

it('layout inclui modal quando user nunca dispensou', function () {
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertSee('welcome-modal-backdrop', false);
});

it('layout NAO inclui modal quando user ja dispensou', function () {
    $this->user->update(['welcome_dismissed_at' => now()]);
    $this->user->refresh();

    // Confirma que a flag persistiu no DB — se falhar, problema é model/migration
    expect($this->user->welcome_dismissed_at)->not->toBeNull();
    expect(App\Models\User::find($this->user->id)->welcome_dismissed_at)->not->toBeNull();

    $response = $this->actingAs($this->user)->get('/dashboard');
    $response->assertStatus(200);

    // O partial só renderiza se auth()->user()->welcome_dismissed_at === null.
    // Com flag setada, o bloco <div id="welcome-modal-backdrop"> não deve aparecer.
    $body = $response->getContent();
    $count = substr_count($body, 'welcome-modal-backdrop');

    // Se contar > 0, dumpa 300 chars antes/depois pra debugar
    if ($count > 0) {
        $pos = strpos($body, 'welcome-modal-backdrop');
        $snippet = substr($body, max(0, $pos - 200), 400);
        dump("Encontrou 'welcome-modal-backdrop' — trecho:", $snippet);
    }

    expect($count)->toBe(0);
});
