<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\OpenPix\PhpSdk\Client::class, function ($app) {
            try {
                $appId = \App\Models\SystemSetting::getValue('openpix_app_id');
                return \OpenPix\PhpSdk\Client::create($appId ?? '');
            } catch (\Throwable $e) {
                return \OpenPix\PhpSdk\Client::create('');
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Configuração Dinâmica de Broadcasting (Pusher/Soketi)
        try {
            if (class_exists(\App\Models\SystemSetting::class) && Schema::hasTable('system_settings')) {
                $pusher_app_id = \App\Models\SystemSetting::getValue('pusher_app_id');
                if ($pusher_app_id) {
                    config([
                        'broadcasting.connections.pusher.app_id' => $pusher_app_id,
                        'broadcasting.connections.pusher.key'    => \App\Models\SystemSetting::getValue('pusher_app_key'),
                        'broadcasting.connections.pusher.secret' => \App\Models\SystemSetting::getValue('pusher_app_secret'),
                        'broadcasting.connections.pusher.options.host'   => \App\Models\SystemSetting::getValue('pusher_host', '127.0.0.1'),
                        'broadcasting.connections.pusher.options.port'   => \App\Models\SystemSetting::getValue('pusher_port', '6001'),
                        'broadcasting.connections.pusher.options.scheme' => \App\Models\SystemSetting::getValue('pusher_scheme', 'http'),
                        'broadcasting.connections.pusher.options.useTLS' => \App\Models\SystemSetting::getValue('pusher_scheme', 'http') === 'https',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Tabela ainda não existe (primeiro deploy / migrations pendentes)
        }

        // Registrar Observers para Geocodificação Automática
        try {
            \App\Models\Beneficiary::observe(\App\Observers\BeneficiaryObserver::class);
            \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        } catch (\Throwable $e) {
            //
        }
    }
}
