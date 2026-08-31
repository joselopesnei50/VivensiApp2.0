<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LandingPageController;
use App\Models\LandingPage;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolve request cujo Host header e um dominio custom cadastrado por
 * cliente do add-on de landing page. Fail-closed: se o Host nao esta na
 * whitelist do DB nem e host proprio da Vivensi, aborta 404 (nao vaza
 * quais tenants existem).
 *
 * Roda como PRIMEIRO middleware do grupo web pra curto-circuitar cedo.
 *
 * Fluxo:
 *   www.ongfulano.org.br -> DNS aponta pro VPS -> nginx passa pra Laravel
 *   -> este middleware detecta Host custom -> busca LP pelo custom_domain
 *   -> chama LandingPageController::renderPage($lp->slug) diretamente.
 *
 * Fase 1 (este middleware): logica pronta.
 * Fase 2 (proxima): certbot + nginx server_block por dominio (pre-requisito
 * pro cert HTTPS ser valido no dominio custom).
 */
class ResolveCustomDomainLanding
{
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHost());

        // Hosts internos passam direto (site principal + IPs locais)
        if ($this->isOwnHost($host)) {
            return $next($request);
        }

        // Busca LP com custom_domain que casa. Fail-closed: whitelist DB.
        $page = LandingPage::withoutGlobalScopes()
            ->where('custom_domain', $host)
            ->where('custom_domain_status', 'active')
            ->where('status', 'published')
            ->first();

        if (!$page) {
            // Nao vaza qual tenant existe. Attacker que force Host arbitrario
            // sempre recebe 404, identico a rota inexistente.
            abort(404);
        }

        // Dispatch direto pro handler. Rota original nao e usada — resolvemos
        // aqui pra deixar o URL do cliente (path /) apontar direto pra LP dele.
        return app(LandingPageController::class)->renderPage($page->slug);
    }

    private function isOwnHost(string $host): bool
    {
        $appHost = strtolower(parse_url((string) config('app.url'), PHP_URL_HOST) ?? '');

        $ownHosts = array_filter([
            $appHost,
            'localhost',
            '127.0.0.1',
            '::1',
        ]);

        if (in_array($host, $ownHosts, true)) {
            return true;
        }

        // Qualquer subdominio de vivensi.app.br conta como proprio
        // (evita landing custom bater com host administrativo).
        if ($appHost && str_ends_with($host, '.' . $appHost)) {
            return true;
        }

        return false;
    }
}
