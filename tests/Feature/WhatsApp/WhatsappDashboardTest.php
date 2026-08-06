<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappChatAssignment;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function waDashTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function waDashUser(Tenant $t, string $role): User
{
    return User::factory()->create(['tenant_id' => $t->id, 'role' => $role]);
}

function waDashAssign(Tenant $t, WhatsappChat $chat, ?User $to, ?int $duration = null, ?User $actor = null, string $action = 'take_over')
{
    return WhatsappChatAssignment::create([
        'tenant_id'           => $t->id,
        'chat_id'             => $chat->id,
        'to_user_id'          => $to?->id,
        'assigned_by_user_id' => $actor?->id,
        'action'              => $action,
        'started_at'          => now()->subHours(2),
        'ended_at'            => $duration !== null ? now()->subHours(2)->addSeconds($duration) : null,
        'duration_seconds'    => $duration,
    ]);
}

it('dashboard exige role de gestor (employee recebe 403)', function () {
    $t = waDashTenant();
    $emp = waDashUser($t, 'employee');

    $this->actingAs($emp)->get('/whatsapp/dashboard')->assertStatus(403);
});

it('manager acessa dashboard e ve KPIs zerados sem historico', function () {
    $t = waDashTenant();
    $mgr = waDashUser($t, 'manager');

    $this->actingAs($mgr)->get('/whatsapp/dashboard')
        ->assertStatus(200)
        ->assertSee('Atendimentos')
        ->assertSee('Tempo médio');
});

it('ranking mostra agente com contagem correta de atendimentos', function () {
    $t   = waDashTenant();
    $mgr = waDashUser($t, 'manager');
    $a   = waDashUser($t, 'ngo');
    $chat1 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);
    $chat2 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);

    waDashAssign($t, $chat1, $a, 600, $a);  // 10min
    waDashAssign($t, $chat2, $a, 1200, $a); // 20min

    $resp = $this->actingAs($mgr)->get('/whatsapp/dashboard')->assertStatus(200);
    $resp->assertSee($a->name);
    // tempo medio = (600+1200)/2 = 900s = 15min
    $resp->assertSee('15min');
});

it('KPI auto-assign conta assignments com actor null', function () {
    $t   = waDashTenant();
    $mgr = waDashUser($t, 'manager');
    $a   = waDashUser($t, 'ngo');
    $chat1 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);
    $chat2 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);
    $chat3 = WhatsappChat::factory()->create(['tenant_id' => $t->id]);

    waDashAssign($t, $chat1, $a, 300, null);  // auto
    waDashAssign($t, $chat2, $a, 300, null);  // auto
    waDashAssign($t, $chat3, $a, 300, $a);    // manual

    $resp = $this->actingAs($mgr)->get('/whatsapp/dashboard');
    // 2 auto de 3 total = 67%
    $resp->assertSee('67%');
});

it('dashboard nao vaza dados de outro tenant', function () {
    $tA  = waDashTenant();
    $tB  = waDashTenant();
    $mgr = waDashUser($tA, 'manager');
    $agentB = waDashUser($tB, 'ngo');
    $chatB  = WhatsappChat::factory()->create(['tenant_id' => $tB->id]);

    waDashAssign($tB, $chatB, $agentB, 600, $agentB);

    $resp = $this->actingAs($mgr)->get('/whatsapp/dashboard');
    $resp->assertDontSee($agentB->name);
});
