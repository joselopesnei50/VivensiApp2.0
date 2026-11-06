<?php

use App\Services\BruceAiService;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Cache;

/**
 * No WhatsApp o Bruno roda sem usuário logado (userId 0). A memória do lead
 * (segmento, tipo de negócio, qualificação) tem que ficar por conversa —
 * antes ficava numa chave só e um lead "contaminava" o outro.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->prompts = [];
    $ds = Mockery::mock(DeepSeekService::class);
    $ds->shouldReceive('chat')->andReturnUsing(function ($messages) {
        $this->prompts[] = $messages[0]['content'] ?? '';
        return ['choices' => [['message' => ['content' => 'Oi! Como posso te chamar?']]], 'usage' => ['total_tokens' => 10]];
    });
    $this->app->instance(DeepSeekService::class, $ds);
});

function bmChat(string $msg, int $chatId, int $userId = 0): array
{
    return app(BruceAiService::class)->chat(
        userMessage: $msg, tenantId: 1, role: 'sales_bot', userId: $userId,
        contextType: 'whatsapp_chat', contextId: $chatId,
    );
}

it('segmento detectado num lead do WhatsApp não vaza para outro lead', function () {
    bmChat('sou MEI, trabalho sozinho', 101);
    expect(Cache::get('bruno.segment.1.whatsapp_chat-101'))->toBe('mei');

    bmChat('oi, tudo bem?', 202);
    expect(Cache::get('bruno.segment.1.whatsapp_chat-202'))->toBeNull()
        ->and(Cache::get('bruno.segment.1.0'))->toBeNull();

    // O prompt do lead 202 é o mesmo de um lead sem segmento (não herdou o "mei" do 101)
    $semSegmento = $this->prompts[1];
    Cache::flush();
    bmChat('oi, tudo bem?', 303);
    expect($semSegmento)->toBe($this->prompts[2]);
});

it('qualificação também fica por conversa', function () {
    $qual = ['score' => 90, 'temperature' => 'quente'];
    Cache::put('bruno.qualification.1.whatsapp_chat-101', $qual, 600);

    expect(bmChat('oi', 101)['qualification'])->toBe($qual);
    expect(bmChat('oi', 202)['qualification'])->toBeNull();
});

it('usuário logado (sandbox) continua com a chave pelo id', function () {
    bmChat('sou de uma ONG', 0, 7);
    expect(Cache::get('bruno.segment.1.7'))->toBe('ongs');
});
