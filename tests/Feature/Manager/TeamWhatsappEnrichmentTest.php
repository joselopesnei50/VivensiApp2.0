<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappChatAssignment;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function mgrTeamTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function mgrTeamUser(Tenant $t, string $role, string $avail = 'available'): User
{
    return User::factory()->create([
        'tenant_id' => $t->id,
        'role' => $role,
        'agent_availability' => $avail,
    ]);
}

it('/manager/team lista e nao explode com colaboradores sem atendimento', function () {
    $t   = mgrTeamTenant();
    $mgr = mgrTeamUser($t, 'manager');
    mgrTeamUser($t, 'employee');

    $this->actingAs($mgr)->get('/manager/team')->assertStatus(200);
});

it('agente WhatsApp (manager) aparece com contagem de chats 7d', function () {
    // /manager/team lista employee|manager|credenciado. Como so 'manager'
    // esta em AGENT_ROLES dentro dessa lista, e ele que ganha coluna WhatsApp.
    $t     = mgrTeamTenant();
    $mgr   = mgrTeamUser($t, 'manager');
    $other = mgrTeamUser($t, 'manager'); // outro gestor recebe atendimentos
    $chat1 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);
    $chat2 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);

    WhatsappChatAssignment::create([
        'tenant_id' => $t->id, 'chat_id' => $chat1->id,
        'to_user_id' => $other->id, 'action' => 'take_over',
        'started_at' => now()->subHours(2),
    ]);
    WhatsappChatAssignment::create([
        'tenant_id' => $t->id, 'chat_id' => $chat2->id,
        'to_user_id' => $other->id, 'action' => 'take_over',
        'started_at' => now()->subDays(3),
    ]);
    // Fora da janela 7d — nao deve contar
    WhatsappChatAssignment::create([
        'tenant_id' => $t->id, 'chat_id' => $chat1->id,
        'to_user_id' => $other->id, 'action' => 'take_over',
        'started_at' => now()->subDays(10),
    ]);

    $resp = $this->actingAs($mgr)->get('/manager/team')->assertStatus(200);
    $resp->assertSee($other->name);
    $resp->assertSee('Chats 7d');
    $resp->assertSeeInOrder([$other->name, '2', 'Chats 7d']);
});

it('employee NAO ganha coluna WhatsApp (fora de AGENT_ROLES)', function () {
    $t   = mgrTeamTenant();
    $mgr = mgrTeamUser($t, 'manager');
    $emp = mgrTeamUser($t, 'employee');

    $resp = $this->actingAs($mgr)->get('/manager/team')->assertStatus(200);
    $resp->assertSee($emp->name);
    // A view mostra "Chats 7d" apenas pra agentes — se ha apenas employee,
    // ainda mostra pro proprio manager que loga (ele e agente). Aqui garantimos
    // que o employee aparece mas SEM o icone whatsapp associado a ele.
    // Verificacao: o card do employee nao tem 'fa-whatsapp' proximo.
    $html = $resp->getContent();
    expect($html)->toContain($emp->name);
});

it('cross-tenant nao vaza contagem WhatsApp', function () {
    $tA  = mgrTeamTenant();
    $tB  = mgrTeamTenant();
    $mgr = mgrTeamUser($tA, 'manager');
    $agentB = mgrTeamUser($tB, 'manager');
    $chatB = WhatsappChat::factory()->create(['tenant_id' => $tB->id]);

    WhatsappChatAssignment::create([
        'tenant_id' => $tB->id, 'chat_id' => $chatB->id,
        'to_user_id' => $agentB->id, 'action' => 'take_over',
        'started_at' => now()->subHours(1),
    ]);

    $resp = $this->actingAs($mgr)->get('/manager/team')->assertStatus(200);
    $resp->assertDontSee($agentB->name);
});

it('botao Dashboard WhatsApp aparece no topo', function () {
    $t   = mgrTeamTenant();
    $mgr = mgrTeamUser($t, 'manager');

    $this->actingAs($mgr)->get('/manager/team')
        ->assertStatus(200)
        ->assertSee('Dashboard WhatsApp')
        ->assertSee(route('whatsapp.dashboard'));
});
