<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contract signing link TTL (days)
    |--------------------------------------------------------------------------
    |
    | How long the public signing link should remain valid.
    | Set to null for no expiration.
    |
    */
    'public_sign_ttl_days' => env('CONTRACT_PUBLIC_SIGN_TTL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Notifications on contract signed
    |--------------------------------------------------------------------------
    |
    | Extra channels besides the in-app bell notification.
    | These are global toggles (safe defaults). If a channel is enabled but
    | not configured (e.g., no Brevo key or no Z-API instance), the system
    | will fail silently to keep signing stable.
    |
    */
    // Default is OFF: only enable via .env explicitly.
    'notify_email_on_signed' => env('CONTRACT_NOTIFY_EMAIL_ON_SIGNED', false),
    'notify_whatsapp_on_signed' => env('CONTRACT_NOTIFY_WHATSAPP_ON_SIGNED', false),
];

