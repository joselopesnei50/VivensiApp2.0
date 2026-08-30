<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Banner::class        => \App\Policies\BannerPolicy::class,
        \App\Models\SocialAccount::class => \App\Policies\SocialAccountPolicy::class,
        \App\Models\ScheduledPost::class => \App\Policies\ScheduledPostPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // super_admin sempre passa — verificado antes de qualquer Gate/Policy
        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'super_admin') return true;
            return null;
        });

        // ── Gates de módulo ───────────────────────────────────────────────────
        // Verifica spatie permissions (quando seeder rodou) com fallback para
        // coluna role. O try/catch absorve PermissionDoesNotExist em ambientes
        // de teste onde o seeder ainda não rodou.

        $perm = function (User $user, string $permission): bool {
            try {
                return $user->hasPermissionTo($permission);
            } catch (\Throwable) {
                return false;
            }
        };

        Gate::define('access-whatsapp', function (User $user) use ($perm) {
            return $perm($user, 'access-whatsapp') || in_array($user->role, ['manager', 'ngo', 'common', 'client']);
        });

        // Bloco 3 — Gate específico pra WhatsApp Cloud API (per-plano).
        // Fica separado de access-whatsapp: quem NÃO tem esse gate ainda pode
        // usar Evolution API (legado, gratuito). O gate combina role + plano.
        Gate::define('has-whatsapp-cloud', function (User $user) {
            if (!in_array($user->role, ['manager', 'ngo', 'common', 'client'], true)) {
                return false;
            }
            return $user->tenant?->hasCapability('whatsapp_cloud') ?? true;
        });

        Gate::define('access-manager', function (User $user) use ($perm) {
            return $perm($user, 'access-manager') || in_array($user->role, ['manager', 'ngo', 'common', 'client']);
        });

        Gate::define('access-admin', function (User $user) {
            return $user->role === 'super_admin';
        });

        Gate::define('access-social-ai', function (User $user) use ($perm) {
            return $perm($user, 'access-social-ai') || in_array($user->role, ['manager', 'ngo', 'common', 'client']);
        });

        Gate::define('manage-settings', function (User $user) use ($perm) {
            return $perm($user, 'manage-settings') || $user->role === 'super_admin';
        });

        Gate::define('manage-donors', function (User $user) use ($perm) {
            return $perm($user, 'manage-donors') || in_array($user->role, ['ngo', 'super_admin']);
        });

        Gate::define('manage-grants', function (User $user) use ($perm) {
            return $perm($user, 'manage-grants') || in_array($user->role, ['ngo', 'super_admin']);
        });

        Gate::define('manage-projects', function (User $user) use ($perm) {
            return $perm($user, 'manage-projects') || in_array($user->role, ['manager', 'super_admin']);
        });

        Gate::define('manage-broadcast', function (User $user) use ($perm) {
            return $perm($user, 'manage-broadcast') || in_array($user->role, ['manager', 'ngo', 'super_admin', 'client']);
        });

        // Deletar beneficiario (individual ou bulk) e restrito ao administrador
        // da conta — employee opera o modulo (CRUD, atendimentos, exports) mas
        // nao remove cadastros. Decisao do dono em 2026-07-31.
        Gate::define('delete-beneficiaries', function (User $user) use ($perm) {
            return $perm($user, 'delete-beneficiaries') || in_array($user->role, ['ngo', 'manager', 'common', 'client']);
        });

        // Gerenciar equipe (criar/editar/remover usuarios do tenant e definir
        // role deles) — restrito ao administrador da conta pra fechar bypass
        // de privilege escalation (auditoria 2026-08-29, achado critico #1).
        Gate::define('manage-team', function (User $user) use ($perm) {
            return $perm($user, 'manage-team') || $user->role === 'ngo';
        });

        // RH da OSC — folha, funcionarios com CPF/PIS/salario, voluntarios,
        // certificados. Auditoria 2026-08-29 achado #2 (alta).
        Gate::define('manage-hr', function (User $user) use ($perm) {
            return $perm($user, 'manage-hr') || $user->role === 'ngo';
        });

        // Portal publico de Transparencia (conselho, docs, parcerias). Afeta
        // pagina externa. Auditoria 2026-08-29 achado #3 (alta).
        Gate::define('manage-transparency', function (User $user) use ($perm) {
            return $perm($user, 'manage-transparency') || $user->role === 'ngo';
        });

        // SIC — Servico de Informacao ao Cidadao (LAI). Responder e mudar
        // status de chamado oficial. Auditoria 2026-08-29 achado #4 (alta).
        Gate::define('manage-sic', function (User $user) use ($perm) {
            return $perm($user, 'manage-sic') || $user->role === 'ngo';
        });
    }
}
