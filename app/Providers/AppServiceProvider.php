<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Broadcasting dinâmico via SystemSetting — só ativa após migrations
        // Configurar manualmente via config/broadcasting.php ou painel admin

        // Registrar Observers para Geocodificação Automática
        try {
            \App\Models\Beneficiary::observe(\App\Observers\BeneficiaryObserver::class);
            \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        } catch (\Throwable $e) {
            //
        }
    }
}
