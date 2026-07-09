<?php

use App\Jobs\ProcessCloudApiWebhook;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\CloudApiTemplateService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    SystemSetting::setValue('meta_cloud_verify_token', 'test-verify-token');
    SystemSetting::setValue('meta_cloud_app_secret',   'test-app-secret-32c');

    $this->tenant   = Tenant::factory()->create();
    $this->user     = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'common']);
    $this->instance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $this->tenant->id]);
});

// ── Service: syncFromMeta ─────────────────────────────────────────────────────

it('syncFromMeta baixa templates da Graph API e cria WhatsappTemplate', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates*' => Http::response([
            'data' => [
                [
                    'id'         => '111',
                    'name'       => 'order_ready',
                    'language'   => 'pt_BR',
                    'category'   => 'UTILITY',
                    'status'     => 'APPROVED',
                    'components' => [['type' => 'BODY', 'text' => 'Seu pedido está pronto']],
                ],
                [
                    'id'         => '222',
                    'name'       => 'promo_black_friday',
                    'language'   => 'pt_BR',
                    'category'   => 'MARKETING',
                    'status'     => 'REJECTED',
                    'rejected_reason' => 'PROMOTIONAL',
                    'components' => [['type' => 'BODY', 'text' => 'Aproveite 50% OFF']],
                ],
            ],
        ], 200),
    ]);

    $service = new CloudApiTemplateService();
    $count = $service->syncFromMeta($this->instance);

    expect($count)->toBe(2);
    expect(WhatsappTemplate::where('tenant_id', $this->tenant->id)->count())->toBe(2);

    $t = WhatsappTemplate::where('name', 'order_ready')->first();
    expect($t->status)->toBe('APPROVED');
    expect($t->waba_id)->toBe($this->instance->waba_id);

    $rejected = WhatsappTemplate::where('name', 'promo_black_friday')->first();
    expect($rejected->status)->toBe('REJECTED');
    expect($rejected->rejection_reason)->toBe('PROMOTIONAL');
});

it('syncFromMeta e idempotente (segunda chamada nao duplica)', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates*' => Http::response([
            'data' => [['id' => '1', 'name' => 'tpl', 'language' => 'pt_BR', 'category' => 'UTILITY', 'status' => 'APPROVED', 'components' => []]],
        ], 200),
    ]);

    (new CloudApiTemplateService())->syncFromMeta($this->instance);
    (new CloudApiTemplateService())->syncFromMeta($this->instance);
    (new CloudApiTemplateService())->syncFromMeta($this->instance);

    expect(WhatsappTemplate::count())->toBe(1);
});

it('syncFromMeta joga RuntimeException quando Graph API falha', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates*' => Http::response(['error' => ['message' => 'Token invalido']], 400),
    ]);

    expect(fn () => (new CloudApiTemplateService())->syncFromMeta($this->instance))
        ->toThrow(RuntimeException::class);
});

it('syncFromMeta rejeita instancia Evolution', function () {
    $evo = WhatsappInstance::factory()->create(['tenant_id' => $this->tenant->id]);

    expect(fn () => (new CloudApiTemplateService())->syncFromMeta($evo))
        ->toThrow(RuntimeException::class, 'não é Cloud API');
});

// ── Service: create ───────────────────────────────────────────────────────────

it('create POSTa template na Meta e cria WhatsappTemplate com status inicial', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates' => Http::response([
            'id'     => '999888777',
            'status' => 'PENDING',
        ], 200),
    ]);

    $tpl = (new CloudApiTemplateService())->create($this->instance, [
        'name'     => 'novo_template',
        'language' => 'pt_BR',
        'category' => 'UTILITY',
        'components' => [
            ['type' => 'BODY', 'text' => 'Ola {{1}}, novo template.'],
        ],
    ]);

    expect($tpl->name)->toBe('novo_template');
    expect($tpl->status)->toBe('PENDING');
    expect($tpl->meta_template_id)->toBe('999888777');
    expect((int) $tpl->tenant_id)->toBe($this->tenant->id);

    Http::assertSent(function ($req) {
        return $req->method() === 'POST'
            && ($req->data()['name'] ?? null) === 'novo_template'
            && ($req->data()['category'] ?? null) === 'UTILITY';
    });
});

it('create propaga erro da Meta como RuntimeException', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates' => Http::response([
            'error' => ['message' => 'Template name already exists'],
        ], 400),
    ]);

    expect(fn () => (new CloudApiTemplateService())->create($this->instance, [
        'name' => 'x', 'language' => 'pt_BR', 'category' => 'UTILITY', 'components' => [],
    ]))->toThrow(RuntimeException::class, 'Template name already exists');
});

// ── Service: applyStatusUpdate (webhook path) ─────────────────────────────────

it('applyStatusUpdate atualiza status e rejection_reason', function () {
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'whatsapp_instance_id' => $this->instance->id,
        'waba_id'              => $this->instance->waba_id,
        'meta_template_id'     => '555',
        'status'               => 'PENDING',
    ]);

    $service = new CloudApiTemplateService();
    $updated = $service->applyStatusUpdate($this->instance->waba_id, '555', 'REJECTED', 'INVALID_FORMAT');

    expect($updated)->not->toBeNull();
    expect($tpl->fresh()->status)->toBe('REJECTED');
    expect($tpl->fresh()->rejection_reason)->toBe('INVALID_FORMAT');
});

it('applyStatusUpdate retorna null se template desconhecido', function () {
    $result = (new CloudApiTemplateService())->applyStatusUpdate('unknown_waba', '000', 'APPROVED');
    expect($result)->toBeNull();
});

// ── Webhook integration ───────────────────────────────────────────────────────

it('ProcessCloudApiWebhook processa message_template_status_update', function () {
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'whatsapp_instance_id' => $this->instance->id,
        'waba_id'              => $this->instance->waba_id,
        'meta_template_id'     => 'META_TPL_123',
        'status'               => 'PENDING',
    ]);

    $payload = [
        'entry' => [[
            'id' => $this->instance->waba_id,
            'changes' => [[
                'field' => 'message_template_status_update',
                'value' => [
                    'event'               => 'APPROVED',
                    'message_template_id' => 'META_TPL_123',
                    'message_template_name'     => $tpl->name,
                    'message_template_language' => $tpl->language,
                    'reason'              => null,
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();

    expect($tpl->fresh()->status)->toBe('APPROVED');
});

// ── Controller endpoints ──────────────────────────────────────────────────────

it('GET /whatsapp/cloud/templates lista templates do tenant', function () {
    WhatsappTemplate::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'whatsapp_instance_id' => $this->instance->id,
        'waba_id'              => $this->instance->waba_id,
        'name'                 => 'meu_template_visivel',
    ]);

    $this->actingAs($this->user)
        ->get('/whatsapp/cloud/templates')
        ->assertStatus(200)
        ->assertSee('meu_template_visivel');
});

it('GET /whatsapp/cloud/templates NAO mostra templates de outro tenant (multi-tenancy)', function () {
    $outroTenant = Tenant::factory()->create();
    $outroInstance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $outroTenant->id]);
    WhatsappTemplate::factory()->create([
        'tenant_id'            => $outroTenant->id,
        'whatsapp_instance_id' => $outroInstance->id,
        'waba_id'              => $outroInstance->waba_id,
        'name'                 => 'nao_deveria_aparecer',
    ]);

    $this->actingAs($this->user)
        ->get('/whatsapp/cloud/templates')
        ->assertStatus(200)
        ->assertDontSee('nao_deveria_aparecer');
});

it('POST /whatsapp/cloud/templates cria template', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates' => Http::response(['id' => 'abc', 'status' => 'PENDING'], 200),
    ]);

    $response = $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'test_template',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Olá {{1}}',
    ]);

    $response->assertRedirect(route('whatsapp.templates.cloud.index'));
    expect(WhatsappTemplate::where('name', 'test_template')->count())->toBe(1);
});

it('POST /whatsapp/cloud/templates rejeita nome com maiusculas ou espacos', function () {
    $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'Nome Errado',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Ola',
    ])->assertSessionHasErrors('name');
});

it('DELETE /whatsapp/cloud/templates/{id} bloqueia acesso de outro tenant (IDOR)', function () {
    $outroTenant = Tenant::factory()->create();
    $outroInstance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $outroTenant->id]);
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $outroTenant->id,
        'whatsapp_instance_id' => $outroInstance->id,
        'waba_id'              => $outroInstance->waba_id,
    ]);

    $this->actingAs($this->user)
        ->delete("/whatsapp/cloud/templates/{$tpl->id}")
        ->assertStatus(404);

    expect(WhatsappTemplate::find($tpl->id))->not->toBeNull(); // Nao foi deletado
});

it('GET /whatsapp/cloud/templates/create redireciona se tenant nao tem instancia Cloud', function () {
    $outroTenant = Tenant::factory()->create();
    $outroUser   = User::factory()->create(['tenant_id' => $outroTenant->id, 'role' => 'common']);

    $this->actingAs($outroUser)
        ->get('/whatsapp/cloud/templates/create')
        ->assertRedirect(route('whatsapp.templates.cloud.index'));
});
