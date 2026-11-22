<?php

namespace App\Jobs;

use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Apaga "para todos" as mensagens de uma troca com o bot de gestão interna
 * (o que o cliente digitou + a resposta do bot).
 *
 * Roda com atraso (ProcessWhatsAppBotMessage::PURGE_DELAY_MINUTES) para o
 * cliente conseguir ler a resposta antes — apagar na hora fazia a resposta
 * virar "Mensagem apagada" no celular dele (decisão do dono em 2026-11-22).
 */
class PurgeBotMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    /**
     * @param  array<int, array{id:?string, remoteJid:?string, fromMe:bool, participant:?string}>  $keys
     */
    public function __construct(
        public string $instanceName,
        public array $keys,
    ) {}

    public function handle(): void
    {
        $context = new \stdClass;
        $context->evolution_instance_name = $this->instanceName;
        $context->evolution_instance_token = null;
        $evo = new EvolutionApiService($context);

        foreach ($this->keys as $key) {
            if (! empty($key['id']) && ! empty($key['remoteJid'])) {
                $evo->deleteMessageForEveryone($key);
            }
        }
    }
}
