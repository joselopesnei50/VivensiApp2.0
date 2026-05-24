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

        // Somente aplica os headers se a resposta for do tipo aplicável (evita quebrar downloads binários, etc se não for tratado)
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN'); // Impede Clickjacking (site ser carregado num iframe de outro domínio)
            $response->header('X-XSS-Protection', '1; mode=block'); // Proteção extra para navegadores mais antigos contra XSS
            $response->header('X-Content-Type-Options', 'nosniff'); // Previne o navegador de tentar adivinhar o MIME type e executar vírus disfarçado
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin'); // Mantém os dados da URL seguros ao sair do seu site
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->header('Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com 'unsafe-inline'; " .
                "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; " .
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                "img-src 'self' data: https:; " .
                "connect-src 'self'; " .
                "frame-ancestors 'self'; " .
                "base-uri 'self'; " .
                "form-action 'self';"
            );
            $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');
        }

        return $response;
    }
}
