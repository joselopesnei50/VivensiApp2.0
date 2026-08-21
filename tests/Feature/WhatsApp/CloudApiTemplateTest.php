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

    // 2026-08-21: agora o corpo com {{1}} exige variable_samples ({{1}}=>algo)
    // porque a Meta rejeita templates com variavel sem example.body_text.
    $response = $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'test_template',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Olá {{1}}, tudo bem?',
        'variable_samples'     => [1 => 'Maria'],
    ]);

    $response->assertRedirect(route('whatsapp.templates.cloud.index'));
    expect(WhatsappTemplate::where('name', 'test_template')->count())->toBe(1);
});

// ── Service: extractVariables / validateBodyAndSamples / buildBodyComponent ──
// Suite adicionada 2026-08-21 pra travar o fix de example.body_text (bug prod:
// todo template com variavel era rejeitado pela Meta com "Variaveis de modelo
// sem texto de amostra").

it('extractVariables retorna array vazio para corpo sem variavel', function () {
    $s = new CloudApiTemplateService();
    expect($s->extractVariables('Olá tudo bem?'))->toBe([]);
});

it('extractVariables extrai variaveis em ordem crescente sem duplicatas', function () {
    $s = new CloudApiTemplateService();
    // {{2}} aparece antes de {{1}}, {{2}} aparece duas vezes — deve virar [1, 2]
    expect($s->extractVariables('Ei {{2}}, o {{1}} chegou. Obrigado {{2}}!'))->toBe([1, 2]);
});

it('validateBodyAndSamples aceita corpo sem variavel e sem samples', function () {
    $s = new CloudApiTemplateService();
    expect($s->validateBodyAndSamples('Ola tudo bem?', [], []))->toBe([]);
});

it('validateBodyAndSamples bloqueia corpo que comeca com variavel', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('{{1}}, ola', [1], [1 => 'Maria']);
    expect($errors)->toHaveKey('body');
    expect($errors['body'])->toContain('comecar');
});

it('validateBodyAndSamples bloqueia corpo que termina com variavel', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('Ola {{1}}', [1], [1 => 'Maria']);
    expect($errors)->toHaveKey('body');
    expect($errors['body'])->toContain('terminar');
});

it('validateBodyAndSamples bloqueia variaveis adjacentes {{1}}{{2}}', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('ola {{1}}{{2}} mundo', [1, 2], [1 => 'a', 2 => 'b']);
    expect($errors)->toHaveKey('body');
    expect($errors['body'])->toContain('coladas');
});

it('validateBodyAndSamples bloqueia lacuna nas variaveis (1 e 3 sem 2)', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('ola {{1}} tudo {{3}} bem', [1, 3], [1 => 'a', 3 => 'c']);
    expect($errors)->toHaveKey('body');
    expect($errors['body'])->toContain('sequenciais');
});

it('validateBodyAndSamples bloqueia amostra vazia', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('ola {{1}} mundo', [1], [1 => '']);
    expect($errors)->toHaveKey('variable_samples.1');
});

it('validateBodyAndSamples bloqueia amostra so com espacos', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('ola {{1}} mundo', [1], [1 => '   ']);
    expect($errors)->toHaveKey('variable_samples.1');
});

it('validateBodyAndSamples bloqueia amostra com quebra de linha', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('ola {{1}} mundo', [1], [1 => "com\nquebra"]);
    expect($errors)->toHaveKey('variable_samples.1');
    expect($errors['variable_samples.1'])->toContain('quebras');
});

it('validateBodyAndSamples bloqueia amostra com 5 espacos consecutivos', function () {
    $s = new CloudApiTemplateService();
    $errors = $s->validateBodyAndSamples('ola {{1}} mundo', [1], [1 => 'a     b']);
    expect($errors)->toHaveKey('variable_samples.1');
});

it('buildBodyComponent SEM example quando corpo nao tem variavel', function () {
    $s = new CloudApiTemplateService();
    $c = $s->buildBodyComponent('Ola tudo bem?', []);
    expect($c)->toBe([
        'type' => 'BODY',
        'text' => 'Ola tudo bem?',
    ]);
    expect($c)->not->toHaveKey('example');
});

it('buildBodyComponent COM example.body_text quando corpo tem variavel', function () {
    $s = new CloudApiTemplateService();
    $c = $s->buildBodyComponent('Olá {{1}}, tudo bem?', [1 => 'Maria']);
    expect($c)->toBe([
        'type'    => 'BODY',
        'text'    => 'Olá {{1}}, tudo bem?',
        'example' => ['body_text' => [['Maria']]],
    ]);
});

it('buildBodyComponent ordena samples pela sequencia das variaveis', function () {
    $s = new CloudApiTemplateService();
    // Passa samples fora de ordem — deve ser reordenado por [1, 2]
    $c = $s->buildBodyComponent('Ola {{1}} da {{2}}!', [2 => 'Araraquara', 1 => 'Instituto']);
    expect($c['example']['body_text'])->toBe([['Instituto', 'Araraquara']]);
});

it('service create envia example.body_text no payload da Graph API quando ha variavel', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates' => Http::response(['id' => 'x', 'status' => 'PENDING'], 200),
    ]);

    $service = new CloudApiTemplateService();
    $body = 'Olá {{1}}, seu pedido {{2}} está pronto.';
    $samples = [1 => 'Maria', 2 => '#42'];

    $service->create($this->instance, [
        'name'             => 'ex_1',
        'language'         => 'pt_BR',
        'category'         => 'UTILITY',
        'components'       => [$service->buildBodyComponent($body, $samples)],
        'variable_samples' => $samples,
    ]);

    Http::assertSent(function ($req) {
        $body = $req->data()['components'][0] ?? [];
        return ($body['type'] ?? null) === 'BODY'
            && ($body['example']['body_text'] ?? null) === [['Maria', '#42']];
    });
});

it('service create formata error_user_title + error_user_msg no exception', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates' => Http::response([
            'error' => [
                'message'           => 'raw error',
                'error_user_title'  => 'Template invalido',
                'error_user_msg'    => 'A variavel {{1}} nao tem exemplo.',
            ],
        ], 400),
    ]);

    expect(fn () => (new CloudApiTemplateService())->create($this->instance, [
        'name' => 'x', 'language' => 'pt_BR', 'category' => 'UTILITY', 'components' => [],
    ]))->toThrow(RuntimeException::class, 'Template invalido');
});

// ── Controller: variable_samples end-to-end ───────────────────────────────────

it('POST /whatsapp/cloud/templates persiste variable_samples no banco', function () {
    Http::fake([
        'graph.facebook.com/*/message_templates' => Http::response(['id' => 'abc', 'status' => 'PENDING'], 200),
    ]);

    $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'tpl_samples',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Olá {{1}}, aqui é da {{2}}. Tudo bem?',
        'variable_samples'     => [1 => 'Maria', 2 => 'Vivensi'],
    ]);

    $tpl = WhatsappTemplate::where('name', 'tpl_samples')->first();
    expect($tpl)->not->toBeNull();
    expect($tpl->variable_samples)->toBe([1 => 'Maria', 2 => 'Vivensi']);
});

it('POST /whatsapp/cloud/templates rejeita quando body tem variavel sem sample', function () {
    $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'no_sample',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Olá {{1}}, tudo bem?',
        // sem variable_samples
    ])->assertSessionHasErrors('variable_samples.1');

    expect(WhatsappTemplate::where('name', 'no_sample')->count())->toBe(0);
});

it('POST /whatsapp/cloud/templates bloqueia sample com quebra de linha', function () {
    $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'bad_sample',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Olá {{1}}, tudo bem?',
        'variable_samples'     => [1 => "com\nquebra"],
    ])->assertSessionHasErrors('variable_samples.1');

    expect(WhatsappTemplate::where('name', 'bad_sample')->count())->toBe(0);
});

it('POST /whatsapp/cloud/templates bloqueia lacuna 1..3 sem chamar Graph API', function () {
    Http::fake();

    $this->actingAs($this->user)->post('/whatsapp/cloud/templates', [
        'whatsapp_instance_id' => $this->instance->id,
        'name'                 => 'lacuna',
        'language'             => 'pt_BR',
        'category'             => 'UTILITY',
        'body'                 => 'Ola {{1}} e depois {{3}} certo?',
        'variable_samples'     => [1 => 'a', 3 => 'c'],
    ])->assertSessionHasErrors('body');

    Http::assertNothingSent();
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

it('GET /whatsapp/cloud/templates/create renderiza view com sucesso quando ha instancia', function () {
    $this->actingAs($this->user)
        ->get('/whatsapp/cloud/templates/create')
        ->assertStatus(200)
        ->assertSee('Novo template')
        ->assertSee('Corpo da mensagem');
});

// ── sendTest endpoint ─────────────────────────────────────────────────────────

it('POST send-test envia template APPROVED e retorna JSON com provider_message_id', function () {
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'whatsapp_instance_id' => $this->instance->id,
        'waba_id'              => $this->instance->waba_id,
        'status'               => 'APPROVED',
    ]);

    Http::fake([
        'graph.facebook.com/*/messages' => Http::response([
            'messages' => [['id' => 'wamid.TESTE_ABC']],
        ], 200),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/whatsapp/cloud/templates/{$tpl->id}/send-test", [
            'to'        => '+5511987654321',
            'variables' => ['João'],
        ]);

    $response->assertStatus(200)
        ->assertJson(['ok' => true, 'provider' => 'cloud_api', 'provider_message_id' => 'wamid.TESTE_ABC']);
});

it('POST send-test rejeita template com status PENDING (422)', function () {
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'whatsapp_instance_id' => $this->instance->id,
        'waba_id'              => $this->instance->waba_id,
        'status'               => 'PENDING',
    ]);

    $this->actingAs($this->user)
        ->postJson("/whatsapp/cloud/templates/{$tpl->id}/send-test", [
            'to' => '+5511987654321',
        ])
        ->assertStatus(422)
        ->assertJson(['ok' => false]);
});

it('POST send-test bloqueia acesso de outro tenant (IDOR, retorna 404)', function () {
    $outroTenant = Tenant::factory()->create();
    $outroInstance = WhatsappInstance::factory()->cloudApi()->create(['tenant_id' => $outroTenant->id]);
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $outroTenant->id,
        'whatsapp_instance_id' => $outroInstance->id,
        'waba_id'              => $outroInstance->waba_id,
        'status'               => 'APPROVED',
    ]);

    $this->actingAs($this->user)
        ->postJson("/whatsapp/cloud/templates/{$tpl->id}/send-test", ['to' => '+5511987654321'])
        ->assertStatus(404);
});

it('POST send-test valida formato E.164 do telefone', function () {
    $tpl = WhatsappTemplate::factory()->create([
        'tenant_id'            => $this->tenant->id,
        'whatsapp_instance_id' => $this->instance->id,
        'waba_id'              => $this->instance->waba_id,
        'status'               => 'APPROVED',
    ]);

    foreach (['abc', '123', '+55abc12345', '+55123'] as $badPhone) {
        $this->actingAs($this->user)
            ->postJson("/whatsapp/cloud/templates/{$tpl->id}/send-test", ['to' => $badPhone])
            ->assertStatus(422);
    }
});
