<?php

use App\Jobs\ProcessCloudApiWebhook;
use App\Models\SystemSetting;
use App\Models\WhatsappChat;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->verifyToken = 'vivensi-verify-token-super-secret';
    $this->appSecret   = 'nc5hubdigital-emp-app-secret-32ch';

    SystemSetting::setValue('meta_cloud_verify_token', $this->verifyToken);
    SystemSetting::setValue('meta_cloud_app_secret',   $this->appSecret);
});

/**
 * Assina um raw body como Meta faria (sha256=hex(hmac_sha256(body, app_secret))).
 */
function metaSign(string $body, string $secret): string
{
    return 'sha256=' . hash_hmac('sha256', $body, $secret);
}

// ── GET verify ────────────────────────────────────────────────────────────────

it('GET retorna hub.challenge quando verify_token bate', function () {
    $challenge = 'random_challenge_1234';

    $this->get("/api/whatsapp/cloud-webhook?hub_mode=subscribe&hub_verify_token={$this->verifyToken}&hub_challenge={$challenge}")
        ->assertStatus(200)
        ->assertSee($challenge);
});

it('GET retorna 403 quando verify_token nao bate', function () {
    $this->get('/api/whatsapp/cloud-webhook?hub_mode=subscribe&hub_verify_token=INVALID&hub_challenge=abc')
        ->assertStatus(403);
});

it('GET retorna 403 quando hub_mode nao e subscribe', function () {
    $this->get("/api/whatsapp/cloud-webhook?hub_mode=unsubscribe&hub_verify_token={$this->verifyToken}&hub_challenge=abc")
        ->assertStatus(403);
});

// ── POST signature validation ─────────────────────────────────────────────────

it('POST retorna 401 sem X-Hub-Signature-256', function () {
    $this->postJson('/api/whatsapp/cloud-webhook', ['object' => 'whatsapp_business_account'])
        ->assertStatus(401);
});

it('POST retorna 401 com assinatura HMAC invalida', function () {
    $body = json_encode(['object' => 'whatsapp_business_account']);

    $this->call(
        'POST', '/api/whatsapp/cloud-webhook',
        [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=forjado_e_totalmente_errado'],
        $body
    )->assertStatus(401);
});

it('POST retorna 503 quando meta_cloud_app_secret nao configurado', function () {
    SystemSetting::setValue('meta_cloud_app_secret', '');

    $this->postJson('/api/whatsapp/cloud-webhook', [])
        ->assertStatus(503);
});

// ── POST valid signature → dispatch ───────────────────────────────────────────

it('POST valido despacha ProcessCloudApiWebhook', function () {
    Bus::fake();

    $payload = ['object' => 'whatsapp_business_account', 'entry' => []];
    $body    = json_encode($payload);

    $this->call(
        'POST', '/api/whatsapp/cloud-webhook',
        [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => metaSign($body, $this->appSecret)],
        $body
    )->assertStatus(200)->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessCloudApiWebhook::class);
});

// ── Job: persistencia texto ───────────────────────────────────────────────────

it('Job persiste inbound text em WhatsappChat + WhatsappMessage', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => $instance->waba_id,
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => $instance->phone_number_id, 'display_phone_number' => '5511987654321'],
                    'contacts' => [['profile' => ['name' => 'João Silva'], 'wa_id' => '5511999998888']],
                    'messages' => [[
                        'from' => '5511999998888',
                        'id'   => 'wamid.HBgN_TEST_123==',
                        'timestamp' => (string) now()->timestamp,
                        'type' => 'text',
                        'text' => ['body' => 'Ola, quero saber sobre o Vivensi'],
                    ]],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();

    $chat = WhatsappChat::where('wa_id', '5511999998888')->where('tenant_id', $instance->tenant_id)->first();
    expect($chat)->not->toBeNull();
    expect($chat->contact_name)->toBe('João Silva');
    expect($chat->opt_in_at)->not->toBeNull();

    $msg = WhatsappMessage::where('message_id', 'wamid.HBgN_TEST_123==')->first();
    expect($msg)->not->toBeNull();
    expect($msg->content)->toBe('Ola, quero saber sobre o Vivensi');
    expect($msg->direction)->toBe('inbound');
    expect($msg->type)->toBe('text');
    expect((int) $msg->tenant_id)->toBe($instance->tenant_id);
});

// ── Job: idempotência ────────────────────────────────────────────────────────

it('Job e idempotente (mesmo message_id nao gera duplicata)', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    $payload = [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $instance->phone_number_id],
                    'messages' => [['from' => '5511999998888', 'id' => 'wamid.DUP', 'type' => 'text', 'text' => ['body' => 'oi']]],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();
    (new ProcessCloudApiWebhook($payload))->handle();
    (new ProcessCloudApiWebhook($payload))->handle();

    expect(WhatsappMessage::where('message_id', 'wamid.DUP')->count())->toBe(1);
});

// ── Job: ignora phone_number_id desconhecido ──────────────────────────────────

it('Job ignora entry com phone_number_id que nao mapeia pra instance', function () {
    $payload = [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => '999_INEXISTENTE_999'],
                    'messages' => [['from' => '5511999998888', 'id' => 'wamid.X', 'type' => 'text', 'text' => ['body' => 'oi']]],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();

    expect(WhatsappMessage::count())->toBe(0);
});

// ── Job: mídia (imagem com caption) ───────────────────────────────────────────

it('Job persiste imagem inbound com caption', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();

    $payload = [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $instance->phone_number_id],
                    'contacts' => [['profile' => ['name' => 'Maria'], 'wa_id' => '5511977776666']],
                    'messages' => [[
                        'from' => '5511977776666',
                        'id'   => 'wamid.IMG',
                        'type' => 'image',
                        'image' => ['id' => 'media_id_abc', 'mime_type' => 'image/jpeg', 'caption' => 'Comprovante em anexo'],
                    ]],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();

    $msg = WhatsappMessage::where('message_id', 'wamid.IMG')->first();
    expect($msg)->not->toBeNull();
    expect($msg->type)->toBe('image');
    expect($msg->media_caption)->toBe('Comprovante em anexo');
    expect($msg->media_path)->toBe('media_id_abc');
});

// ── Job: status update progride sent → delivered → read ───────────────────────

it('Job atualiza status outbound de sent para delivered para read', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    $chat     = WhatsappChat::factory()->create(['tenant_id' => $instance->tenant_id]);
    $msg      = WhatsappMessage::create([
        'tenant_id'  => $instance->tenant_id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.OUT_1',
        'content'    => 'oi',
        'direction'  => 'outbound',
        'type'       => 'text',
        'status'     => 'sent',
    ]);

    $mkPayload = fn (string $status) => [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $instance->phone_number_id],
                    'statuses' => [['id' => 'wamid.OUT_1', 'status' => $status]],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($mkPayload('delivered')))->handle();
    expect($msg->fresh()->status)->toBe('delivered');

    (new ProcessCloudApiWebhook($mkPayload('read')))->handle();
    expect($msg->fresh()->status)->toBe('read');

    // Regressão inversa não altera (delivered veio depois de read)
    (new ProcessCloudApiWebhook($mkPayload('delivered')))->handle();
    expect($msg->fresh()->status)->toBe('read');
});

it('Job aceita status failed como terminal', function () {
    $instance = WhatsappInstance::factory()->cloudApi()->create();
    $chat     = WhatsappChat::factory()->create(['tenant_id' => $instance->tenant_id]);
    $msg      = WhatsappMessage::create([
        'tenant_id'  => $instance->tenant_id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.FAIL',
        'content'    => 'oi',
        'direction'  => 'outbound',
        'type'       => 'text',
        'status'     => 'sent',
    ]);

    $payload = [
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $instance->phone_number_id],
                    'statuses' => [['id' => 'wamid.FAIL', 'status' => 'failed']],
                ],
            ]],
        ]],
    ];

    (new ProcessCloudApiWebhook($payload))->handle();
    expect($msg->fresh()->status)->toBe('failed');
});
