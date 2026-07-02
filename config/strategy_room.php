<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sala de Estratégia — feature flag
    |--------------------------------------------------------------------------
    | Quando true, habilita os comandos artisan e services da Sala de
    | Estratégia. Fase 1.3: sem rota web, sem view. Default false.
    */
    'enabled' => env('STRATEGY_ROOM_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Modelo DeepSeek por agente (otimização de custo)
    |--------------------------------------------------------------------------
    | Os 3 agentes "de dado" (Financeiro/Inteligência/Mobilização) rodam em
    | 'deepseek-v4-flash' por default — são analíticos mas relativamente
    | mecânicos (consultam tool, resumem em 3-4 frases). O Estrategista-Chefe
    | (moderador/síntese) fica em 'deepseek-v4-pro' pra manter profundidade
    | de raciocínio na síntese das 3 falas.
    |
    | Se um agente escorregar demais com -flash (alucinar handle, quebrar
    | JSON), promova só ele pra -pro editando .env:
    |
    |   STRATEGY_MODEL_FINANCEIRO=deepseek-v4-pro
    |
    | Depois: php artisan config:clear (necessário APENAS quando alterar o
    | .env — o cache do config normalmente já está limpo em prod).
    */
    'models' => [
        'financeiro'   => env('STRATEGY_MODEL_FINANCEIRO',   'deepseek-v4-flash'),
        'inteligencia' => env('STRATEGY_MODEL_INTELIGENCIA', 'deepseek-v4-flash'),
        'mobilizacao'  => env('STRATEGY_MODEL_MOBILIZACAO',  'deepseek-v4-flash'),
        'chefe'        => env('STRATEGY_MODEL_CHEFE',        'deepseek-v4-pro'),
    ],
];
