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
];
