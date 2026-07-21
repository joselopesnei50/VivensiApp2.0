<?php

use Illuminate\Support\Str;

return [

    'name'   => env('HORIZON_NAME', 'Vivensi'),
    'domain' => env('HORIZON_DOMAIN'),
    'path'   => env('HORIZON_PATH', 'horizon'),
    'use'    => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'vivensi'), '_').'_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        'redis:default'    => 60,
        'redis:whatsapp'   => 30,
        'redis:ai'         => 120,
        'redis:emails'     => 60,
        'redis:social'     => 90,
    ],

    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080,
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    'silenced' => [],
    'silenced_tags' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job'   => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,
    'memory_limit'     => 128,

    /*
    |--------------------------------------------------------------------------
    | Supervisores — defaults (base para todos os ambientes)
    |--------------------------------------------------------------------------
    |
    | Separados por domínio para isolamento de falhas:
    |   supervisor-default   → filas gerais, pagamentos, webhooks
    |   supervisor-whatsapp  → broadcast, chat, automações (timeout longo)
    |   supervisor-ai        → IA, social posts, prospecção (memória extra)
    |   supervisor-emails    → notificações, relatórios, e-mails
    |   supervisor-social    → publicações em redes sociais
    |
    */
    'defaults' => [
        'supervisor-default' => [
            'connection'         => 'redis',
            'queue'              => ['default'],
            'balance'            => 'auto',
            'autoScalingStrategy'=> 'time',
            'maxProcesses'       => 2,
            'maxTime'            => 0,
            'maxJobs'            => 500,
            'memory'             => 128,
            'tries'              => 3,
            'timeout'            => 90,
            'nice'               => 0,
        ],

        'supervisor-whatsapp' => [
            'connection'         => 'redis',
            'queue'              => ['whatsapp'],
            'balance'            => 'simple',
            'autoScalingStrategy'=> 'time',
            'maxProcesses'       => 2,
            'maxTime'            => 0,
            'maxJobs'            => 0,
            'memory'             => 128,
            'tries'              => 3,
            'timeout'            => 960,   // ProcessBroadcastCampaignJob chunks: $timeout=900s (15min) + margem de 1min
            'nice'               => 0,
        ],

        'supervisor-ai' => [
            'connection'         => 'redis',
            'queue'              => ['ai'],
            'balance'            => 'simple',
            'autoScalingStrategy'=> 'time',
            'maxProcesses'       => 1,
            'maxTime'            => 0,
            'maxJobs'            => 0,
            'memory'             => 256,  // IA precisa de mais memória
            'tries'              => 2,
            'timeout'            => 300,
            'nice'               => 5,    // menor prioridade de CPU
        ],

        'supervisor-emails' => [
            'connection'         => 'redis',
            'queue'              => ['emails', 'notifications'],
            'balance'            => 'auto',
            'autoScalingStrategy'=> 'time',
            'maxProcesses'       => 2,
            'maxTime'            => 0,
            'maxJobs'            => 1000,
            'memory'             => 128,
            'tries'              => 3,
            'timeout'            => 60,
            'nice'               => 0,
        ],

        'supervisor-social' => [
            'connection'         => 'redis',
            'queue'              => ['social'],
            'balance'            => 'simple',
            'autoScalingStrategy'=> 'time',
            'maxProcesses'       => 1,
            'maxTime'            => 0,
            'maxJobs'            => 0,
            'memory'             => 128,
            'tries'              => 2,
            'timeout'            => 120,
            'nice'               => 5,
        ],

        'supervisor-webhooks' => [
            'connection'         => 'redis',
            'queue'              => ['webhooks'],
            'balance'            => 'auto',
            'autoScalingStrategy'=> 'time',
            'maxProcesses'       => 2,
            'maxTime'            => 0,
            'maxJobs'            => 0,
            'memory'             => 128,
            'tries'              => 3,
            'timeout'            => 30,
            'nice'               => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
            ],
            'supervisor-whatsapp' => [
                'maxProcesses'    => 2,
            ],
            'supervisor-ai' => [
                'maxProcesses'    => 2,
                'memory'          => 384,
            ],
            'supervisor-emails' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-social' => [
                'maxProcesses'    => 2,
            ],
            'supervisor-webhooks' => [
                'maxProcesses'    => 3,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'supervisor-default'  => ['maxProcesses' => 1],
            'supervisor-whatsapp' => ['maxProcesses' => 1],
            'supervisor-ai'       => ['maxProcesses' => 1],
            'supervisor-emails'   => ['maxProcesses' => 1],
            'supervisor-social'   => ['maxProcesses' => 1],
            'supervisor-webhooks' => ['maxProcesses' => 1],
        ],
    ],

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],
];
