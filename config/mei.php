<?php

/**
 * Vivensi — Módulo MEI (Microempreendedor Individual).
 *
 * Valores oficiais 2026. Atualizar quando a Receita publicar nova faixa
 * (geralmente jan/ano). Tudo sobrescrevível via .env por se a Receita
 * publicar valor novo no meio do ano.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Teto anual de faturamento MEI
    |--------------------------------------------------------------------------
    | Receita bruta anual máxima pra manter regime MEI. Em 2026 = R$ 81.000,00.
    | Valor em CENTAVOS pra evitar float drift.
    */
    'teto_anual_centavos' => (int) env('MEI_TETO_ANUAL_CENTAVOS', 8_100_000),

    /*
    |--------------------------------------------------------------------------
    | DAS — Documento de Arrecadação do Simples Nacional
    |--------------------------------------------------------------------------
    | Vencimento: dia 20 de todo mês (referente ao mês anterior). Valores
    | variam por atividade (Comércio/Indústria, Serviços, Comércio+Serviços).
    | Default usa a faixa mais cara (comércio+serviços) = R$ 81,90 em 2026.
    */
    'das' => [
        'dia_vencimento'   => (int) env('MEI_DAS_DIA_VENCIMENTO', 20),
        'valor_centavos'   => (int) env('MEI_DAS_VALOR_CENTAVOS', 8_190),
        // Description fixa usada pra identificar a Transaction "DAS pago".
        'description_marker' => env('MEI_DAS_DESCRIPTION', 'DAS — MEI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Faixas de alerta do termômetro do teto
    |--------------------------------------------------------------------------
    | Limiar (em %) onde a UI muda de cor.
    */
    'termometro' => [
        'verde_max'    => (int) env('MEI_TERM_VERDE_MAX', 70),
        'amarelo_max'  => (int) env('MEI_TERM_AMARELO_MAX', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (segundos)
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => (int) env('MEI_CACHE_TTL', 300),

];
