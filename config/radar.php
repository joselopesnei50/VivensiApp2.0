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
        'base_url' => 'https://api.transferegov.gestao.gov.br',
        'endpoint' => '/chamamentos/chamamento-publico',
        'timeout'  => 15,
    ],

    'auto_approve' => [
        // Só keywords com comprovada precisão disparam auto-aprovação
        'high_precision_keywords' => [
            '"chamamento público"',
            '"termo de fomento"',
            '"termo de colaboração"',
        ],
        // Volume mínimo de feedbacks por keyword antes de confiar na automação
        'min_feedback_count' => env('RADAR_AUTO_MIN_FEEDBACK', 10),
        // Taxa mínima de 'útil' para considerar a keyword confiável
        'min_util_rate'      => env('RADAR_AUTO_MIN_UTIL_RATE', 0.70),
        // Taxa acima da qual uma keyword é sinalizada para revisão
        'flag_nao_util_rate' => env('RADAR_AUTO_FLAG_RATE', 0.30),
    ],
];
