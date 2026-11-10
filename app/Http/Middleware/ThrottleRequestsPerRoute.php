<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use RuntimeException;

/**
 * throttle:X,Y com contador POR ROTA.
 *
 * No Laravel, o throttle numérico usa a mesma chave para o usuário em todas as
 * rotas (sha1 do id). Assim, ações frequentes (notificações, chat, polling)
 * somavam no limite de 10/min do checkout e o pagamento dava 429 sem o usuário
 * ter tentado pagar 10 vezes. Aqui a chave inclui método + rota.
 *
 * Limitadores nomeados (throttle:web_write etc.) não passam por aqui quando
 * definem ->by(); os que não definem também ficam por rota.
 */
class ThrottleRequestsPerRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        $routeKey = $request->method() . '|' . ($route ? ($route->getDomain() . '|' . $route->uri()) : $request->path());

        if ($user = $request->user()) {
            return sha1('u|' . $user->getAuthIdentifier() . '|' . $routeKey);
        }
        if ($route) {
            return sha1('ip|' . $request->ip() . '|' . $routeKey);
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }
}
