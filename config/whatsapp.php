<?php

return [
    // Data retention policy for WhatsApp messages/notes (LGPD).
    // Use 0 or null to disable cleanup scheduling (handled in Kernel).
    'retention_days' => (int) env('WHATSAPP_RETENTION_DAYS', 365),

    // Sandbox mode for localhost/dev: do not call external providers.
    'sandbox_enabled' => (bool) env('WHATSAPP_SANDBOX_ENABLED', false),

    // Evolution API v2 connection settings
    'evolution_api_url'    => env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br'),
    'evolution_global_key' => env('EVOLUTION_GLOBAL_KEY'),

    // Bot de Gestão Interna (Vivensi Command Bot)
    // Nome da instância na Evolution API dedicada ao bot de comandos
    'bot_instance_name' => env('WHATSAPP_BOT_INSTANCE', 'vivensi-bot'),
    'bot_phone'         => env('WHATSAPP_BOT_PHONE', '5516997618695'),
];


