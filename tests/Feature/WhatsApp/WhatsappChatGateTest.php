<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;

/**
 * Auditoria 2026-08-01 — achados 1 e 2 do /whatsapp/chat:
 * endpoints do chat sem Gate::authorize('access-whatsapp') permitiam que
 * role=employee lesse conversas, iniciasse chats, trocasse PIX/treinamento
 * do bot (saveSettings) e pareasse a instância (qr-code/pairing).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function wgTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function wgEmployee(Tenant $tenant): User
{
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);
}

function wgManager(Tenant $tenant): User
{
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

it('employee recebe 403 nos endpoints do chat', function (string $method, string $uri) {
    $tenant   = wgTenant();
    $employee = wgEmployee($tenant);
    $chat     = WhatsappChat::factory()->create(['tenant_id' => $tenant->id]);

    $uri = str_replace('{chat}', (string) $chat->id, $uri);

    $this->actingAs($employee)->json($method, $uri)->assertStatus(403);
})->with([
    ['POST', '/whatsapp/settings'],
    ['GET',  '/whatsapp/chat/list'],
    ['GET',  '/whatsapp/chat/{chat}/messages'],
    ['POST', '/whatsapp/chat/start'],
    ['POST', '/whatsapp/chat/{chat}/read'],
    ['POST', '/whatsapp/notes'],
    ['GET',  '/whatsapp/canned'],
    ['POST', '/whatsapp/canned'],
    ['GET',  '/api/whatsapp/templates'],
    ['GET',  '/whatsapp/status'],
    ['GET',  '/whatsapp/qr-code'],
    ['POST', '/whatsapp/pairing-code'],
]);

it('manager continua acessando chat list e mensagens', function () {
    $tenant  = wgTenant();
    $manager = wgManager($tenant);
    $chat    = WhatsappChat::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($manager)->getJson('/whatsapp/chat/list')->assertStatus(200);
    $this->actingAs($manager)->getJson("/whatsapp/chat/{$chat->id}/messages")->assertStatus(200);
});

it('manager continua salvando settings e config e criada se nao existir', function () {
    $tenant  = wgTenant();
    $manager = wgManager($tenant);

    expect(\App\Models\WhatsappConfig::where('tenant_id', $tenant->id)->count())->toBe(0);

    $this->actingAs($manager)
        ->post('/whatsapp/settings', ['ai_enabled' => '0'])
        ->assertRedirect();

    expect(\App\Models\WhatsappConfig::where('tenant_id', $tenant->id)->count())->toBe(1);
});
