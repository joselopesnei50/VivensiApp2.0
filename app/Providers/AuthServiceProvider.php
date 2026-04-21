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

        // Acesso ao módulo WhatsApp (chat, broadcast, configurações)
        Gate::define('access-whatsapp', function (User $user) {
            return $user->role === 'super_admin'
                || in_array($user->role, ['manager', 'ngo'])
                || ($user->tenant && $user->tenant->type === 'ngo');
        });

        // Acesso às funcionalidades de gestor
        Gate::define('access-manager', function (User $user) {
            return in_array($user->role, ['manager', 'super_admin']);
        });

        // Acesso exclusivo de super_admin
        Gate::define('access-admin', function (User $user) {
            return $user->role === 'super_admin';
        });

        // super_admin ignora todas as Policies automaticamente
        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'super_admin') return true;
            return null;
        });
    }
}
