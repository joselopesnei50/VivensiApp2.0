<?php

return [
    // Data retention policy for WhatsApp messages/notes (LGPD).
    // Use 0 or null to disable cleanup scheduling (handled in Kernel).
    'retention_days' => (int) env('WHATSAPP_RETENTION_DAYS', 365),

    // Sandbox mode for localhost/dev: do not call external providers.
    'sandbox_enabled' => (bool) env('WHATSAPP_SANDBOX_ENABLED', false),

    // Evolution API v2 connection settings
    'evolution_api_url' => env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br'),
    'evolution_global_key' => env('EVOLUTION_GLOBAL_KEY', 'e838f5d5b86ea0fe27492c283c27498ffe0b250085896f1eaa8093baf0a3309e'),
];

