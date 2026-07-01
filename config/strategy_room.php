<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sala de Estratégia — feature flag
    |--------------------------------------------------------------------------
    | Quando true, habilita os comandos artisan e services da Sala de
    | Estratégia. Fase 0: sem rota web, sem view. Default false.
    */
    'enabled' => env('STRATEGY_ROOM_ENABLED', false),
];
