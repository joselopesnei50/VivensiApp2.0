<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * NoReferrerPolicy — força Referrer-Policy: no-referrer em rotas que
 * contêm token de acesso na URL.
 *
 * Fix 3 do relatório de segurança (2026-07-05): rotas como
 * /portal-doador/{token}, /r/{token}, /sign/{token} e /chamada/{token}
 * expõem o token na URL. Sem esse header, o token pode vazar via cabeçalho
 * Referer para qualquer link externo clicado dentro da página (ex.: link
 * pra rede social do doador, link de suporte externo, redes de anúncio
 * carregadas por iframe).
 *
 * SecurityHeaders global usa `strict-origin-when-cross-origin` — passa a
 * origem em cross-origin, o que ainda pode ser problema. `no-referrer` é
 * definitivo pra rotas de token.
 */
class NoReferrerPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            // Sobrescreve o valor definido pelo SecurityHeaders (que roda antes
            // como middleware global) — o último header vale.
            $response->header('Referrer-Policy', 'no-referrer');
        }

        return $response;
    }
}
