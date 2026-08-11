<?php

use App\Services\BruceAiService;

/**
 * Confirma que os blocos novos de growth marketing (marketing_frameworks
 * + analogias_referencia + novas objecoes/few-shots) sao carregados do KB
 * e injetados no system prompt do Bruno. Base: growth-marketing-ai-training.md
 * integrado em 2026-08-11.
 */

it('KB do bot-vendedor tem os blocos de growth marketing', function () {
    $kb = config('bot-vendedor');

    expect($kb)->toHaveKey('marketing_frameworks');
    expect($kb['marketing_frameworks'])->toHaveKeys([
        'situacao_mercado', 'frameworks', 'aplicacao_vivensi', 'quando_usar',
    ]);

    // Frameworks essenciais do documento
    expect($kb['marketing_frameworks']['frameworks'])->toHaveKeys([
        'metodo_cientifico', 'sistema_solar', 'matriz_rfm', 'gancho_2s',
        'funil_educacao', 'autenticidade',
    ]);

    // Mapeamento framework -> modulo Vivensi
    expect($kb['marketing_frameworks']['aplicacao_vivensi'])->toHaveKeys([
        'RFM', 'Funil de educacao', 'Gancho 2s', 'Metodo cientifico', 'Sistema Solar',
    ]);

    expect($kb)->toHaveKey('analogias_referencia');
    expect($kb['analogias_referencia'])->toHaveCount(4);
    $nomes = collect($kb['analogias_referencia'])->pluck('nome');
    expect($nomes->contains(fn ($n) => str_contains($n, 'Minimal')))->toBeTrue();
    expect($nomes->contains(fn ($n) => str_contains($n, 'Infomoney')))->toBeTrue();
    expect($nomes->contains(fn ($n) => str_contains($n, 'Lugano')))->toBeTrue();
    expect($nomes->contains(fn ($n) => str_contains($n, 'Dropbox')))->toBeTrue();
});

it('novas objecoes de marketing entraram no bloco objections', function () {
    $kb = config('bot-vendedor');
    $textos = collect($kb['objections'])->pluck('objection')->implode(' | ');

    expect($textos)->toContain('anuncios');
    expect($textos)->toContain('doadores');
    expect($textos)->toContain('criativo');
});

it('novos few-shots de growth entraram no bloco few_shot', function () {
    $kb = config('bot-vendedor');
    $situacoes = collect($kb['few_shot'])->pluck('situacao')->implode(' | ');

    // 3 novos few-shots sobre: ads que nao convertem, RFM, Hub de Marketing IA
    expect($situacoes)->toContain('ads que nao convertem');
    expect($situacoes)->toContain('doadores mais valiosos');
    expect($situacoes)->toContain('Hub de Marketing IA');
});

it('system prompt do sales bot inclui os blocos de growth e analogias', function () {
    // Resolve pelo container pra receber DeepSeekService + TenantContextService.
    $svc  = app(BruceAiService::class);
    $ref  = new ReflectionClass($svc);
    $meth = $ref->getMethod('buildSalesBotPrompt');
    $meth->setAccessible(true);

    // tenantId=1, sem qualification/segment/business/campaign -> abertura generica
    $prompt = $meth->invoke($svc, 1, null, null, null, null);

    // Blocos novos aparecem
    expect($prompt)->toContain('GROWTH MARKETING');
    expect($prompt)->toContain('matriz_rfm');
    expect($prompt)->toContain('funil_educacao');
    expect($prompt)->toContain('gancho_2s');
    expect($prompt)->toContain('ANALOGIAS DE REFERENCIA');
    expect($prompt)->toContain('Infomoney');
    expect($prompt)->toContain('Dropbox');

    // Regra explicita anti-palestra e anti-case-falso
    expect($prompt)->toContain('NAO vire palestra');
    expect($prompt)->toContain('NUNCA diga que sao case da Vivensi');
});
