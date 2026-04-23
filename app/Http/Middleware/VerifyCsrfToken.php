<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api/whatsapp/webhook',
        '/api/whatsapp/bot',       // Bot de Gestão Interna
        '/api/webhooks/asaas',
        '/api/abacatepay/webhook', // AbacatePay
        '/lp/*/lead',
        '/validar-recibo',
        '/openpix/webhook',
    ];
}
