<?php

return [
    // Data retention policy for WhatsApp messages/notes (LGPD).
    // Use 0 or null to disable cleanup scheduling (handled in Kernel).
    'retention_days' => (int) env('WHATSAPP_RETENTION_DAYS', 365),

    // Sandbox mode for localhost/dev: do not call external providers.
    'sandbox_enabled' => (bool) env('WHATSAPP_SANDBOX_ENABLED', false),

    // Evolution API v2 connection settings
    'evolution_api_url'      => env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br'),
    'evolution_global_key'   => env('EVOLUTION_GLOBAL_KEY'),
    // Segredo HMAC opcional para validar assinatura dos webhooks da Evolution API.
    // Se definido, rejeita payloads sem header x-webhook-hmac válido.
    'evolution_webhook_secret' => env('EVOLUTION_WEBHOOK_SECRET'),

    // Chave dedicada para o blind index dos tokens de instância WhatsApp
    // (campo instance_token_bidx em whatsapp_instances). Antes da Tarefa 1.5
    // o hash usava config('app.key') diretamente — rotacionar APP_KEY quebrava
    // a busca de instâncias nos webhooks. Esta chave separada permite rotação
    // independente. Se vazia, o helper whatsapp_bidx_key() faz fallback para
    // APP_KEY (mantém compatibilidade em dev e em prod ainda não migrada).
    'bidx_key' => env('WHATSAPP_BIDX_KEY'),

    // ── Política antiban (Tarefa 3.2 da auditoria) ─────────────────────────
    // Valores que antes eram constantes no AntiBanManager. Configurável por
    // env (defaults idênticos ao comportamento anterior) e overridável por
    // instância via whatsapp_instances.settings['warming_profile'] e
    // settings['max_per_hour']. Ver docs/WHATSAPP_TUNING.md.
    'antiban' => [
        // Limite máximo por hora (rate limiter horário). Default 55 = comportamento
        // pré-3.2. Override por instância via settings.max_per_hour.
        'max_per_hour' => (int) env('WHATSAPP_MAX_PER_HOUR', 55),

        // Horas de restrição quando isBanSignal detecta sinal de ban (markAsRestricted).
        // Aplicado globalmente — não há override por instância por design (decisão
        // de saúde de plataforma, não de produto).
        'default_ban_restriction_hours' => (int) env('WHATSAPP_BAN_RESTRICTION_HOURS', 24),

        // Perfis de warming disponíveis. Cada perfil mapeia dia => limite diário.
        // Após dia 14 o warming é desativado e usa instance.daily_limit normal.
        //
        // Tenant escolhe via $instance->settings['warming_profile'] = 'default' | 'conservative'.
        // Default = comportamento pré-3.2 (20→370 em 14 dias, ~30% crescimento/dia).
        // Conservative = recomendado para chip novo ou cliente cauteloso (15→150 em 14 dias).
        'warming_profiles' => [
            'default' => [
                1  => 20,  2  => 30,  3  => 40,  4  => 55,  5  => 70,
                6  => 90,  7  => 115, 8  => 140, 9  => 170, 10 => 205,
                11 => 245, 12 => 290, 13 => 340, 14 => 370,
            ],
            'conservative' => [
                1  => 15,  2  => 20,  3  => 25,  4  => 35,  5  => 45,
                6  => 55,  7  => 65,  8  => 80,  9  => 95,  10 => 110,
                11 => 125, 12 => 135, 13 => 145, 14 => 150,
            ],
        ],
    ],

    // Bot de Gestão Interna (Vivensi Command Bot)
    // Nome da instância na Evolution API dedicada ao bot de comandos
    'bot_instance_name' => env('WHATSAPP_BOT_INSTANCE', 'vivensi-bot'),
    'bot_phone'         => env('WHATSAPP_BOT_PHONE', '5516997618695'),
];


