<?php

/**
 * Meta Pixel refactor 2026-08-11:
 * - <x-meta-pixel /> substitui snippet inline em 15 arquivos
 * - <x-meta-pixel-event event="..." /> pra eventos de conversao (Lead, Purchase)
 * - Pixel ID via config('services.meta.pixel_id') com fallback env META_PIXEL_ID
 */

it('meta-pixel renderiza init + PageView com o ID configurado', function () {
    config(['services.meta.pixel_id' => '999888777']);
    $html = view('components.meta-pixel')->render();

    expect($html)->toContain('999888777');
    expect($html)->toContain("fbq('init', '999888777')");
    expect($html)->toContain("fbq('track', 'PageView')");
    expect($html)->toContain('facebook.com/tr?id=999888777');
});

it('meta-pixel nao renderiza nada quando pixel_id vazio (dev/teste)', function () {
    config(['services.meta.pixel_id' => '']);
    $html = view('components.meta-pixel')->render();

    expect(trim($html))->toBe('');
});

it('meta-pixel-event Lead sem valor renderiza fbq track', function () {
    config(['services.meta.pixel_id' => '999888777']);
    $html = view('components.meta-pixel-event', ['event' => 'Lead'])->render();

    expect($html)->toContain('"Lead"');
    expect($html)->toContain("fbq('track'");
    expect($html)->not->toContain('value');
});

it('meta-pixel-event Purchase com value dispara com currency', function () {
    config(['services.meta.pixel_id' => '999888777']);
    $html = view('components.meta-pixel-event', [
        'event' => 'Purchase', 'value' => 349.90, 'currency' => 'BRL',
    ])->render();

    expect($html)->toContain('"Purchase"');
    expect($html)->toContain('349.9');
    expect($html)->toContain('"currency":"BRL"');
});

it('meta-pixel-event nao renderiza nada quando pixel_id vazio', function () {
    config(['services.meta.pixel_id' => '']);
    $html = view('components.meta-pixel-event', ['event' => 'Lead'])->render();

    expect(trim($html))->toBe('');
});

it('meta-pixel-event nao renderiza nada sem event', function () {
    config(['services.meta.pixel_id' => '999888777']);
    $html = view('components.meta-pixel-event')->render();

    expect(trim($html))->toBe('');
});

it('meta-pixel-event aceita params extras (content_ids etc)', function () {
    config(['services.meta.pixel_id' => '999888777']);
    $html = view('components.meta-pixel-event', [
        'event'  => 'Purchase',
        'value'  => 100,
        'params' => ['content_ids' => ['plan-1'], 'content_name' => 'Plano NGO'],
    ])->render();

    expect($html)->toContain('"content_ids":["plan-1"]');
    expect($html)->toContain('"content_name":"Plano NGO"');
});

it('config services.php declara meta.pixel_id com default hardcoded', function () {
    // Le direto do arquivo pra nao depender de config mutada por outros testes.
    // Tolera alinhamento por espacos (=> pode ter N espacos antes).
    $file = file_get_contents(config_path('services.php'));
    expect($file)->toMatch("/'pixel_id'\s+=>\s+env\('META_PIXEL_ID'/");
    expect($file)->toContain("'493025661075925'");
});
