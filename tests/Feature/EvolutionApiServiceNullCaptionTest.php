<?php

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Http;

/**
 * Fix face.md (2026-08-07) — ProcessBroadcastCampaignJob:419 estourava
 * TypeError quando broadcast era so imagem sem message (campaign->message null).
 * sendMedia/sendMessage agora aceitam ?string e coagem pra ''.
 */

it('sendMedia aceita null como caption sem TypeError (broadcast so imagem)', function () {
    Http::fake([
        '*/message/sendMedia/*' => Http::response(['key' => ['id' => 'stub']], 200),
    ]);

    // Instancia stub minima (sem RefreshDatabase — teste unit, so precisa das props).
    $inst = new WhatsappInstance();
    $inst->forceFill([
        'id'            => 1,
        'name'          => 'stub-instance',
        'instance_name' => 'stub-instance',
        'token'         => 'token',
        'api_key'       => 'key',
    ]);

    $evo = new EvolutionApiService($inst);
    $res = $evo->sendMedia('5511999990001', 'https://example.com/img.jpg', null, 'image/jpeg');

    // Antes: TypeError explodia. Agora: retorna array (fake HTTP responde).
    expect($res)->toBeArray();
    expect($res)->not->toHaveKey('error');
});

it('sendMessage retorna erro estruturado quando message e null (nao explode)', function () {
    $inst = new WhatsappInstance();
    $inst->forceFill([
        'id'            => 1,
        'name'          => 'stub-instance',
        'instance_name' => 'stub-instance',
        'token'         => 'token',
        'api_key'       => 'key',
    ]);

    $evo = new EvolutionApiService($inst);
    $res = $evo->sendMessage('5511999990001', null);

    expect($res)->toBeArray();
    expect($res['error'] ?? null)->toBe('Empty message rejected');
});
