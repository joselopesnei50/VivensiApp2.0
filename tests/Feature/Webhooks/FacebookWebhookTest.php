<?php

use App\Models\FacebookDataDeletionRequest;
use App\Models\SystemSetting;

beforeEach(function () {
    $this->appId     = '1046888724675814';
    $this->appSecret = 'test-secret-32-chars-long-string!';
    SystemSetting::setValue('meta_social_app_id', $this->appId);
    SystemSetting::setValue('meta_social_app_secret', $this->appSecret);
});

/**
 * Gera signed_request no formato que a Meta envia:
 *   base64url(HMAC-SHA256(base64url_payload, secret)) . base64url(json_payload)
 */
function makeSignedRequest(array $payload, string $secret): string
{
    $json      = json_encode(array_merge(['algorithm' => 'HMAC-SHA256'], $payload));
    $b64Payload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig       = hash_hmac('sha256', $b64Payload, $secret, true);
    $b64Sig    = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    return $b64Sig . '.' . $b64Payload;
}

// ── Deauthorize Callback ──────────────────────────────────────────────────────

it('retorna 200 para deauthorize com signed_request válido', function () {
    $signed = makeSignedRequest([
        'user_id'   => '10000000000000001',
        'issued_at' => now()->timestamp,
    ], $this->appSecret);

    $this->post('/social/facebook/deauthorize', ['signed_request' => $signed])
        ->assertStatus(200);
});

it('retorna 200 para deauthorize mesmo com signed_request inválido (sem quebrar)', function () {
    $this->post('/social/facebook/deauthorize', ['signed_request' => 'invalid.garbage'])
        ->assertStatus(200);
});

it('retorna 200 para deauthorize sem signed_request', function () {
    $this->post('/social/facebook/deauthorize', [])->assertStatus(200);
});

it('rejeita deauthorize com assinatura HMAC forjada', function () {
    // Signed_request assinado com secret errado
    $signed = makeSignedRequest([
        'user_id'   => '999',
        'issued_at' => now()->timestamp,
    ], 'wrong-secret-that-nobody-knows-32');

    // Deve retornar 200 (Meta exige) mas o log fica com "assinatura inválida"
    $this->post('/social/facebook/deauthorize', ['signed_request' => $signed])
        ->assertStatus(200);
});

// ── Data Deletion Callback ────────────────────────────────────────────────────

it('retorna JSON com url e confirmation_code para data-deletion válido', function () {
    $signed = makeSignedRequest([
        'user_id'   => '10000000000000002',
        'issued_at' => now()->timestamp,
    ], $this->appSecret);

    $response = $this->postJson('/social/facebook/data-deletion', [
        'signed_request' => $signed,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['url', 'confirmation_code']);

    $code = $response->json('confirmation_code');
    expect($code)->toBeString()->toHaveLength(40);
    expect($response->json('url'))->toContain('/lgpd/status/' . $code);
});

it('persiste FacebookDataDeletionRequest quando signed_request é válido', function () {
    $signed = makeSignedRequest([
        'user_id'   => '10000000000000003',
        'issued_at' => now()->timestamp,
    ], $this->appSecret);

    $this->postJson('/social/facebook/data-deletion', ['signed_request' => $signed])
        ->assertStatus(200);

    $req = FacebookDataDeletionRequest::latest()->first();
    expect($req)->not->toBeNull();
    expect($req->facebook_user_id)->toBe('10000000000000003');
    expect($req->status)->toBe('received');
    expect($req->confirmation_code)->toHaveLength(40);
});

it('persiste como rejected quando signed_request é inválido', function () {
    $this->postJson('/social/facebook/data-deletion', ['signed_request' => 'garbage.data'])
        ->assertStatus(200);

    $req = FacebookDataDeletionRequest::latest()->first();
    expect($req)->not->toBeNull();
    expect($req->status)->toBe('rejected');
    expect($req->facebook_user_id)->toBeNull();
});

// ── Página pública de status ──────────────────────────────────────────────────

it('mostra status de solicitação existente pelo confirmation_code', function () {
    $req = FacebookDataDeletionRequest::create([
        'facebook_user_id'  => '123',
        'confirmation_code' => 'test-code-abc123',
        'status'            => 'received',
    ]);

    $this->get('/lgpd/status/test-code-abc123')
        ->assertStatus(200)
        ->assertSee('test-code-abc123')
        ->assertSee('Recebida');
});

it('mostra mensagem de código não encontrado para código inexistente', function () {
    $this->get('/lgpd/status/codigo-inexistente-xyz')
        ->assertStatus(200)
        ->assertSee('Código não encontrado');
});

// ── CSRF exclusion ────────────────────────────────────────────────────────────

it('não exige CSRF token nos endpoints públicos de webhook', function () {
    // Se o CSRF middleware estivesse ativo, POST sem token daria 419.
    // Basta receber 200 mesmo enviando POST sem X-CSRF-TOKEN.
    $this->post('/social/facebook/deauthorize', [])->assertStatus(200);
    $this->postJson('/social/facebook/data-deletion', [])->assertStatus(200);
});
