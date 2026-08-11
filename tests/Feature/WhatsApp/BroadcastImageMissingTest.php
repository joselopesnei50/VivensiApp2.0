<?php

use App\Jobs\ProcessBroadcastCampaignJob;
use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Fix 2026-08-11: ProcessBroadcastCampaignJob parou de cair em silent fallback
 * pra texto quando has_image=true mas o arquivo nao existe. Antes marcava
 * como sent (mentindo pro cliente); agora marca campaign=failed com log critico.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bimSetup(): array
{
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    $instance = WhatsappInstance::factory()->create([
        'tenant_id' => $tenant->id,
        'status'    => 'open',
    ]);
    return [$tenant, $user, $instance];
}

it('falha explicito se has_image=true mas image_path e NULL', function () {
    [$tenant, $user, $instance] = bimSetup();

    $campaign = BroadcastCampaign::create([
        'tenant_id'     => $tenant->id,
        'created_by'    => $user->id,
        'name'          => 'Bug DB',
        'message'       => 'teste',
        'has_image'     => true,
        'image_path'    => null, // DB inconsistente
        'audience_type' => 'selected',
        'phones'        => '5511999998888',
        'status'        => 'queued',
    ]);

    (new ProcessBroadcastCampaignJob($campaign->id, $tenant->id))->handle();

    $campaign->refresh();
    expect($campaign->status)->toBe('failed');
    expect((int) $campaign->total_sent)->toBe(0);
});

it('falha explicito se arquivo da imagem nao existe no disk public', function () {
    [$tenant, $user, $instance] = bimSetup();
    Storage::fake('public'); // disco limpo — arquivo NAO existe

    $campaign = BroadcastCampaign::create([
        'tenant_id'     => $tenant->id,
        'created_by'    => $user->id,
        'name'          => 'Arquivo sumiu',
        'message'       => 'teste',
        'has_image'     => true,
        'image_path'    => 'broadcasts/broadcast_fantasma.jpg',
        'audience_type' => 'selected',
        'phones'        => '5511999998888',
        'status'        => 'queued',
    ]);

    (new ProcessBroadcastCampaignJob($campaign->id, $tenant->id))->handle();

    $campaign->refresh();
    expect($campaign->status)->toBe('failed');
    expect((int) $campaign->total_sent)->toBe(0);
});

it('nao afeta broadcast SEM imagem (has_image=false continua funcional)', function () {
    [$tenant, $user, $instance] = bimSetup();

    // Fake Evolution: numero validado (retorna map wa_id => jid) + envio OK.
    Http::fake([
        '*/chat/whatsappNumbers/*' => Http::response([
            ['jid' => '5511999998888@s.whatsapp.net', 'exists' => true, 'number' => '5511999998888'],
        ], 200),
        '*/message/sendText/*'  => Http::response(['key' => ['id' => 'MSG_OK']], 200),
        '*'                     => Http::response([], 200),
    ]);

    // Precisa de um chat existente com opt_in porque audience=selected+phones
    // ainda passa por normalizacao e blacklist; broadcast a phone puro tolera.
    $campaign = BroadcastCampaign::create([
        'tenant_id'     => $tenant->id,
        'created_by'    => $user->id,
        'name'          => 'Texto puro',
        'message'       => 'so texto',
        'has_image'     => false,
        'image_path'    => null,
        'audience_type' => 'selected',
        'phones'        => '5511999998888',
        'status'        => 'queued',
    ]);

    (new ProcessBroadcastCampaignJob($campaign->id, $tenant->id))->handle();

    $campaign->refresh();
    // Nao deve marcar failed — mesmo que 0 sent (numero pode nao existir no fake),
    // o importante e que a checagem de imagem nao aborta o job.
    expect($campaign->status)->not->toBe('failed');
});

it('isBanSignal so retorna true para sinais de instancia, nao por-destinatario', function () {
    $evo = Mockery::mock(\App\Services\EvolutionApiService::class);
    $ab  = new AntiBanManager($evo);

    // Instance-level: SIM
    expect($ab->isBanSignal(['error' => 'HTTP 429 Too Many Requests']))->toBeTrue();
    expect($ab->isBanSignal(['error' => 'rate_limit exceeded']))->toBeTrue();
    expect($ab->isBanSignal(['error' => 'account suspended']))->toBeTrue();
    expect($ab->isBanSignal(['error' => 'unauthorized token invalid']))->toBeTrue();

    // Per-recipient: NAO deve restringir instancia
    expect($ab->isBanSignal(['error' => 'recipient blocked us']))->toBeFalse();
    expect($ab->isBanSignal(['error' => 'message flagged as spam by user']))->toBeFalse();
});

it('isRecipientBlockSignal captura os erros por-destinatario', function () {
    $evo = Mockery::mock(\App\Services\EvolutionApiService::class);
    $ab  = new AntiBanManager($evo);

    expect($ab->isRecipientBlockSignal(['error' => 'recipient blocked us']))->toBeTrue();
    expect($ab->isRecipientBlockSignal(['error' => 'reported as spam']))->toBeTrue();
    expect($ab->isRecipientBlockSignal(['error' => 'not-authorized']))->toBeTrue();
    expect($ab->isRecipientBlockSignal(['error' => 'recipient not found']))->toBeTrue();

    // Nao confunde com sinal de instancia
    expect($ab->isRecipientBlockSignal(['error' => 'rate_limit']))->toBeFalse();
});
