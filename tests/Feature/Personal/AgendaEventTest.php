<?php

use App\Models\AgendaEvent;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function agendaTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function agendaUser(Tenant $t, string $role = 'common'): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

function agendaEvent(Tenant $t, array $overrides = []): AgendaEvent
{
    return AgendaEvent::create(array_merge([
        'tenant_id' => $t->id,
        'title'     => 'Reunião com cliente',
        'kind'      => 'meeting',
        'starts_on' => now()->addDay()->toDateString(),
        'starts_at' => '10:00',
        'status'    => 'pending',
    ], $overrides));
}

it('lista agenda pra user role=common', function () {
    $t = agendaTenant();
    agendaEvent($t, ['title' => 'Reunião ACME']);

    $this->actingAs(agendaUser($t))
        ->get('/personal/agenda')
        ->assertOk()
        ->assertSee('Reunião ACME');
});

it('bloqueia user role=ngo do /personal/agenda', function () {
    $t = agendaTenant();
    $this->actingAs(agendaUser($t, 'ngo'))
        ->get('/personal/agenda')
        ->assertForbidden();
});

it('nao mostra eventos de outro tenant', function () {
    $t1 = agendaTenant();
    $t2 = agendaTenant();
    agendaEvent($t2, ['title' => 'Compromisso do outro tenant']);

    $this->actingAs(agendaUser($t1))
        ->get('/personal/agenda')
        ->assertOk()
        ->assertDontSee('Compromisso do outro tenant');
});

it('renderiza formulario de novo compromisso', function () {
    $t = agendaTenant();
    $this->actingAs(agendaUser($t))
        ->get('/personal/agenda/create')
        ->assertOk()
        ->assertSee('Novo Compromisso');
});

it('cria compromisso valido', function () {
    $t = agendaTenant();
    $client = Client::create(['tenant_id' => $t->id, 'name' => 'Cliente X']);

    $this->actingAs(agendaUser($t))
        ->post('/personal/agenda', [
            'title'     => 'Visita técnica',
            'kind'      => 'visit',
            'starts_on' => now()->addDays(2)->toDateString(),
            'starts_at' => '14:00',
            'ends_at'   => '15:30',
            'client_id' => $client->id,
            'location'  => 'Escritório ACME',
        ])
        ->assertRedirect(route('agenda.index'));

    $this->assertDatabaseHas('agenda_events', [
        'tenant_id' => $t->id,
        'title'     => 'Visita técnica',
        'kind'      => 'visit',
        'client_id' => $client->id,
        'status'    => 'pending',
    ]);
});

it('rejeita compromisso sem titulo', function () {
    $t = agendaTenant();
    $this->actingAs(agendaUser($t))
        ->post('/personal/agenda', [
            'kind'      => 'meeting',
            'starts_on' => now()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors(['title']);
});

it('rejeita compromisso com hora fim anterior a hora inicio', function () {
    $t = agendaTenant();
    $this->actingAs(agendaUser($t))
        ->post('/personal/agenda', [
            'title'     => 'Reunião',
            'kind'      => 'meeting',
            'starts_on' => now()->addDay()->toDateString(),
            'starts_at' => '15:00',
            'ends_at'   => '14:00',
        ])
        ->assertSessionHasErrors(['ends_at']);
});

it('rejeita client_id de outro tenant', function () {
    $t1 = agendaTenant();
    $t2 = agendaTenant();
    $alienClient = Client::create(['tenant_id' => $t2->id, 'name' => 'Cliente Alheio']);

    $this->actingAs(agendaUser($t1))
        ->post('/personal/agenda', [
            'title'     => 'Reunião',
            'kind'      => 'meeting',
            'starts_on' => now()->addDay()->toDateString(),
            'client_id' => $alienClient->id,
        ])
        ->assertForbidden();
});

it('marca compromisso como concluido', function () {
    $t = agendaTenant();
    $event = agendaEvent($t);

    $this->actingAs(agendaUser($t))
        ->post(route('agenda.done', $event))
        ->assertRedirect();

    expect($event->fresh()->status)->toBe('done');
});

it('reabre compromisso concluido', function () {
    $t = agendaTenant();
    $event = agendaEvent($t, ['status' => 'done']);

    $this->actingAs(agendaUser($t))
        ->post(route('agenda.reopen', $event))
        ->assertRedirect();

    expect($event->fresh()->status)->toBe('pending');
});

it('cancela compromisso', function () {
    $t = agendaTenant();
    $event = agendaEvent($t);

    $this->actingAs(agendaUser($t))
        ->post(route('agenda.cancel', $event))
        ->assertRedirect();

    expect($event->fresh()->status)->toBe('cancelled');
});

it('bloqueia show de evento de outro tenant', function () {
    $t1 = agendaTenant();
    $t2 = agendaTenant();
    $alien = agendaEvent($t2);

    $this->actingAs(agendaUser($t1))
        ->get(route('agenda.show', $alien))
        ->assertForbidden();
});

it('bloqueia destroy de evento de outro tenant', function () {
    $t1 = agendaTenant();
    $t2 = agendaTenant();
    $alien = agendaEvent($t2);

    $this->actingAs(agendaUser($t1))
        ->delete(route('agenda.destroy', $alien))
        ->assertForbidden();

    $this->assertDatabaseHas('agenda_events', ['id' => $alien->id, 'deleted_at' => null]);
});

it('atualiza compromisso proprio', function () {
    $t = agendaTenant();
    $event = agendaEvent($t, ['title' => 'Antigo']);

    $this->actingAs(agendaUser($t))
        ->put(route('agenda.update', $event), [
            'title'     => 'Novo',
            'kind'      => 'call',
            'starts_on' => now()->addDay()->toDateString(),
            'status'    => 'pending',
        ])
        ->assertRedirect(route('agenda.show', $event));

    expect($event->fresh()->title)->toBe('Novo');
    expect($event->fresh()->kind)->toBe('call');
});

it('filtra por status=today', function () {
    $t = agendaTenant();
    agendaEvent($t, ['title' => 'Hoje', 'starts_on' => now()->toDateString()]);
    agendaEvent($t, ['title' => 'Amanha', 'starts_on' => now()->addDay()->toDateString()]);

    $this->actingAs(agendaUser($t))
        ->get('/personal/agenda?status=today')
        ->assertOk()
        ->assertSee('Hoje')
        ->assertDontSee('Amanha');
});

it('filtra por status=overdue', function () {
    $t = agendaTenant();
    agendaEvent($t, ['title' => 'Atrasado', 'starts_on' => now()->subDays(3)->toDateString()]);
    agendaEvent($t, ['title' => 'Futuro',  'starts_on' => now()->addDays(3)->toDateString()]);

    $this->actingAs(agendaUser($t))
        ->get('/personal/agenda?status=overdue')
        ->assertOk()
        ->assertSee('Atrasado')
        ->assertDontSee('Futuro');
});
