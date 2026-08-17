<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class BotWebhookConfigure extends Command
{
    protected $signature = 'bot:webhook-configure
                            {--instance= : Nome da instancia Evolution (padrao: SystemSetting bot_instance_name)}
                            {--show : So mostra o webhook atual, nao altera}';

    protected $description = 'Configura o webhook da instancia Evolution do bot interno pra apontar pro /api/whatsapp/bot?bot_token=... do Vivensi. Fix pra webhook criado sem token que retornava 401 silencioso.';

    public function handle(): int
    {
        $instance = $this->option('instance') ?: SystemSetting::getValue('bot_instance_name');
        if (!$instance) {
            $this->error('Sem bot_instance_name em SystemSetting. Passe --instance=NOME.');
            return self::FAILURE;
        }

        $evoUrl    = rtrim((string) config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL')), '/');
        $evoKey    = (string) config('whatsapp.evolution_global_key', env('EVOLUTION_GLOBAL_KEY'));
        $botSecret = (string) config('services.whatsapp.bot_secret');

        if (!$evoUrl || !$evoKey) {
            $this->error('EVOLUTION_API_URL ou EVOLUTION_GLOBAL_KEY nao configurados no .env');
            return self::FAILURE;
        }
        if (!$botSecret) {
            $this->error('WHATSAPP_BOT_SECRET nao configurado no .env (services.whatsapp.bot_secret vazio)');
            return self::FAILURE;
        }

        $appUrl     = rtrim((string) config('app.url'), '/');
        $webhookUrl = $appUrl . '/api/whatsapp/bot?bot_token=' . urlencode($botSecret);

        $this->info("Evolution:    {$evoUrl}");
        $this->info("Instance:     {$instance}");
        $this->info("Webhook URL:  {$webhookUrl}");
        $this->line('');

        // Consulta webhook atual antes de mexer
        $find = Http::timeout(15)->withHeaders(['apikey' => $evoKey])
            ->get("{$evoUrl}/webhook/find/{$instance}");

        $this->line('==> Webhook atual na Evolution:');
        $this->line($find->body() ?: '(vazio)');
        $this->line('');

        if ($this->option('show')) {
            return self::SUCCESS;
        }

        // Sobrescreve
        $payload = [
            'webhook' => [
                'enabled'  => true,
                'url'      => $webhookUrl,
                'byEvents' => false,
                'base64'   => false,
                'events'   => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
            ],
        ];

        $set = Http::timeout(15)->withHeaders(['apikey' => $evoKey])
            ->post("{$evoUrl}/webhook/set/{$instance}", $payload);

        $this->line('==> Resposta do webhook/set:');
        $this->line($set->body() ?: '(vazio)');
        $this->line('');

        if (!$set->successful()) {
            $this->error('Evolution retornou HTTP ' . $set->status());
            return self::FAILURE;
        }

        // Re-consulta pra confirmar
        $verify = Http::timeout(15)->withHeaders(['apikey' => $evoKey])
            ->get("{$evoUrl}/webhook/find/{$instance}");

        $this->line('==> Confirmacao (webhook/find depois):');
        $this->line($verify->body() ?: '(vazio)');

        $body = (array) $verify->json();
        $urlFound = $body['url'] ?? ($body['webhook']['url'] ?? null);
        if ($urlFound && str_contains($urlFound, 'bot_token=')) {
            $this->line('');
            $this->info('OK — webhook cadastrado com bot_token.');
            return self::SUCCESS;
        }

        $this->warn('Webhook cadastrado mas nao conferi bot_token no retorno. Verifique manualmente.');
        return self::SUCCESS;
    }
}
