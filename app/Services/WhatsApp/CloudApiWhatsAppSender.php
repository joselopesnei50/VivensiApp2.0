<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappInstance;
use App\Services\Messaging\MetaCloudApiService;
use App\Services\WhatsApp\Contracts\WhatsAppSenderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adaptador do MetaCloudApiService (Graph API) para a WhatsAppSenderInterface.
 *
 * Reutiliza MetaCloudApiService para text e template (métodos já existentes).
 * Implementa sendMedia diretamente porque a Cloud API exige URL pública OU
 * media_id retornado do endpoint /media (upload prévio).
 */
class CloudApiWhatsAppSender implements WhatsAppSenderInterface
{
    private string $apiVersion;

    public function __construct(
        private WhatsappInstance $instance,
        private MetaCloudApiService $meta,
    ) {
        $this->apiVersion = config('whatsapp.meta_api_version', 'v20.0');
    }

    public function providerName(): string
    {
        return WhatsappInstance::PROVIDER_CLOUD_API;
    }

    public function isConfigured(): bool
    {
        return !empty($this->instance->phone_number_id) && !empty($this->instance->graph_access_token);
    }

    public function sendText(string $to, string $text): array
    {
        $raw = $this->meta->sendTextMessage($to, $text);
        return $this->normalize($raw);
    }

    public function sendMedia(string $to, string $type, string $media, string $caption = ''): array
    {
        if (!$this->isConfigured()) {
            return $this->normalize(['error' => 'Meta Cloud API credentials missing']);
        }

        // Cloud API aceita 'link' (URL pública) OU 'id' (media_id upload prévio).
        // Se veio URL, mandamos direto; se veio base64/local, precisaria upload — não suportado no MVP.
        $mediaField = str_starts_with($media, 'http') ? ['link' => $media] : ['id' => $media];

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => preg_replace('/\D/', '', $to),
            'type'              => $type,
            $type               => $mediaField + ($caption !== '' && $type !== 'audio' ? ['caption' => $caption] : []),
        ];

        try {
            $response = Http::timeout(10)
                ->withToken($this->instance->graph_access_token)
                ->post("https://graph.facebook.com/{$this->apiVersion}/{$this->instance->phone_number_id}/messages", $payload);

            if ($response->failed()) {
                Log::error('Cloud API sendMedia failed', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->normalize(['error' => 'Failed to send media', 'details' => $response->body()]);
            }

            return $this->normalize($response->json());
        } catch (\Exception $e) {
            Log::error('Cloud API sendMedia exception: ' . $e->getMessage());
            return $this->normalize(['error' => $e->getMessage()]);
        }
    }

    public function sendTemplate(string $to, string $templateName, string $languageCode = 'pt_BR', array $variables = []): array
    {
        $raw = $this->meta->sendTemplateMessage($to, $templateName, $languageCode, $variables);
        return $this->normalize($raw);
    }

    private function normalize(array $raw): array
    {
        $error = $raw['error'] ?? null;
        $messageId = $raw['messages'][0]['id'] ?? null;

        return [
            'ok'                  => $error === null && $messageId !== null,
            'provider'            => $this->providerName(),
            'provider_message_id' => $messageId,
            'error'               => is_string($error) ? $error : (is_array($error) ? ($error['message'] ?? 'unknown') : null),
            'raw'                 => $raw,
        ];
    }
}
