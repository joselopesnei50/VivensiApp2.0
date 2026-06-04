<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bruce AI — Contexto por Projeto (feature flag)
    |--------------------------------------------------------------------------
    | Quando true, /api/bruce/chat passa a aceitar os parâmetros context_type
    | e context_id e injeta dados do projeto no system prompt. Default false
    | para rollout gradual — pode ser ligado por .env sem deploy.
    */
    'context_project_enabled' => env('BRUCE_CONTEXT_PROJECT_ENABLED', false),
];
