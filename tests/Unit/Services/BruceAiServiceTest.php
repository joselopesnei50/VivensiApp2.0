<?php

use App\Services\BruceAiService;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function seedCtx(int $tenantId): void
{
    Cache::put("bruce.ctx.{$tenantId}", [
        'income'          => 5000.0,
        'expense'         => 1200.0,
        'balance'         => 3800.0,
        'active_projects' => 3,
        'open_tasks'      => 7,
        'overdue_tasks'   => 2,
    ], 300);
}

function fakeDeepSeekOk(string $content = 'Saldo positivo este mês.'): DeepSeekService
{
    $mock = Mockery::mock(DeepSeekService::class);
    $mock->shouldReceive('chat')->andReturn([
        'choices' => [['message' => ['content' => $content]]],
        'usage'   => ['total_tokens' => 42],
    ]);
    return $mock;
}

function fakeDeepSeekError(): DeepSeekService
{
    $mock = Mockery::mock(DeepSeekService::class);
    $mock->shouldReceive('chat')->andReturn(['error' => 'API unavailable']);
    return $mock;
}

function makeBruce(DeepSeekService $ds): BruceAiService
{
    return new BruceAiService($ds);
}

// ── fmt() ─────────────────────────────────────────────────────────────────────

it('fmt formats float with pt_BR notation', function () {
    $bruce = makeBruce(fakeDeepSeekOk());
    $ref   = new ReflectionMethod($bruce, 'fmt');
    $ref->setAccessible(true);

    expect($ref->invoke($bruce, 1500.75))->toBe('1.500,75');
    expect($ref->invoke($bruce, 0.0))->toBe('0,00');
    expect($ref->invoke($bruce, 250.5))->toBe('250,50');
});

// ── chat() — success ──────────────────────────────────────────────────────────

it('chat returns reply and token count on success', function () {
    Cache::flush();
    seedCtx(1);

    $result = makeBruce(fakeDeepSeekOk('Tudo certo!'))->chat('Qual meu saldo?', 1, 'common');

    expect($result)->toHaveKeys(['reply', 'tokens', 'timestamp']);
    expect($result['reply'])->toBe('Tudo certo!');
    expect($result['tokens'])->toBe(42);
});

// ── chat() — error propagation ────────────────────────────────────────────────

it('chat propagates DeepSeek error', function () {
    Cache::flush();
    seedCtx(2);

    $result = makeBruce(fakeDeepSeekError())->chat('Olá', 2, 'ngo');

    expect($result)->toHaveKey('error');
    expect($result['error'])->toBe('API unavailable');
});

// ── history management ────────────────────────────────────────────────────────

it('chat persists conversation history in cache', function () {
    Cache::flush();
    seedCtx(99);

    $bruce = makeBruce(fakeDeepSeekOk('Resposta 1'));
    $bruce->chat('Mensagem 1', 99, 'common');

    $history = Cache::get('bruce.history.99');
    expect($history)->toBeArray()->toHaveCount(2);
    expect($history[0]['role'])->toBe('user');
    expect($history[1]['role'])->toBe('assistant');
});

it('clearHistory removes tenant history from cache', function () {
    Cache::put('bruce.history.5', [['role' => 'user', 'content' => 'Oi']], 1800);

    makeBruce(fakeDeepSeekOk())->clearHistory(5);

    expect(Cache::get('bruce.history.5'))->toBeNull();
});

it('history is capped at MAX_HISTORY messages', function () {
    Cache::flush();
    seedCtx(7);

    $bruce = makeBruce(fakeDeepSeekOk('ok'));

    $existing = [];
    for ($i = 0; $i < 10; $i++) {
        $existing[] = ['role' => 'user', 'content' => "msg $i"];
    }
    Cache::put('bruce.history.7', $existing, 1800);

    $bruce->chat('new message', 7, 'common');

    $history = Cache::get('bruce.history.7');
    expect(count($history))->toBeLessThanOrEqual(BruceAiService::MAX_HISTORY);
});

// ── dailyInsight() ────────────────────────────────────────────────────────────

it('dailyInsight returns string insight', function () {
    Cache::flush();
    seedCtx(10);

    $insight = makeBruce(fakeDeepSeekOk('Receitas cresceram 12% este mês.'))->dailyInsight(10, 'manager');

    expect($insight)->toBeString()->not->toBeEmpty();
});

it('dailyInsight caches result for the day (second call skips DeepSeek)', function () {
    Cache::flush();
    seedCtx(20);

    $called = 0;
    $mock   = Mockery::mock(DeepSeekService::class);
    $mock->shouldReceive('chat')->andReturnUsing(function () use (&$called) {
        $called++;
        return ['choices' => [['message' => ['content' => 'Insight do dia.']]]];
    });

    $bruce = makeBruce($mock);
    $bruce->dailyInsight(20, 'common');
    $bruce->dailyInsight(20, 'common');

    expect($called)->toBe(1);
});

it('dailyInsight returns fallback when DeepSeek returns empty', function () {
    Cache::flush();
    seedCtx(30);

    $mock = Mockery::mock(DeepSeekService::class);
    $mock->shouldReceive('chat')->andReturn(['choices' => [['message' => ['content' => '']]]]);

    $insight = makeBruce($mock)->dailyInsight(30, 'ngo');
    expect($insight)->toBe('Adicione mais transações para gerar insights precisos.');
});

// ── system prompt role context ────────────────────────────────────────────────

it('system prompt for ngo role mentions terceiro setor vocabulary', function () {
    Cache::flush();
    seedCtx(1);

    $bruce = makeBruce(fakeDeepSeekOk());
    $ref   = new ReflectionMethod($bruce, 'buildSystemPrompt');
    $ref->setAccessible(true);

    $prompt = $ref->invoke($bruce, 1, 'ngo');
    expect($prompt)->toContain('doadores')
                   ->toContain('editais')
                   ->toContain('beneficiários');
});

it('system prompt for manager role mentions projetos and equipe', function () {
    Cache::flush();
    seedCtx(1);

    $bruce = makeBruce(fakeDeepSeekOk());
    $ref   = new ReflectionMethod($bruce, 'buildSystemPrompt');
    $ref->setAccessible(true);

    $prompt = $ref->invoke($bruce, 1, 'manager');
    expect($prompt)->toContain('projetos')
                   ->toContain('equipe');
});

it('system prompt contains explicit prohibition of animal references', function () {
    Cache::flush();
    seedCtx(1);

    $bruce = makeBruce(fakeDeepSeekOk());
    $ref   = new ReflectionMethod($bruce, 'buildSystemPrompt');
    $ref->setAccessible(true);

    $prompt = $ref->invoke($bruce, 1, 'common');
    // The prohibition rule must be present so the LLM knows to avoid these words
    expect($prompt)->toContain('PROIBIDO')
                   ->toContain('Golden Retriever')
                   ->toContain('cachorro');
});

afterEach(fn () => Mockery::close());
