<?php

use App\Jobs\ProcessBroadcastCampaignJob;
use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Models\WhatsappTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

/**
 * Broadcast v1 via Template Cloud API (2026-08-21).
 *
 * Coexiste com o fluxo Evolution (texto/imagem/audio livre). Regras:
 *  - audience: apenas 'all' ou 'labels'
 *  - imagem/audio bloqueados
 *  - template deve ser do mesmo tenant + status APPROVED
 *  - variaveis fixas por campanha, indexadas por numero
 *  - IDOR: template_id de outro tenant devolve 404
 *  - Job chama sender->sendTemplate quando send_channel=cloud_api_template
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bctManager(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

function bctMakeTemplate(int $tenantId, string $body = 'Olá {{1}}, aqui é a {{2}}!'): WhatsappTemplate
{
    $instance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $tenantId]);
    return WhatsappTemplate::factory()->create([
        'tenant_id'            => $tenantId,
        'whatsapp_instance_id' => $instance->id,
        'waba_id'              => $instance->waba_id,
        'status'               => WhatsappTemplate::STATUS_APPROVED,
        'language'             => 'pt_BR',
        'components'           => [['type' => 'BODY', 'text' => $body]],
    ]);
}

// ── Migration ────────────────────────────────────────────────────────────────

it('migration adiciona send_channel, template_id, template_variables', function () {
    expect(Schema::hasColumn('broadcast_campaigns', 'send_channel'))->toBeTrue();
    expect(Schema::hasColumn('broadcast_campaigns', 'template_id'))->toBeTrue();
    expect(Schema::hasColumn('broadcast_campaigns', 'template_variables'))->toBeTrue();
});

// ── Controller: POST /whatsapp/broadcast ─────────────────────────────────────

it('cria BroadcastCampaign com template_id preenchido quando send_channel=cloud_api_template', function () {
    Queue::fake();
    $user = bctManager();
    $tpl  = bctMakeTemplate($user->tenant_id, 'Olá {{1}} da {{2}}!');

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'             => 'all',
        'send_channel'         => 'cloud_api_template',
        'template_id'          => $tpl->id,
        'template_variables'   => [1 => 'Maria', 2 => 'Vivensi'],
    ]);

    $resp->assertSessionHasNoErrors();

    $camp = BroadcastCampaign::where('tenant_id', $user->tenant_id)->first();
    expect($camp)->not->toBeNull();
    expect($camp->send_channel)->toBe('cloud_api_template');
    expect((int) $camp->template_id)->toBe($tpl->id);
    expect($camp->template_variables)->toBe(['1' => 'Maria', '2' => 'Vivensi']);
});

it('rejeita audience=selected quando send_channel=cloud_api_template', function () {
    $user = bctManager();
    $tpl  = bctMakeTemplate($user->tenant_id);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'           => 'selected',
        'phones'             => '5511999990001',
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tpl->id,
        'template_variables' => [1 => 'Maria', 2 => 'Vivensi'],
    ]);

    $resp->assertRedirect('/whatsapp/broadcast');
    expect(session('error'))->toContain('template Cloud API');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('rejeita audience=groups quando send_channel=cloud_api_template', function () {
    $user = bctManager();
    $tpl  = bctMakeTemplate($user->tenant_id);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'           => 'groups',
        'group_ids'          => ['1@g.us'],
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tpl->id,
        'template_variables' => [1 => 'Maria', 2 => 'Vivensi'],
    ]);

    $resp->assertRedirect('/whatsapp/broadcast');
    expect(session('error'))->toContain('template Cloud API');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('rejeita imagem anexada quando send_channel=cloud_api_template', function () {
    $user = bctManager();
    $tpl  = bctMakeTemplate($user->tenant_id);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'           => 'all',
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tpl->id,
        'template_variables' => [1 => 'Maria', 2 => 'Vivensi'],
        'broadcast_image'    => UploadedFile::fake()->create('foto.jpg', 8, 'image/jpeg'),
    ]);

    $resp->assertRedirect('/whatsapp/broadcast');
    expect(session('error'))->toContain('não suporta anexo');
    expect(BroadcastCampaign::count())->toBe(0);
});

it('rejeita template_id de outro tenant (IDOR devolve 404)', function () {
    $user = bctManager();

    $outroTenant  = Tenant::factory()->create();
    $tplOutro     = bctMakeTemplate($outroTenant->id);

    // instancia Cloud do proprio user (sem ela o controller aborta antes)
    WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $user->tenant_id]);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'           => 'all',
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tplOutro->id,
        'template_variables' => [1 => 'Maria', 2 => 'Vivensi'],
    ]);

    $resp->assertNotFound();
    expect(BroadcastCampaign::count())->toBe(0);
});

it('rejeita template com status PENDING (nao-APPROVED)', function () {
    $user     = bctManager();
    $instance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $user->tenant_id]);
    $tpl      = WhatsappTemplate::factory()->create([
        'tenant_id'            => $user->tenant_id,
        'whatsapp_instance_id' => $instance->id,
        'waba_id'              => $instance->waba_id,
        'status'               => WhatsappTemplate::STATUS_PENDING,
        'components'           => [['type' => 'BODY', 'text' => 'Ola {{1}}!']],
    ]);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'           => 'all',
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tpl->id,
        'template_variables' => [1 => 'Maria'],
    ]);

    $resp->assertNotFound();
    expect(BroadcastCampaign::count())->toBe(0);
});

it('rejeita quando variaveis do form nao batem com corpo do template', function () {
    $user = bctManager();
    $tpl  = bctMakeTemplate($user->tenant_id, 'Olá {{1}} da {{2}}!');

    // Passa so a variavel 1 — variavel 2 faltando cai no validateBodyAndSamples
    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'audience'           => 'all',
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tpl->id,
        'template_variables' => [1 => 'Maria'],
    ]);

    $resp->assertSessionHasErrors('variable_samples.2');
    expect(BroadcastCampaign::count())->toBe(0);
});

// ── Job: envio real ──────────────────────────────────────────────────────────

it('job chama sendTemplate quando send_channel=cloud_api_template', function () {
    Http::fake([
        'graph.facebook.com/*/messages' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.BROADCAST_XYZ']],
        ], 200),
    ]);

    $tenant   = Tenant::factory()->create(['subscription_status' => 'active']);
    $instance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $tenant->id]);
    $tpl      = WhatsappTemplate::factory()->create([
        'tenant_id'            => $tenant->id,
        'whatsapp_instance_id' => $instance->id,
        'waba_id'              => $instance->waba_id,
        'status'               => WhatsappTemplate::STATUS_APPROVED,
        'name'                 => 'welcome_test',
        'language'             => 'pt_BR',
        'components'           => [['type' => 'BODY', 'text' => 'Olá {{1}} da {{2}}!']],
    ]);

    // Cria destinatario com opt-in (senao o job pula por compliance)
    WhatsappChat::factory()->create([
        'tenant_id'  => $tenant->id,
        'wa_id'      => '5511999990001',
        'opt_in_at'  => now()->subDay(),
    ]);

    $campaign = BroadcastCampaign::create([
        'tenant_id'          => $tenant->id,
        'audience_type'      => 'all',
        'status'             => 'queued',
        'send_channel'       => 'cloud_api_template',
        'template_id'        => $tpl->id,
        'template_variables' => ['1' => 'Instituto', '2' => 'Vivensi'],
        'cadence'            => 1,
    ]);

    (new ProcessBroadcastCampaignJob($campaign->id, $tenant->id))->handle();

    Http::assertSent(function ($req) use ($tpl) {
        if (!str_contains((string) $req->url(), '/messages')) return false;
        $data = $req->data();
        if (($data['type'] ?? null) !== 'template') return false;
        if (($data['template']['name'] ?? null) !== $tpl->name) return false;
        if (($data['template']['language']['code'] ?? null) !== 'pt_BR') return false;

        // Confere que as variaveis do body vao na ordem [Instituto, Vivensi]
        $bodyComp = collect($data['template']['components'] ?? [])
            ->firstWhere('type', 'body');
        $params = collect($bodyComp['parameters'] ?? [])->pluck('text')->all();
        return $params === ['Instituto', 'Vivensi'];
    });

    expect($campaign->fresh()->status)->toBe('completed');
    expect((int) $campaign->fresh()->total_sent)->toBe(1);
});

it('regressao: fluxo evolution (send_channel default) continua funcionando', function () {
    Queue::fake();
    $user = bctManager();
    WhatsappInstance::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'open']);

    $resp = $this->actingAs($user)->from('/whatsapp/broadcast')->post('/whatsapp/broadcast', [
        'message'  => 'Ola pessoal!',
        'audience' => 'all',
    ]);

    $resp->assertSessionHasNoErrors();
    $camp = BroadcastCampaign::first();
    expect($camp)->not->toBeNull();
    expect($camp->send_channel)->toBe('evolution');
    expect($camp->template_id)->toBeNull();
});
