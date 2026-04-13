<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudApiService
{
    protected $wabaId;
    protected $phoneNumberId;
    protected $accessToken;
    protected $apiVersion;

    /**
     * @param \Illuminate\Database\Eloquent\Model|null $contextModel (App\Models\Tenant, User, ou WhatsappConfig)
     */
    public function __construct($contextModel = null)
    {
        $this->apiVersion = config('whatsapp.meta_api_version', 'v20.0');

        if ($contextModel) {
            $this->wabaId = $contextModel->meta_waba_id;
            $this->phoneNumberId = $contextModel->meta_phone_number_id;
            $this->accessToken = $contextModel->meta_access_token;
        }
    }

    /**
     * Valida se a instância possui as credenciais da Meta configuradas.
     */
    public function isConfigured(): bool
    {
        return !empty($this->phoneNumberId) && !empty($this->accessToken);
    }

    /**
     * Envia uma mensagem de texto livre (Requer Janela de 24h aberta).
     */
    public function sendTextMessage(string $to, string $text): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'Meta Cloud API credentials missing'];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatNumber($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text
            ]
        ];

        return $this->dispatchRequest($url, $payload);
    }

    /**
     * Envia um Modelo de Mensagem (Whatsapp Template) para campanhas.
     * @param array $variables Lista simples de strings para substituir as variáveis {{1}}, {{2}}, etc.
     */
    public function sendTemplateMessage(string $to, string $templateName, string $languageCode = 'pt_BR', array $variables = []): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'Meta Cloud API credentials missing'];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $components = [];
        if (!empty($variables)) {
            $parameters = [];
            foreach ($variables as $var) {
                $parameters[] = ['type' => 'text', 'text' => (string) $var];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components
            ]
        ];

        return $this->dispatchRequest($url, $payload);
    }

    /**
     * Marca uma mensagem como lida (envia read receipt ao remetente).
     * Deve ser chamado logo após receber e processar a mensagem inbound.
     */
    public function markMessageAsRead(string $messageId): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'Meta Cloud API credentials missing'];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        return $this->dispatchRequest($url, [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ]);
    }

    /**
     * Verifica a assinatura HMAC-SHA256 enviada pela Meta no header X-Hub-Signature-256.
     * Deve ser chamado no webhook handler antes de processar qualquer payload.
     *
     * @param string $rawBody    Corpo bruto da requisição (não decodificado)
     * @param string $signature  Valor do header X-Hub-Signature-256
     * @param string $appSecret  Meta App Secret (META_APP_SECRET no .env)
     */
    public static function verifyWebhookSignature(string $rawBody, string $signature, string $appSecret): bool
    {
        if (empty($appSecret) || empty($signature)) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Envia um Modelo com suporte a componente de HEADER (texto, imagem ou documento).
     *
     * @param string      $to           Número do destinatário
     * @param string      $templateName Nome do template aprovado
     * @param string      $languageCode Código de idioma (ex: pt_BR)
     * @param array       $bodyVars     Variáveis do corpo {{1}}, {{2}}...
     * @param array|null  $header       ['type' => 'text|image|document', 'value' => 'texto ou URL']
     */
    public function sendTemplateMessageFull(
        string $to,
        string $templateName,
        string $languageCode = 'pt_BR',
        array  $bodyVars     = [],
        ?array $header       = null
    ): array {
        if (!$this->isConfigured()) {
            return ['error' => 'Meta Cloud API credentials missing'];
        }

        $url        = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
        $components = [];

        // Header component (opcional)
        if (!empty($header)) {
            $type = $header['type'] ?? 'text';

            if ($type === 'text') {
                $components[] = [
                    'type'       => 'header',
                    'parameters' => [['type' => 'text', 'text' => (string) ($header['value'] ?? '')]],
                ];
            } elseif (in_array($type, ['image', 'video', 'document'], true)) {
                $components[] = [
                    'type'       => 'header',
                    'parameters' => [[
                        'type'  => $type,
                        $type   => ['link' => (string) ($header['value'] ?? '')],
                    ]],
                ];
            }
        }

        // Body component
        if (!empty($bodyVars)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], $bodyVars),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->formatNumber($to),
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->dispatchRequest($url, $payload);
    }

    /**
     * Busca os templates aprovados na conta do WhatsApp Business da ONG.
     */
    public function getTemplates(): array
    {
        if (empty($this->wabaId) || empty($this->accessToken)) {
            return ['error' => 'WABA ID or Access Token missing'];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->wabaId}/message_templates";

        try {
            $response = Http::withToken($this->accessToken)
                ->get($url, ['limit' => 100, 'fields' => 'name,status,category,language,components']);

            if ($response->successful()) {
                // Retorna o objeto completo: ['data' => [...], 'paging' => [...]]
                return $response->json();
            }
            
            Log::error("Meta getTemplates falhou: " . $response->body());
            return ['error' => 'Falha ao buscar templates', 'details' => $response->body()];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Despacha a requisição HTTP para a Graph API.
     */
    protected function dispatchRequest(string $url, array $payload): array
    {
        try {
            $response = Http::timeout(10)
                ->withToken($this->accessToken)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('Meta Cloud API Error: ', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'payload' => $payload
                ]);
                
                return [
                    'error' => 'Failed to send message',
                    'status' => $response->status(),
                    'details' => $response->body()
                ];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Meta Cloud API Exception: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Baixa um arquivo de mídia da Meta Cloud API.
     * @param string $mediaId ID da mídia retornado pelo Webhook.
     * @return array ['extension' => string, 'binary' => string] ou ['error' => string]
     */
    public function downloadMedia(string $mediaId): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'Meta Cloud API credentials missing'];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$mediaId}";

        try {
            // 1. Obter a URL de download
            $response = Http::withToken($this->accessToken)->get($url);

            if ($response->failed()) {
                return ['error' => 'Failed to get media URL', 'details' => $response->body()];
            }

            $mediaData = $response->json();
            $downloadUrl = $mediaData['url'] ?? null;
            $mimeType = $mediaData['mime_type'] ?? 'image/jpeg';

            if (!$downloadUrl) {
                return ['error' => 'Media URL not found in response'];
            }

            // 2. Baixar o binário
            $fileResponse = Http::withToken($this->accessToken)->get($downloadUrl);

            if ($fileResponse->failed()) {
                return ['error' => 'Failed to download binary', 'details' => $fileResponse->body()];
            }

            // Mapeamento simples de extensões
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'audio/ogg' => 'ogg',
                'audio/mpeg' => 'mp3',
                'audio/mp4' => 'm4a',
                'audio/amr' => 'amr',
                'application/pdf' => 'pdf',
            ];

            return [
                'extension' => $extensions[$mimeType] ?? 'bin',
                'mime_type' => $mimeType,
                'binary' => $fileResponse->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Meta Cloud Media Download Exception: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Limpa a formatação mantendo apenas dígitos (Meta Cloud exige formato internacional sem o +).
     */
    protected function formatNumber(string $number): string
    {
        return preg_replace('/\D/', '', $number);
    }
}
