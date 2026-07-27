<?php

namespace App\Http\Middleware;

use App\Models\ProjectMember;
use Closure;
use Illuminate\Http\Request;

/**
 * Restringe user com role=credenciado a um conjunto minimo de rotas.
 *
 * Users com role=credenciado sao vinculados a um ou mais projetos (via
 * ProjectMember) e so podem interagir com:
 *   - Painel proprio /credenciado/*
 *   - Rotas /projects/{id}/... APENAS se ele tem ProjectMember daquele projeto
 *   - Perfil, logout, 2FA, redefinicao de senha, suporte, notificacoes
 *
 * Qualquer outra coisa (dashboard, doadores, financeiro, admin, email-campaigns,
 * whatsapp etc) e redirecionada pro /credenciado (com aviso) ou 403 em ajax.
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
        '#^support(/.*)?$#',
        '#^notifications(/.*)?$#',
        '#^api/notifications(/.*)?$#',
    ];

    // Padrao para rotas /projects/{id}/... — libera se ProjectMember existe.
    // Group 1 captura o ID numerico do projeto.
    private const PROJECT_PATH_PATTERN = '#^projects/(\d+)(/.*)?$#';

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'credenciado') {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        // Whitelist estatica primeiro.
        foreach (self::ALLOWED_PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                return $next($request);
            }
        }

        // Rota do projeto: libera SO se o credenciado for member do projeto.
        // Consulta indexada em project_members(user_id, project_id) — leve.
        if (preg_match(self::PROJECT_PATH_PATTERN, $path, $m)) {
            $projectId = (int) $m[1];
            $isMember = ProjectMember::where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->where('tenant_id', $user->tenant_id)
                ->exists();

            if ($isMember) {
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
            ->with('warning', 'Sua conta tem acesso apenas aos projetos vinculados.');
    }
}
