<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modulo de Planejamento de Projetos — feature flag
    |--------------------------------------------------------------------------
    | Quando true, expoe a aba /projects/{id}/planning com Apresentacao
    | Institucional, Objetivos, Metas qualitativas e Marcos planejados.
    | Default false para rollout gradual — pode ser ligado por .env sem
    | deploy (php artisan config:clear apos editar o .env).
    */
    'enabled' => env('PROJECT_PLANNING_ENABLED', false),
];
