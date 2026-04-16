<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Trials: lembrete de vencimento — todo dia às 09:00
        $schedule->command('trials:remind')->dailyAt('09:00');

        // WhatsApp: limpeza de dados antigos — todo dia às 03:30
        $days = (int) config('whatsapp.retention_days', 365);
        if ($days > 0) {
            $schedule->command("whatsapp:cleanup --days={$days}")->dailyAt('03:30');
        }

        // Redes Sociais: publica posts agendados a cada minuto
        $schedule->command('posts:publish')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Relatório Semanal com Bruce AI — todo domingo às 08:00
        $schedule->job(new \App\Jobs\SendWeeklyReportJob())
                 ->weeklyOn(0, '08:00')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('SendWeeklyReportJob falhou.');
                 });

        // Backup automático do banco — todo dia às 02:00
        $schedule->command('db:backup')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('✅ Backup diário concluído.');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('❌ Falha no backup diário do banco!');
                 });

        // Health-check: confirma que o scheduler está rodando a cada hora
        $schedule->call(function () {
            \Illuminate\Support\Facades\Log::info('🟢 Scheduler alive — ' . now()->toDateTimeString());
        })->hourly()->name('scheduler:health-check')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     * IMPORTANT: $this->load() já carrega todos os comandos de /Commands automaticamente.
     * NÃO chamar $this->commands([...]) aqui — causa recursão infinita!
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
