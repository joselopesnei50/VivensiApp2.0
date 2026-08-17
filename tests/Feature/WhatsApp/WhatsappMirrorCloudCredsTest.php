<?php

use App\Models\Tenant;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCloudInstanceForMirror(int $tenantId, string $phone): WhatsappInstance
{
    return WhatsappInstance::create([
        'tenant_id'          => $tenantId,
        'provider'           => WhatsappInstance::PROVIDER_CLOUD_API,
        'waba_id'            => 'WABA_' . $phone,
        'phone_number_id'    => $phone,
        'graph_access_token' => 'EAA_TOKEN_' . $phone,
        'instance_name'      => 'cloud_' . $tenantId . '_' . substr($phone, -4),
        'instance_token'     => bin2hex(random_bytes(16)),
        'status'             => 'open',
    ]);
}

it('mirror cria WhatsappConfig quando tenant nao tem um', function () {
    $tenant = Tenant::factory()->create();
    makeCloudInstanceForMirror($tenant->id, '999888777');

    $this->artisan('whatsapp:mirror-cloud-creds')->assertSuccessful();

    $config = WhatsappConfig::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)->first();

    expect($config)->not->toBeNull()
        ->and($config->meta_waba_id)->toBe('WABA_999888777')
        ->and($config->meta_phone_number_id)->toBe('999888777')
        ->and($config->meta_access_token)->toBe('EAA_TOKEN_999888777');
});

it('mirror atualiza sem sobrescrever ai_enabled/ai_training existentes', function () {
    $tenant = Tenant::factory()->create();
    WhatsappConfig::create([
        'tenant_id'   => $tenant->id,
        'is_active'   => true,
        'ai_enabled'  => false, // custom
        'ai_training' => 'Prompt customizado',
    ]);
    makeCloudInstanceForMirror($tenant->id, '111222333');

    $this->artisan('whatsapp:mirror-cloud-creds')->assertSuccessful();

    $config = WhatsappConfig::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)->first();

    expect($config->meta_phone_number_id)->toBe('111222333')
        ->and($config->meta_access_token)->toBe('EAA_TOKEN_111222333')
        ->and((bool) $config->ai_enabled)->toBeFalse()
        ->and($config->ai_training)->toBe('Prompt customizado');
});

it('dry-run nao grava nada', function () {
    $tenant = Tenant::factory()->create();
    makeCloudInstanceForMirror($tenant->id, '000111222');

    $this->artisan('whatsapp:mirror-cloud-creds', ['--dry-run' => true])->assertSuccessful();

    expect(WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists())->toBeFalse();
});

it('mirror --tenant filtra pra um tenant so', function () {
    $t1 = Tenant::factory()->create();
    $t2 = Tenant::factory()->create();
    makeCloudInstanceForMirror($t1->id, '444555666');
    makeCloudInstanceForMirror($t2->id, '777888999');

    $this->artisan('whatsapp:mirror-cloud-creds', ['--tenant' => $t1->id])->assertSuccessful();

    expect(WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $t1->id)->exists())->toBeTrue()
        ->and(WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $t2->id)->exists())->toBeFalse();
});

it('mirror idempotente — rodar 2x nao duplica config', function () {
    $tenant = Tenant::factory()->create();
    makeCloudInstanceForMirror($tenant->id, '333444555');

    $this->artisan('whatsapp:mirror-cloud-creds')->assertSuccessful();
    $this->artisan('whatsapp:mirror-cloud-creds')->assertSuccessful();

    expect(WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(1);
});
