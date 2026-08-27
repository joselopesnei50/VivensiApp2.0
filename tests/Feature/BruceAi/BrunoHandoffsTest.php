<?php

use App\Models\BrunoHandoff;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Services\Bruno\BrunoHandoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Fase 4 Bruno — handoff inteligente pro atendimento humano.
 * Cobre: migration, service (briefing IA + idempotencia + assume/resolve),
 * hook por deteccao textual, controller/rotas com isolamento de tenant.
 */

function bhSuperAdmin(): User
{
    return User::factory()->create([
        'role'                    => 'super_admin',
        'tenant_id'               => null,
        'two_factor_confirmed_at' => now(),
        'two_factor_secret'       => 'test-secret',
    ]);
}

function bhAsSuperAdmin(User $u)
{
    return test()->actingAs($u)->withSession(['2fa_verified' => true]);
}

function bhMakeChat(Tenant $tenant, array $overrides = []): WhatsappChat
{
    return WhatsappChat::create(array_merge([
        'tenant_id'     => $tenant->id,
        'wa_id'         => '5511900000000',
        'contact_name'  => 'ONG Teste',
        'contact_phone' => '+55 11 90000-0000',
        'status'        => 'open',
        'is_bot_active' => true,
    ], $overrides));
}

function bhMockDeepSeekJson(): void
{
    SystemSetting::updateOrCreate(
        ['key' => 'deepseek_api_key'],
        ['value' => 'test-key-123']
    );

    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'briefing' => 'Lead da ONG Alfa em Sao Paulo com projeto ativo de assistencia social — objecao inicial de verba resolvida com condicao especial. Fase de proposta. Proximo passo: agendar reuniao esta semana.',
                        'structured_data' => [
                            'organizacao'          => 'ONG Alfa',
                            'tipo_organizacao'     => 'ong',
                            'cidade'               => 'Sao Paulo/SP',
                            'area_atuacao'         => 'Assistencia social',
                            'orcamento_mencionado' => 'R$ 429/mes ok',
                            'urgencia'             => 'media',
                            'fase_funil'           => 'proposta',
                            'sinais_compra'        => ['pediu preco', 'aceitou demo'],
                            'objecoes_levantadas'  => ['verba apertada'],
                            'proxima_acao'         => 'Agendar reuniao pra fechar plano',
                        ],
                    ]),
                ],
            ]],
        ], 200),
    ]);
}

it('migration cria tabela bruno_handoffs com colunas esperadas', function () {
    expect(\Schema::hasTable('bruno_handoffs'))->toBeTrue();
    expect(\Schema::hasColumns('bruno_handoffs', [
        'id', 'tenant_id', 'whatsapp_chat_id', 'briefing', 'structured_data',
        'status', 'assumed_by', 'assumed_at', 'resolved_at', 'resolution_note',
    ]))->toBeTrue();
});

it('createHandoff gera briefing via DeepSeek e persiste com dados estruturados', function () {
    bhMockDeepSeekJson();
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    WhatsappMessage::create(['chat_id' => $chat->id, 'message_id' => 'x1', 'content' => 'Oi, sou da ONG Alfa', 'direction' => 'inbound', 'type' => 'text']);
    WhatsappMessage::create(['chat_id' => $chat->id, 'message_id' => 'x2', 'content' => 'Legal! Me conta mais', 'direction' => 'outbound', 'type' => 'text']);

    /** @var BrunoHandoffService $svc */
    $svc = app(BrunoHandoffService::class);
    $handoff = $svc->createHandoff($chat);

    expect($handoff)->toBeInstanceOf(BrunoHandoff::class);
    expect($handoff->status)->toBe(BrunoHandoff::STATUS_PENDING);
    expect($handoff->tenant_id)->toBe($tenant->id);
    expect($handoff->briefing)->toContain('ONG Alfa');
    expect($handoff->structured_data['organizacao'])->toBe('ONG Alfa');
    expect($handoff->structured_data['fase_funil'])->toBe('proposta');
});

it('createHandoff eh idempotente — nao duplica pra mesmo chat pendente', function () {
    bhMockDeepSeekJson();
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    WhatsappMessage::create(['chat_id' => $chat->id, 'message_id' => 'x1', 'content' => 'oi', 'direction' => 'inbound', 'type' => 'text']);

    /** @var BrunoHandoffService $svc */
    $svc = app(BrunoHandoffService::class);
    $h1 = $svc->createHandoff($chat);
    $h2 = $svc->createHandoff($chat);

    expect($h2->id)->toBe($h1->id);
    expect(BrunoHandoff::where('whatsapp_chat_id', $chat->id)->count())->toBe(1);
});

it('createHandoff salva fallback quando IA falha', function () {
    // sem SystemSetting.deepseek_api_key -> retorna error dict -> throw -> fallback
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    WhatsappMessage::create(['chat_id' => $chat->id, 'message_id' => 'x1', 'content' => 'oi', 'direction' => 'inbound', 'type' => 'text']);

    /** @var BrunoHandoffService $svc */
    $svc = app(BrunoHandoffService::class);
    $handoff = $svc->createHandoff($chat, 'Teste sem IA');

    expect($handoff)->toBeInstanceOf(BrunoHandoff::class);
    expect($handoff->briefing)->toContain('Teste sem IA');
    expect($handoff->structured_data)->toBe([]);
});

it('assume marca status assumido + assumed_by/at', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $chat = bhMakeChat($tenant);
    $handoff = BrunoHandoff::create([
        'tenant_id' => $tenant->id,
        'whatsapp_chat_id' => $chat->id,
        'briefing' => 'test',
        'structured_data' => [],
        'status' => BrunoHandoff::STATUS_PENDING,
    ]);

    $svc = app(BrunoHandoffService::class);
    $updated = $svc->assume($handoff, $user->id);

    expect($updated->status)->toBe(BrunoHandoff::STATUS_ASSUMED);
    expect((int) $updated->assumed_by)->toBe((int) $user->id);
    expect($updated->assumed_at)->not->toBeNull();
});

it('resolve marca resolvido + salva nota', function () {
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    $handoff = BrunoHandoff::create([
        'tenant_id' => $tenant->id,
        'whatsapp_chat_id' => $chat->id,
        'briefing' => 'test',
        'structured_data' => [],
        'status' => BrunoHandoff::STATUS_ASSUMED,
    ]);

    $svc = app(BrunoHandoffService::class);
    $updated = $svc->resolve($handoff, 'Fechou plano anual');

    expect($updated->status)->toBe(BrunoHandoff::STATUS_RESOLVED);
    expect($updated->resolution_note)->toBe('Fechou plano anual');
    expect($updated->resolved_at)->not->toBeNull();
});

it('GET /admin/bruno/handoffs renderiza pra super_admin', function () {
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    BrunoHandoff::create([
        'tenant_id' => $tenant->id,
        'whatsapp_chat_id' => $chat->id,
        'briefing' => 'Briefing de teste — ONG Teste em SP',
        'structured_data' => ['organizacao' => 'ONG Teste', 'urgencia' => 'alta'],
        'status' => BrunoHandoff::STATUS_PENDING,
    ]);

    $admin = bhSuperAdmin();
    $res = bhAsSuperAdmin($admin)->get('/admin/bruno/handoffs');

    $res->assertOk();
    $res->assertSee('Handoffs do Bruno');
    $res->assertSee('Briefing de teste');
    $res->assertSee('ONG Teste');
});

it('GET /admin/bruno/handoffs filtra por status', function () {
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    BrunoHandoff::create([
        'tenant_id' => $tenant->id, 'whatsapp_chat_id' => $chat->id,
        'briefing' => 'PENDENTE-XYZ', 'structured_data' => [],
        'status' => BrunoHandoff::STATUS_PENDING,
    ]);
    BrunoHandoff::create([
        'tenant_id' => $tenant->id, 'whatsapp_chat_id' => $chat->id,
        'briefing' => 'RESOLVIDO-XYZ', 'structured_data' => [],
        'status' => BrunoHandoff::STATUS_RESOLVED,
    ]);

    $admin = bhSuperAdmin();

    $pendResp = bhAsSuperAdmin($admin)->get('/admin/bruno/handoffs?status=pendente');
    $pendResp->assertOk();
    $pendResp->assertSee('PENDENTE-XYZ');
    $pendResp->assertDontSee('RESOLVIDO-XYZ');

    $resResp = bhAsSuperAdmin($admin)->get('/admin/bruno/handoffs?status=resolvido');
    $resResp->assertOk();
    $resResp->assertSee('RESOLVIDO-XYZ');
    $resResp->assertDontSee('PENDENTE-XYZ');
});

it('POST /admin/bruno/handoffs/{h}/assume muda status', function () {
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    $handoff = BrunoHandoff::create([
        'tenant_id' => $tenant->id, 'whatsapp_chat_id' => $chat->id,
        'briefing' => 't', 'structured_data' => [],
        'status' => BrunoHandoff::STATUS_PENDING,
    ]);

    $admin = bhSuperAdmin();
    $res = bhAsSuperAdmin($admin)->post("/admin/bruno/handoffs/{$handoff->id}/assume");

    $res->assertRedirect();
    $handoff->refresh();
    expect($handoff->status)->toBe(BrunoHandoff::STATUS_ASSUMED);
    expect((int) $handoff->assumed_by)->toBe((int) $admin->id);
});

it('POST assume em handoff nao-pendente eh bloqueado com flash', function () {
    $tenant = Tenant::factory()->create();
    $chat = bhMakeChat($tenant);
    $handoff = BrunoHandoff::create([
        'tenant_id' => $tenant->id, 'whatsapp_chat_id' => $chat->id,
        'briefing' => 't', 'structured_data' => [],
        'status' => BrunoHandoff::STATUS_RESOLVED,
    ]);

    $admin = bhSuperAdmin();
    $res = bhAsSuperAdmin($admin)->post("/admin/bruno/handoffs/{$handoff->id}/assume");

    $res->assertSessionHas('error');
    $handoff->refresh();
    expect($handoff->status)->toBe(BrunoHandoff::STATUS_RESOLVED);
});

it('nao super_admin (role ngo) recebe 403 em /admin/bruno/handoffs', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $res = test()->actingAs($user)->get('/admin/bruno/handoffs');
    $res->assertForbidden();
});
