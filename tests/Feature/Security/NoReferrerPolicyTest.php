<?php

/**
 * Fix 3 do relatório de segurança (2026-07-05): rotas com {token} na URL
 * devem retornar `Referrer-Policy: no-referrer` pra não vazar o token via
 * cabeçalho Referer quando o usuário clicar em link externo dentro da página.
 */

it('reset-password/{token} devolve Referrer-Policy: no-referrer', function () {
    $r = $this->get('/reset-password/some-fake-token');

    // Independente do status (form pode renderizar 200 ou 404), o header
    // precisa estar no valor no-referrer sobrescrevendo o SecurityHeaders global.
    expect($r->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

it('r/{token} (recibo) devolve Referrer-Policy: no-referrer', function () {
    $r = $this->get('/r/some-fake-token');

    expect($r->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

it('sign/{token} (contrato) devolve Referrer-Policy: no-referrer', function () {
    $r = $this->get('/sign/some-fake-token');

    expect($r->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

it('portal-doador/{token} devolve Referrer-Policy: no-referrer', function () {
    $r = $this->get('/portal-doador/some-fake-token');

    expect($r->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

it('chamada/{token} devolve Referrer-Policy: no-referrer', function () {
    $r = $this->get('/chamada/some-fake-token');

    expect($r->headers->get('Referrer-Policy'))->toBe('no-referrer');
});

it('rotas SEM token mantêm Referrer-Policy padrão (strict-origin-when-cross-origin)', function () {
    // Rota comum sem token no path — deve manter o valor do SecurityHeaders global,
    // não o no-referrer.
    $r = $this->get('/login');

    expect($r->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});
