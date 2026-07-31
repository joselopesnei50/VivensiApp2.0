<?php

return [

    'enabled' => env('RADAR_ENABLED', false),

    'keywords' => [
        '"chamamento público"',
        '"termo de fomento"',
        '"termo de colaboração"',
        '"edital de chamamento"',
        '"organização da sociedade civil"',
    ],

    'collect' => [
        'days_back' => 7,
    ],

    'querido_diario' => [
        'base_url'           => 'https://api.queridodiario.ok.org.br',
        'excerpt_size'       => 500,
        'number_of_excerpts' => 3,
        'page_size'          => 20,
        'timeout'            => 15,
    ],

    'transferegov' => [
        // ESTACIONADO 2026-07-31: o endpoint abaixo retorna 404 desde a migração
        // para api-publica.transferegov.gestao.gov.br (Comunicado nº 23/2026).
        // A API de chamamentos MROSC ainda NÃO existe — módulo "Discricionárias
        // e Legais", Entrega 1 "Atos Preparatórios" prevista para 31/10/2026.
        // Revisitar ~11/2026: trocar base_url/endpoint, paginação passa a ser
        // pagina/tamanho_da_pagina com envelope {data:[]} (sem PostgREST eq./gte.).
        'enabled'  => env('RADAR_TRANSFEREGOV_ENABLED', false),
        'base_url' => 'https://api.transferegov.gestao.gov.br',
        'endpoint' => '/chamamentos/chamamento-publico',
        'timeout'  => 15,
    ],

    'auto_approve' => [
        // Só keywords com comprovada precisão disparam auto-aprovação.
        // 'transferegov' é o keyword_matched fixo dos findings federais —
        // eles qualificam pela própria taxa de feedback, sem contaminar
        // as estatísticas das keywords do Querido Diário.
        'high_precision_keywords' => [
            '"chamamento público"',
            '"termo de fomento"',
            '"termo de colaboração"',
            'transferegov',
        ],
        // Volume mínimo de feedbacks por keyword antes de confiar na automação
        'min_feedback_count' => env('RADAR_AUTO_MIN_FEEDBACK', 10),
        // Taxa mínima de 'útil' para considerar a keyword confiável
        'min_util_rate'      => env('RADAR_AUTO_MIN_UTIL_RATE', 0.70),
        // Taxa acima da qual uma keyword é sinalizada para revisão
        'flag_nao_util_rate' => env('RADAR_AUTO_FLAG_RATE', 0.30),
    ],
];
