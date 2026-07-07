<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\WhatsApp\Contracts\WhatsAppSenderInterface;

/**
 * Adaptador do EvolutionApiService (auto-hospedado) para a WhatsAppSenderInterface.
 *
 * Mantém o comportamento existente do EvolutionApiService — este wrapper apenas
 * normaliza o formato de resposta e implementa o contrato. Nenhuma lógica de envio
 * foi movida para cá; permanece em EvolutionApiService pra evitar regressão.
 */
class EvolutionWhatsAppSender implements WhatsAppSenderInterface
{
    public function __construct(
        private WhatsappInstance $instance,
        private EvolutionApiService $evo,
    ) {
    }

    public function providerName(): string
    {
        return WhatsappInstance::PROVIDER_EVOLUTION;
    }

    public function isConfigured(): bool
    {
        return !empty($this->instance->instance_name) && !empty($this->instance->instance_token);
    }

    public function sendText(string $to, string $text): array
    {
        $raw = $this->evo->sendMessage($to, $text, null, 0);
        return $this->normalize($raw);
    }

    public function sendMedia(string $to, string $type, string $media, string $caption = ''): array
    {
        // Evolution trata 'audio' via endpoint dedicado (sendWhatsAppAudio); demais via sendMedia
        if ($type === 'audio') {
            $raw = $this->evo->sendAudio($to, $media);
        } else {
            $mimetype = $this->guessMimetype($type);
            $raw = $this->evo->sendMedia($to, $media, $caption, $mimetype);
        }

        return $this->normalize($raw);
    }

    public function sendTemplate(string $to, string $templateName, string $languageCode = 'pt_BR', array $variables = []): array
    {
        // Evolution não suporta templates aprovados pela Meta; enviamos como texto livre
        // substituindo placeholders {{1}}, {{2}}, ... pelas variáveis.
        $text = $templateName;
        foreach ($variables as $i => $value) {
            $text = str_replace('{{' . ($i + 1) . '}}', (string) $value, $text);
        }

        return $this->sendText($to, $text);
    }

    private function guessMimetype(string $type): string
    {
        return match ($type) {
            'image'    => 'image/jpeg',
            'video'    => 'video/mp4',
            'document' => 'application/pdf',
            default    => 'application/octet-stream',
        };
    }

    private function normalize(array $raw): array
    {
        $error = $raw['error'] ?? null;
        $messageId = $raw['key']['id'] ?? $raw['messageId'] ?? null;

        return [
            'ok'                  => $error === null && $messageId !== null,
            'provider'            => $this->providerName(),
            'provider_message_id' => $messageId,
            'error'               => is_string($error) ? $error : null,
            'raw'                 => $raw,
        ];
    }
}
