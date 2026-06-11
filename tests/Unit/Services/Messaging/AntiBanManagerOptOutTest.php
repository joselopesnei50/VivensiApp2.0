<?php

use App\Services\Messaging\AntiBanManager;

/**
 * Cobre os critérios de aceite da Tarefa 3.1 da auditoria
 * (PROMPT_CORRECAO_VIVENSI.md): isOptOutMessage não pode disparar
 * em palavras que apenas contêm a keyword como substring.
 */

// ── NÃO é opt-out (falsos-positivos do str_contains antigo) ───────────────────

test('parabéns não é opt-out (continha "para")', function () {
    expect(AntiBanManager::isOptOutMessage('parabéns pelo trabalho'))->toBeFalse();
});

test('parece não é opt-out (continha "pare")', function () {
    expect(AntiBanManager::isOptOutMessage('parece que vai chover'))->toBeFalse();
});

test('saia não é opt-out (continha "sai")', function () {
    expect(AntiBanManager::isOptOutMessage('comprei uma saia nova'))->toBeFalse();
});

test('comparar não é opt-out (continha "parar")', function () {
    expect(AntiBanManager::isOptOutMessage('preciso comparar os planos'))->toBeFalse();
});

test('preparar não é opt-out (continha "parar")', function () {
    expect(AntiBanManager::isOptOutMessage('vou preparar o jantar'))->toBeFalse();
});

test('cancelaram não é opt-out (continha "cancelar")', function () {
    expect(AntiBanManager::isOptOutMessage('cancelaram a entrega'))->toBeFalse();
});

test('chegamos não é opt-out (continha "chega")', function () {
    expect(AntiBanManager::isOptOutMessage('chegamos cedo no evento'))->toBeFalse();
});

// ── É opt-out (intent claro) ──────────────────────────────────────────────────

test('verbo "remover" como palavra completa dispara opt-out (trade-off conhecido)', function () {
    // Word-boundary não distingue intent (cancelar) vs uso casual do verbo.
    // 'remover' isolado retorna true mesmo em contexto positivo. Escolha
    // deliberada: falso positivo é melhor que falso negativo regulatoriamente.
    expect(AntiBanManager::isOptOutMessage('vamos remover obstáculos? bora!'))->toBeTrue();
});

test('PARAR maiúsculo é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('PARAR'))->toBeTrue();
});

test('parar minúsculo é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('parar'))->toBeTrue();
});

test('para sozinho é opt-out (palavra exata)', function () {
    expect(AntiBanManager::isOptOutMessage('para'))->toBeTrue();
});

test('quero cancelar é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('quero cancelar'))->toBeTrue();
});

test('não quero receber é opt-out (com acento)', function () {
    expect(AntiBanManager::isOptOutMessage('não quero receber mais isso'))->toBeTrue();
});

test('nao quero receber é opt-out (sem acento)', function () {
    expect(AntiBanManager::isOptOutMessage('nao quero receber mais isso'))->toBeTrue();
});

test('unsubscribe é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('unsubscribe'))->toBeTrue();
});

test('descadastrar é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('quero me descadastrar dessa lista'))->toBeTrue();
});

test('stop sozinho é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('stop'))->toBeTrue();
});

test('chega sozinho é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('chega'))->toBeTrue();
});

test('parar de receber é opt-out (palavra completa)', function () {
    expect(AntiBanManager::isOptOutMessage('quero parar de receber mensagens'))->toBeTrue();
});

// ── Edge cases ────────────────────────────────────────────────────────────────

test('mensagem vazia não é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage(''))->toBeFalse();
});

test('mensagem só com espaços não é opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('   '))->toBeFalse();
});

test('mensagem com tags HTML é normalizada antes de comparar', function () {
    expect(AntiBanManager::isOptOutMessage('<b>PARAR</b>'))->toBeTrue();
});

test('PARÁ (caps + acento) normaliza para "para" e é tratado como opt-out', function () {
    // Limitação consciente: palavra-única curta sempre vira opt-out após
    // normalização. Risco residual de quem manda só "PARÁ" (estado) sozinho
    // — mas uso real desse estado é em frase completa, não palavra solta.
    expect(AntiBanManager::isOptOutMessage('PARÁ'))->toBeTrue();
});

test('mensagem contextualizada com PARÁ não dispara opt-out', function () {
    expect(AntiBanManager::isOptOutMessage('moro no PARÁ desde 2010'))->toBeFalse();
    expect(AntiBanManager::isOptOutMessage('viajei pra PARÁ no feriado'))->toBeFalse();
});
