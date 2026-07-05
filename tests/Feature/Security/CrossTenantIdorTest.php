<?php

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use Illuminate\Support\Str;

/**
 * Fix 4 do relatório de segurança (2026-07-05) — regressão de isolamento
 * multi-tenant. Prova que um user autenticado no tenant B não consegue
 * ler recursos do tenant A por ID forjado (IDOR clássico).
 *
 * Cobre 3 caminhos representativos:
 *  1. `WhatsappInstance` — helper `findForTenant()` no controller API
 *  2. `Project` — filtro explícito no controller Api/V1
 *  3. `WhatsappChat` — global scope do BelongsToTenant trait
 *
 * Não é exaustivo (74 models usam o trait), mas garante o pattern.
 */

function idorEnv(): array
{
    $tA = Tenant::factory()->create(['subscription_status' => 'active']);
    $tB = Tenant::factory()->create(['subscription_status' => 'active']);

    $uA = User::factory()->create(['tenant_id' => $tA->id, 'role' => 'manager']);
    $uB = User::factory()->create(['tenant_id' => $tB->id, 'role' => 'manager']);

    return [$tA, $tB, $uA, $uB];
}

it('user do tenant B não acessa WhatsappInstance do tenant A', function () {
    [$tA, , , $uB] = idorEnv();

    $instanceA = WhatsappInstance::create([
        'tenant_id'      => $tA->id,
        'instance_name'  => 'a-' . Str::random(6),
        'instance_token' => Str::random(32),
        'status'         => 'open',
    ]);

    // Health endpoint
    $this->actingAs($uB)
        ->getJson("/whatsapp/instances/{$instanceA->id}/health")
        ->assertStatus(404);

    // API index — não deve incluir instância do tenant A
    $r = $this->actingAs($uB)->getJson('/api/whatsapp/instances');
    $r->assertStatus(200);
    $body = json_encode($r->json());
    expect($body)->not->toContain($instanceA->instance_name);
});

it('user do tenant B não acessa Project do tenant A via Api/V1', function () {
    [$tA, , , $uB] = idorEnv();

    $projectA = Project::create([
        'tenant_id'    => $tA->id,
        'name'         => 'Projeto Sigiloso A',
        'description'  => 'Só do tenant A',
        'status'       => 'active',
        'created_by'   => 1,
    ]);

    $this->actingAs($uB, 'sanctum')
        ->getJson("/api/v1/projects/{$projectA->id}")
        ->assertStatus(404);
});

it('BelongsToTenant filtra WhatsappChat cross-tenant no listing', function () {
    // PHPUnit roda em CLI e o trait auto-desliga em runningInConsole.
    // Reflection força o caminho HTTP como em produção (mesmo padrão do
    // TenantIsolationTest existente).
    $prop = new ReflectionProperty($this->app, 'isRunningInConsole');
    $prop->setAccessible(true);
    $prop->setValue($this->app, false);

    [$tA, $tB, , $uB] = idorEnv();

    $chatA = WhatsappChat::factory()->create([
        'tenant_id' => $tA->id,
        'wa_id'     => '5511900000001',
        'contact_name' => 'Sensivel Tenant A',
    ]);
    $chatB = WhatsappChat::factory()->create([
        'tenant_id' => $tB->id,
        'wa_id'     => '5511900000002',
        'contact_name' => 'Do Tenant B',
    ]);

    $this->actingAs($uB);
    $chats = WhatsappChat::all();

    expect($chats->pluck('id')->toArray())->toContain($chatB->id)
        ->and($chats->pluck('id')->toArray())->not->toContain($chatA->id);
});

it('guest sem auth não vê nenhum registro tenant-scoped (FAIL-CLOSED)', function () {
    $prop = new ReflectionProperty($this->app, 'isRunningInConsole');
    $prop->setAccessible(true);
    $prop->setValue($this->app, false);

    [$tA] = idorEnv();

    WhatsappChat::factory()->create(['tenant_id' => $tA->id]);
    WhatsappChat::factory()->create(['tenant_id' => $tA->id]);

    // Sem actingAs — guest
    $chats = WhatsappChat::all();

    expect($chats->count())->toBe(0);
});
