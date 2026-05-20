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
        // Cada gate verifica spatie permissions (quando seeder rodou) com
        // fallback para coluna role (antes do seeder / usuários legados).

        Gate::define('access-whatsapp', function (User $user) {
            if ($user->hasPermissionTo('access-whatsapp')) return true;
            return in_array($user->role, ['manager', 'ngo', 'common']);
        });

        Gate::define('access-manager', function (User $user) {
            if ($user->hasPermissionTo('access-manager')) return true;
            return in_array($user->role, ['manager', 'ngo', 'common']);
        });

        Gate::define('access-admin', function (User $user) {
            return $user->role === 'super_admin';
        });

        Gate::define('access-social-ai', function (User $user) {
            if ($user->hasPermissionTo('access-social-ai')) return true;
            return in_array($user->role, ['manager', 'ngo', 'common']);
        });

        Gate::define('manage-settings', function (User $user) {
            if ($user->hasPermissionTo('manage-settings')) return true;
            return $user->role === 'super_admin';
        });

        Gate::define('manage-donors', function (User $user) {
            if ($user->hasPermissionTo('manage-donors')) return true;
            return in_array($user->role, ['ngo', 'super_admin']);
        });

        Gate::define('manage-grants', function (User $user) {
            if ($user->hasPermissionTo('manage-grants')) return true;
            return in_array($user->role, ['ngo', 'super_admin']);
        });

        Gate::define('manage-projects', function (User $user) {
            if ($user->hasPermissionTo('manage-projects')) return true;
            return in_array($user->role, ['manager', 'super_admin']);
        });

        Gate::define('manage-broadcast', function (User $user) {
            if ($user->hasPermissionTo('manage-broadcast')) return true;
            return in_array($user->role, ['manager', 'ngo', 'super_admin']);
        });
    }
}
