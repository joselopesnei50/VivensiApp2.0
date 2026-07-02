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
        'programas'    => env('STRATEGY_MODEL_PROGRAMAS',    'deepseek-v4-flash'),
        'chefe'        => env('STRATEGY_MODEL_CHEFE',        'deepseek-v4-pro'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cota diária de debates por tenant (Fase 3)
    |--------------------------------------------------------------------------
    | Cada debate custa 4 chamadas de API (3 flash + 1 pro). O throttle de
    | rota (6/h) protege contra rajada; esta cota protege o custo diário.
    | 0 = sem limite.
    */
    'daily_quota' => (int) env('STRATEGY_ROOM_DAILY_QUOTA', 10),

    /*
    |--------------------------------------------------------------------------
    | Trigger automático por queda de health score (Fase 3.1)
    |--------------------------------------------------------------------------
    | Comando strategy:auto-trigger (agendado diário 07:30) grava snapshots
    | de health dos projetos ativos e convoca reunião quando o overall_score
    | de algum projeto cai >= drop_threshold vs o snapshot anterior.
    |
    | Proteções de custo: cooldown por tenant + cap global diário de reuniões
    | automáticas + cota diária normal do tenant. Default OFF — ligar com
    | STRATEGY_ROOM_AUTO_TRIGGER=true no .env do VPS.
    */
    'auto_trigger'                  => env('STRATEGY_ROOM_AUTO_TRIGGER', false),
    'auto_trigger_drop_threshold'   => (int) env('STRATEGY_ROOM_AUTO_DROP_THRESHOLD', 10),
    'auto_trigger_cooldown_days'    => (int) env('STRATEGY_ROOM_AUTO_COOLDOWN_DAYS', 7),
    'auto_trigger_global_daily_cap' => (int) env('STRATEGY_ROOM_AUTO_GLOBAL_CAP', 20),

    /*
    |--------------------------------------------------------------------------
    | Trigger automático por doador recorrente em declínio
    |--------------------------------------------------------------------------
    | Comando strategy:donor-decline-trigger (agendado diário 07:45).
    | Doador recorrente = doou em >= recurring_min_months meses distintos nos
    | últimos 6 meses. Em declínio = sem nenhuma doação há decline_days dias.
    | Se o tenant tem >= 1 doador nessa condição, convoca reunião
    | (trigger_type=auto_donor_decline).
    |
    | Compartilha o cap global diário e o cooldown do trigger de health.
    | Default OFF — ligar com STRATEGY_ROOM_AUTO_TRIGGER_DONOR=true no .env.
    */
    'auto_trigger_donor'         => env('STRATEGY_ROOM_AUTO_TRIGGER_DONOR', false),
    'donor_recurring_min_months' => (int) env('STRATEGY_ROOM_DONOR_MIN_MONTHS', 3),
    'donor_decline_days'         => (int) env('STRATEGY_ROOM_DONOR_DECLINE_DAYS', 45),
];
