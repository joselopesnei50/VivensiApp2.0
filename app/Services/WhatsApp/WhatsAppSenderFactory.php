<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\MetaCloudApiService;
use App\Services\WhatsApp\Contracts\WhatsAppSenderInterface;
use InvalidArgumentException;

/**
 * Factory que resolve o sender correto pra uma WhatsappInstance,
 * baseado na coluna `provider`.
 *
 * Uso típico:
 *   $sender = WhatsAppSenderFactory::forInstance($instance);
 *   $sender->sendText('5511999998888', 'Olá');
 */
class WhatsAppSenderFactory
{
    public static function forInstance(WhatsappInstance $instance): WhatsAppSenderInterface
    {
        if ($instance->isCloudApi()) {
            // Cria proxy pro MetaCloudApiService populado com credenciais da INSTÂNCIA
            $context = new class ($instance) {
                public string $meta_waba_id;
                public string $meta_phone_number_id;
                public string $meta_access_token;

                public function __construct(WhatsappInstance $i)
                {
                    $this->meta_waba_id         = (string) ($i->waba_id ?? '');
                    $this->meta_phone_number_id = (string) ($i->phone_number_id ?? '');
                    $this->meta_access_token    = (string) ($i->graph_access_token ?? '');
                }
            };

            return new CloudApiWhatsAppSender($instance, new MetaCloudApiService($context));
        }

        if ($instance->isEvolution()) {
            return new EvolutionWhatsAppSender($instance, new EvolutionApiService($instance));
        }

        throw new InvalidArgumentException(
            "WhatsappInstance {$instance->id} has unknown provider: '{$instance->provider}'"
        );
    }
}
