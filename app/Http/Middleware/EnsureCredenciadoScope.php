<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Restringe user com role=credenciado ao painel proprio /credenciado/*.
 *
 * O credenciado NUNCA acessa o workspace normal do projeto (/projects/{id}),
 * porque essa view expoe financeiro, doadores, transacoes, membros da equipe
 * interna e outros dados que sao segredo da entidade. Todo acesso do
 * credenciado ao projeto passa por /credenciado/projeto/{id}, que renderiza
 * uma view minimal so com o que ele precisa pra atuar (info + tarefas +
 * presenca).
 *
 * Whitelist minima:
 *   - /credenciado/*        painel proprio (dashboard + workspace minimal)
 *   - /logout               sair
 *   - /profile*             perfil (editar dados pessoais)
 *   - /2fa* + /password* + /reset-password/*  seguranca da propria conta
 *   - /locale/*             troca de idioma
 *   - /welcome/dismiss      dispensar modal de boas-vindas
 *   - /support*             suporte
 *   - /notifications*       ver notificacoes proprias
 *   - /api/notifications*   idem via ajax
 *
 * Qualquer outra coisa -> redirect /credenciado (web) ou 403 (ajax).
 */
class EnsureCredenciadoScope
{
    private const ALLOWED_PATH_PATTERNS = [
        '#^credenciado(/.*)?$#',
        '#^logout$#',
        '#^profile(/.*)?$#',
        '#^2fa(/.*)?$#',
        '#^password(/.*)?$#',
        '#^reset-password(/.*)?$#',
        '#^locale/.+$#',
        '#^welcome/dismiss$#',
        '#^cookie/(accept|revoke|dismiss)$#',
        '#^onboarding/complete/[^/]+$#',
        '#^support(/.*)?$#',
        '#^notifications(/.*)?$#',
        '#^api/notifications(/.*)?$#',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'credenciado') {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        foreach (self::ALLOWED_PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return $next($request);
            }
        }

        // Request AJAX/JSON: 403 explicito ao inves de redirect (quebra fetch).
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error'      => 'Acesso restrito ao painel do credenciado.',
                'error_code' => 'credenciado_scope',
            ], 403);
        }

        return redirect('/credenciado')
            ->with('warning', 'Sua conta tem acesso apenas ao painel de projetos vinculados.');
    }
}
