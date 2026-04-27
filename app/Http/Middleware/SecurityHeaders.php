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
            // TODO [AUDIT A09]: Aumentar para max-age=31536000 após confirmar SSL estável em produção.
            // Não aumentar antes — browsers gravam o HSTS por 1 ano sem reversão fácil.
            $response->header('Strict-Transport-Security', 'max-age=300');
            
            // Content-Security-Policy (básico - pode ser aprofundado se o sistema exigir)
            // Permite fontes normais, fontes do Google, imagens do site e dados de data:, etc.
            // Para não quebrar o canvas.blade e editores, não estamos restringindo img-src duramente.
            $response->header('Content-Security-Policy', "frame-ancestors 'self'; upgrade-insecure-requests;");
        }

        return $response;
    }
}
