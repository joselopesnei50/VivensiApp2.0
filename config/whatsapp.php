<?php

return [
    // Data retention policy for WhatsApp messages/notes (LGPD).
    // Use 0 or null to disable cleanup scheduling (handled in Kernel).
    'retention_days' => (int) env('WHATSAPP_RETENTION_DAYS', 365),

    // Sandbox mode for localhost/dev: do not call external providers.
    'sandbox_enabled' => (bool) env('WHATSAPP_SANDBOX_ENABLED', true),
];

