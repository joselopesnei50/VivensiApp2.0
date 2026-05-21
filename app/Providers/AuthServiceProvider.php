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
            return $perm($user, 'access-whatsapp') || in_array($user->role, ['manager', 'ngo', 'common']);
        });

        Gate::define('access-manager', function (User $user) use ($perm) {
            return $perm($user, 'access-manager') || in_array($user->role, ['manager', 'ngo', 'common']);
        });

        Gate::define('access-admin', function (User $user) {
            return $user->role === 'super_admin';
        });

        Gate::define('access-social-ai', function (User $user) use ($perm) {
            return $perm($user, 'access-social-ai') || in_array($user->role, ['manager', 'ngo', 'common']);
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
            return $perm($user, 'manage-broadcast') || in_array($user->role, ['manager', 'ngo', 'super_admin']);
        });
    }
}
