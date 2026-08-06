<?php

/**
 * Timezone da aplicacao (2026-08-06).
 * Producao roda em America/Sao_Paulo. Testes rodam em UTC (phpunit.xml override)
 * pra manter reprodutibilidade e nao quebrar suites que assumem UTC.
 */

it('config app.timezone respeita env APP_TIMEZONE', function () {
    // phpunit.xml define APP_TIMEZONE=UTC — testes rodam nele por padrao.
    expect(config('app.timezone'))->toBe('UTC');
});

it('config default cai em America/Sao_Paulo se APP_TIMEZONE nao setado', function () {
    // Simula prod: sem env APP_TIMEZONE, precisa cair no default 'America/Sao_Paulo'.
    // Nao mudamos config em runtime pra nao poluir outros testes — validamos a
    // linha do config diretamente.
    $line = file_get_contents(config_path('app.php'));
    expect($line)->toContain("env('APP_TIMEZONE', 'America/Sao_Paulo')");
});
