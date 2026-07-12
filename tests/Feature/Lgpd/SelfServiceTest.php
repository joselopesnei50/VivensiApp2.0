<?php

use App\Jobs\ExportUserLgpdDataJob;
use App\Mail\LgpdExportReadyMail;
use App\Models\LgpdDataRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * LGPD Self-Service — Art. 15 (delecao) + Art. 18 IV (exportacao).
 */

uses(RefreshDatabase::class);

function lgpdUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => 'manager',
    ]);
}

// ── Dashboard ─────────────────────────────────────────────────────────────────

it('exige autenticacao pra acessar /eu/dados', function () {
    $this->get('/eu/dados')->assertRedirect('/login');
});

it('renderiza dashboard self-service sem solicitacoes previas', function () {
    $this->actingAs(lgpdUser())
        ->get('/eu/dados')
        ->assertOk()
        ->assertSee('Meus dados pessoais')
        ->assertSee('Exportar meus dados')
        ->assertSee('Excluir minha conta');
});

// ── Exportacao (Art. 18 IV) ───────────────────────────────────────────────────

it('solicita exportacao cria request pending + despacha job', function () {
    Bus::fake();
    $user = lgpdUser();

    $this->actingAs($user)
        ->post('/eu/dados/exportar')
        ->assertRedirect();

    $req = LgpdDataRequest::withoutGlobalScope('tenant')
        ->where('user_id', $user->id)
        ->first();

    expect($req)->not->toBeNull();
    expect($req->type)->toBe(LgpdDataRequest::TYPE_EXPORT);
    expect($req->status)->toBe(LgpdDataRequest::STATUS_PENDING);
    expect($req->ip_address)->not->toBeNull();

    Bus::assertDispatched(ExportUserLgpdDataJob::class);
});

it('bloqueia segunda exportacao dentro de 6 horas', function () {
    Bus::fake();
    $user = lgpdUser();

    $this->actingAs($user)->post('/eu/dados/exportar')->assertRedirect();
    $this->actingAs($user)->post('/eu/dados/exportar')->assertRedirect();

    // Somente 1 request criada
    expect(LgpdDataRequest::withoutGlobalScope('tenant')
        ->where('user_id', $user->id)
        ->where('type', LgpdDataRequest::TYPE_EXPORT)
        ->count())->toBe(1);
});

it('job de exportacao gera ZIP + salva token + envia email', function () {
    Mail::fake();
    Storage::fake('local');

    $user = lgpdUser();
    $req  = LgpdDataRequest::create([
        'user_id'   => $user->id,
        'tenant_id' => $user->tenant_id,
        'type'      => LgpdDataRequest::TYPE_EXPORT,
        'status'    => LgpdDataRequest::STATUS_PENDING,
        'ip_address'=> '127.0.0.1',
    ]);

    (new ExportUserLgpdDataJob($req->id))->handle();

    $req->refresh();
    expect($req->status)->toBe(LgpdDataRequest::STATUS_COMPLETED);
    expect($req->export_token)->not->toBeNull();
    expect(strlen($req->export_token))->toBeGreaterThanOrEqual(40);
    expect($req->export_file_path)->toStartWith('lgpd-exports/');
    expect(Storage::disk('local')->exists($req->export_file_path))->toBeTrue();
    expect($req->export_expires_at->isFuture())->toBeTrue();

    Mail::assertSent(LgpdExportReadyMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('download com token valido serve o ZIP', function () {
    Mail::fake();
    Storage::fake('local');

    $user = lgpdUser();
    $req  = LgpdDataRequest::create([
        'user_id'   => $user->id,
        'tenant_id' => $user->tenant_id,
        'type'      => LgpdDataRequest::TYPE_EXPORT,
        'status'    => LgpdDataRequest::STATUS_PENDING,
    ]);

    (new ExportUserLgpdDataJob($req->id))->handle();
    $req->refresh();

    // Nao autenticado — token e a autorizacao
    $response = $this->get("/eu/dados/download/{$req->export_token}");
    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');

    $req->refresh();
    expect($req->export_download_count)->toBe(1);
});

it('download com token expirado retorna 410', function () {
    Storage::fake('local');

    $user = lgpdUser();
    $req  = LgpdDataRequest::create([
        'user_id'           => $user->id,
        'tenant_id'         => $user->tenant_id,
        'type'              => LgpdDataRequest::TYPE_EXPORT,
        'status'            => LgpdDataRequest::STATUS_COMPLETED,
        'export_token'      => str_repeat('a', 43),
        'export_expires_at' => now()->subHour(), // ja expirado
        'export_file_path'  => 'lgpd-exports/dummy.zip',
    ]);

    $this->get("/eu/dados/download/{$req->export_token}")->assertStatus(410);
});

it('download com token invalido retorna 404', function () {
    $this->get('/eu/dados/download/token_completamente_invalido_1234567890abcdefgh')
        ->assertNotFound();
});

it('export nao inclui conversas whatsapp do tenant (PII de terceiros)', function () {
    Mail::fake();
    Storage::fake('local');

    $user = lgpdUser();

    // Conversas do tenant com TERCEIROS (leads/contatos) — nao sao dados do titular
    $chat = WhatsappChat::create([
        'tenant_id'     => $user->tenant_id,
        'wa_id'         => '5511999990000',
        'contact_name'  => 'CANARY-CONTATO-TERCEIRO',
        'contact_phone' => '5511999990000',
        'status'        => 'open',
    ]);
    WhatsappMessage::create([
        'tenant_id'  => $user->tenant_id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.canary-poc-1',
        'content'    => 'CANARY-MENSAGEM-TERCEIRO',
        'direction' => 'inbound',
        'type'      => 'text',
        'status'    => 'delivered',
    ]);

    $req = LgpdDataRequest::create([
        'user_id'    => $user->id,
        'tenant_id'  => $user->tenant_id,
        'type'       => LgpdDataRequest::TYPE_EXPORT,
        'status'     => LgpdDataRequest::STATUS_PENDING,
        'ip_address' => '127.0.0.1',
    ]);

    (new ExportUserLgpdDataJob($req->id))->handle();
    $req->refresh();

    $zip = new ZipArchive();
    expect($zip->open(Storage::disk('local')->path($req->export_file_path)))->toBeTrue();
    $json = $zip->getFromName('data.json');
    $zip->close();

    expect($json)->not->toContain('CANARY-CONTATO-TERCEIRO');
    expect($json)->not->toContain('CANARY-MENSAGEM-TERCEIRO');

    $payload = json_decode($json, true);
    expect($payload)->not->toHaveKey('whatsapp_chats');
    expect($payload)->not->toHaveKey('whatsapp_messages');
});

// ── Delecao (Art. 15) ─────────────────────────────────────────────────────────

it('solicita delecao agenda para +30 dias', function () {
    $user = lgpdUser();

    $this->actingAs($user)
        ->post('/eu/dados/excluir', ['confirm' => '1'])
        ->assertRedirect();

    $req = LgpdDataRequest::withoutGlobalScope('tenant')
        ->where('user_id', $user->id)
        ->where('type', LgpdDataRequest::TYPE_DELETE)
        ->first();

    expect($req)->not->toBeNull();
    expect($req->status)->toBe(LgpdDataRequest::STATUS_PENDING);
    expect($req->scheduled_for)->not->toBeNull();
    expect($req->scheduled_for->diffInDays(now()))->toBeGreaterThanOrEqual(29);
    expect($req->scheduled_for->diffInDays(now()))->toBeLessThanOrEqual(31);
});

it('exige checkbox de confirmacao para delecao', function () {
    $user = lgpdUser();

    $this->actingAs($user)
        ->post('/eu/dados/excluir', [])
        ->assertSessionHasErrors('confirm');

    expect(LgpdDataRequest::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('nao duplica delecao ativa', function () {
    $user = lgpdUser();

    $this->actingAs($user)->post('/eu/dados/excluir', ['confirm' => '1'])->assertRedirect();
    $this->actingAs($user)->post('/eu/dados/excluir', ['confirm' => '1'])->assertRedirect();

    expect(LgpdDataRequest::withoutGlobalScope('tenant')
        ->where('user_id', $user->id)
        ->where('type', LgpdDataRequest::TYPE_DELETE)
        ->count())->toBe(1);
});

it('cancela delecao dentro do grace period', function () {
    $user = lgpdUser();

    $this->actingAs($user)->post('/eu/dados/excluir', ['confirm' => '1']);
    $req = LgpdDataRequest::withoutGlobalScope('tenant')->firstWhere('user_id', $user->id);

    $this->actingAs($user)
        ->post("/eu/dados/excluir/cancelar/{$req->id}")
        ->assertRedirect();

    $req->refresh();
    expect($req->cancelled_at)->not->toBeNull();
});

it('bloqueia cancelamento de outro usuario (IDOR)', function () {
    $userA = lgpdUser();
    $userB = lgpdUser();

    // A pede delecao
    $this->actingAs($userA)->post('/eu/dados/excluir', ['confirm' => '1']);
    $reqA = LgpdDataRequest::withoutGlobalScope('tenant')->firstWhere('user_id', $userA->id);

    // B tenta cancelar a delecao de A
    $this->actingAs($userB)
        ->post("/eu/dados/excluir/cancelar/{$reqA->id}")
        ->assertNotFound();

    $reqA->refresh();
    expect($reqA->cancelled_at)->toBeNull();
});

// ── Purge command ─────────────────────────────────────────────────────────────

it('purge command anonimiza usuario com scheduled expirado', function () {
    $user = lgpdUser();
    $originalEmail = $user->email;

    $req = LgpdDataRequest::create([
        'user_id'       => $user->id,
        'tenant_id'     => $user->tenant_id,
        'type'          => LgpdDataRequest::TYPE_DELETE,
        'status'        => LgpdDataRequest::STATUS_PENDING,
        'scheduled_for' => now()->subDay(), // ja vencido
    ]);

    $this->artisan('lgpd:purge-scheduled-deletions')
        ->assertSuccessful();

    $user->refresh();
    expect($user->name)->toBe('Usuario Excluido');
    expect($user->email)->not->toBe($originalEmail);
    expect($user->email)->toContain('@excluido.vivensi');
    expect($user->phone)->toBeNull();
    expect($user->status)->toBe('inactive');

    $req->refresh();
    expect($req->status)->toBe(LgpdDataRequest::STATUS_COMPLETED);
    expect($req->processed_at)->not->toBeNull();
});

it('purge command ignora delecoes canceladas', function () {
    $user = lgpdUser();

    LgpdDataRequest::create([
        'user_id'       => $user->id,
        'tenant_id'     => $user->tenant_id,
        'type'          => LgpdDataRequest::TYPE_DELETE,
        'status'        => LgpdDataRequest::STATUS_PENDING,
        'scheduled_for' => now()->subDay(),
        'cancelled_at'  => now()->subHour(),
    ]);

    $this->artisan('lgpd:purge-scheduled-deletions')
        ->assertSuccessful();

    $user->refresh();
    // Nao anonimizado
    expect($user->status)->not->toBe('inactive');
});

it('purge command dry-run nao altera nada', function () {
    $user = lgpdUser();
    $originalEmail = $user->email;

    LgpdDataRequest::create([
        'user_id'       => $user->id,
        'tenant_id'     => $user->tenant_id,
        'type'          => LgpdDataRequest::TYPE_DELETE,
        'status'        => LgpdDataRequest::STATUS_PENDING,
        'scheduled_for' => now()->subDay(),
    ]);

    $this->artisan('lgpd:purge-scheduled-deletions', ['--dry-run' => true])
        ->assertSuccessful();

    $user->refresh();
    expect($user->email)->toBe($originalEmail);
});
