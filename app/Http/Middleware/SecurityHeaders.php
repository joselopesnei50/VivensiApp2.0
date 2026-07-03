<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-XSS-Protection', '1; mode=block');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->header('Content-Security-Policy', $this->buildCsp());
            $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');
        }

        return $response;
    }

    private function buildCsp(): string
    {
        // Build WebSocket origin from broadcasting config (Soketi/Reverb self-hosted)
        $wsHost   = config('broadcasting.connections.pusher.options.host', '127.0.0.1');
        $wsPort   = config('broadcasting.connections.pusher.options.port', 6001);
        $wsScheme = (config('broadcasting.connections.pusher.options.scheme', 'http') === 'https') ? 'wss' : 'ws';
        $wsOrigin = "{$wsScheme}://{$wsHost}:{$wsPort}";

        $directives = [
            "default-src 'self'",
            "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.googletagmanager.com 'unsafe-inline'",
            "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'",
            "font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' {$wsOrigin} https://viacep.com.br https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives) . ';';
    }
}
