<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Evolution API Service 2.0 (Optimized for VPS Local Integration)
 */
class EvolutionApiService
{
    protected $instanceName;
    protected $apiKey;
    protected $globalApiKey;
    protected $baseUrl;
    protected $contextModel;

    /**
     * @param \Illuminate\Database\Eloquent\Model|null $contextModel (WhatsappInstance, Tenant ou User)
     */
    public function __construct($contextModel = null)
    {
        $this->baseUrl      = rtrim(config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br')), '/');
        $this->globalApiKey = config('whatsapp.evolution_global_key');
        $this->contextModel = $contextModel;

        if ($contextModel) {
            $this->instanceName = $contextModel->evolution_instance_name ?? '';
            $this->apiKey = !empty($contextModel->evolution_instance_token)
                ? $contextModel->evolution_instance_token
                : $this->globalApiKey;
        }
    }

    /**
     * Cria uma instância na Evolution API.
     * NÃO enviar 'number' — causa TypeError na v2.3.6 com QR Code.
     */
    public function createInstance(string $name, string $clientToken = null, ?string $number = null): array
    {
        $appUrl     = rtrim(config('app.url'), '/');
        $webhookUrl = $appUrl . '/api/evo/webhook/' . $clientToken;

        // Evolution API v2.3.6: NÃO enviar qrcode:true nem campos de configuração
        // durante a criação — causa "TypeError: Cannot read properties of undefined
        // (reading 'state')". Payload mínimo + webhook apenas. QR é buscado depois
        // via /instance/connect pelo método connect().
        $payload = [
            'instanceName' => $name,
            'integration'  => 'WHATSAPP-BAILEYS',
            'webhook'      => [
                'enabled'  => true,
                'url'      => $webhookUrl,
                'byEvents' => false,
                'base64'   => true,
                'events'   => ['QRCODE_UPDATED', 'CONNECTION_UPDATE', 'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'SEND_MESSAGE'],
            ],
        ];

        Log::info('EVO createInstance', ['name' => $name, 'webhook' => $webhookUrl]);

        try {
            $response = $this->http()->timeout(45)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post("{$this->baseUrl}/instance/create", $payload);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error('EVOLUTION CREATE INSTANCE FAILED', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);
            return ['error' => 'Falha na criação da instância', 'details' => $response->body()];
        } catch (\Exception $e) {
            Log::error('EVOLUTION CREATE INSTANCE EXCEPTION', [
                'message' => $e->getMessage(),
                'instance' => $name
            ]);
            return ['error' => 'Exceção na criação (API Fora?): ' . $e->getMessage()];
        }
    }

    /**
     * Busca o QR Code da instância diretamente na Evolution API.
     * Logging detalhado para diagnóstico da estrutura real de resposta.
     */
    public function fetchConnectionCode(string $instanceName): array
    {
        try {
            $response = $this->http()->timeout(8)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");

            if ($response->successful()) {
                $data = $response->json();

                // Log da estrutura real — essencial para diagnosticar incompatibilidade
                Log::info('EVO fetchConnectionCode response', [
                    'instance' => $instanceName,
                    'keys'     => is_array($data) ? array_keys($data) : 'not-array',
                    'data'     => $data,
                ]);

                // Tenta todas as estruturas conhecidas da Evolution API
                $qrBase64 = $data['base64']
                    ?? ($data['qrcode']['base64'] ?? null)
                    ?? ($data['code'] ?? null)
                    ?? (is_string($data['qrcode'] ?? null) ? $data['qrcode'] : null);

                if ($qrBase64) {
                    return ['qrcode' => $qrBase64, 'status' => 'connecting'];
                }

                // QR não disponível ainda — pode estar gerando
                return ['error' => 'QR ainda não disponível', 'status' => 'generating'];
            }

            Log::warning('EVO fetchConnectionCode HTTP error', [
                'instance' => $instanceName,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            return ['error' => 'API retornou ' . $response->status(), 'status' => 'generating'];
        } catch (\Exception $e) {
            Log::warning('EVO fetchConnectionCode exception', ['error' => $e->getMessage()]);
            return ['error' => 'Aguardando API...', 'status' => 'generating'];
        }
    }

    public function getConnectionState(): array
    {
        if (!$this->instanceName) return ['error' => 'Not configured'];

        try {
            $response = $this->http()->timeout(5)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");

            return $response->json() ?? ['error' => 'Empty response'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function sendMessage(string $to, string $message, ?string $idempotencyKey = null, int $delaySeconds = 0): array
    {
        if (!$this->instanceName || !$this->apiKey) return ['error' => 'Evolution API Missing Config'];

        $renderedMessage = $this->applySpintax($message);

        $payload = [
            'number' => (string) $to,
            'text'   => $renderedMessage,
            'delay'  => $delaySeconds > 0 ? $delaySeconds * 1000 : 1200,
        ];

        try {
            $response = $this->http()->timeout(15)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", $payload);

            if ($response->failed()) {
                Log::error('EVOLUTION API REJEITOU O ENVIO', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return ['error' => 'Failed to send', 'details' => $response->body()];
            }

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    public function sendMedia(string $to, string $media, string $caption = '', string $mimetype = 'image/jpeg'): array
    {
        if (!$this->instanceName) return ['error' => 'No instance configured'];

        $renderedCaption = $caption ? $this->applySpintax($caption) : '';

        // Derive mediatype (image/video/document) from mimetype
        $mediaType = explode('/', $mimetype)[0];
        if (!in_array($mediaType, ['image', 'video', 'audio'])) $mediaType = 'image';

        $payload = [
            'number'    => (string) $to,
            'mediatype' => $mediaType,
            'mimetype'  => $mimetype,
            'caption'   => $renderedCaption,
            'media'     => $media,
            'fileName'  => 'broadcast.' . explode('/', $mimetype)[1],
        ];

        // Se não for uma URL, assumimos que é Base64 e garantimos o prefixo (apenas no campo media)
        if (!str_starts_with($media, 'http')) {
            if (!str_starts_with($media, 'data:')) {
                $payload['media'] = "data:{$mimetype};base64,{$media}";
            }
        }

        try {
            $response = $this->http()->timeout(20)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post("{$this->baseUrl}/message/sendMedia/{$this->instanceName}", $payload);

            if ($response->failed()) {
                Log::error('EVOLUTION API REJEITOU ENVIO DE MÍDIA', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return ['error' => 'Failed to send media', 'details' => $response->body()];
            }

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Envia áudio como Push-to-Talk (PTT) — formato nativo de voz do WhatsApp.
     * Usar ptt:true reduz drasticamente o risco de ban pois imita gravação humana.
     */
    public function sendAudio(string $to, string $base64Audio): array
    {
        if (!$this->instanceName) return ['error' => 'No instance configured'];

        $payload = [
            'number'    => (string) $to,
            'audio'     => $base64Audio,
            'encoding'  => true, // Evolution API converte para o codec correto automaticamente
        ];

        try {
            $response = $this->http()->timeout(30)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post("{$this->baseUrl}/message/sendWhatsAppAudio/{$this->instanceName}", $payload);

            if ($response->failed()) {
                Log::error('EVOLUTION API REJEITOU ÁUDIO', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return ['error' => 'Failed to send audio', 'details' => $response->body()];
            }

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    public function applySpintax(string $text): string
    {
        return preg_replace_callback('/\{(((?>[^\{\}]+)|(?R))*)\}/x', function ($match) {
            $options = explode('|', $match[1]);
            return $options[array_rand($options)];
        }, $text);
    }

    /**
     * Solicita um Pairing Code para conectar o WhatsApp via número de telefone.
     */
    public function getPairingCode(string $phone): array
    {
        if (!$this->instanceName) return ['error' => 'No instance configured'];

        $cleanPhone = preg_replace('/\D/', '', $phone);

        try {
            $response = $this->http()->timeout(15)
                ->withHeaders(['apikey' => $this->globalApiKey])
                ->post("{$this->baseUrl}/instance/pairingCode/{$this->instanceName}", [
                    'number' => $cleanPhone,
                ]);

            if ($response->successful()) {
                return $response->json() ?? ['error' => 'Empty response'];
            }

            return [
                'error'   => 'Pairing Code request failed',
                'status'  => $response->status(),
                'details' => $response->body(),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    public function logout(): array
    {
        if (!$this->instanceName) return ['error' => 'No instance'];
        try {
            $response = $this->http()->timeout(5)
                ->withHeaders(['apikey' => $this->globalApiKey])
                ->delete("{$this->baseUrl}/instance/logout/{$this->instanceName}");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['error' => 'Logout skipped (API unreachable): ' . $e->getMessage()];
        }
    }

    /**
     * Remove completamente a instância da Evolution API.
     */
    public function deleteInstance(): array
    {
        if (!$this->instanceName) return ['error' => 'No instance'];
        try {
            $response = $this->http()->timeout(5)
                ->withHeaders(['apikey' => $this->globalApiKey])
                ->delete("{$this->baseUrl}/instance/delete/{$this->instanceName}");
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['error' => 'Delete skipped (API unreachable): ' . $e->getMessage()];
        }
    }

    /**
     * Retorna os JIDs dos participantes de um grupo específico.
     * Usado no broadcast modo "members" (mensagem individual para cada membro).
     */
    public function getGroupMembers(string $groupId): array
    {
        if (!$this->instanceName || !$this->apiKey) return [];
        try {
            $response = $this->http()->timeout(45)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/group/fetchAllGroups/{$this->instanceName}", [
                'getParticipants' => 'true',
            ]);

            if ($response->failed()) return [];
            $data = $response->json();
            if (!is_array($data)) return [];

            // Normaliza resposta (array_is_list() requer PHP 8.1 — usa alternativa compatível com 8.0)
            $isList = array_keys($data) === range(0, count($data) - 1);
            $groups = $data['data'] ?? $data['groups'] ?? ($isList ? $data : []);

            foreach ($groups as $group) {
                $id = $group['id'] ?? '';
                if ($id !== $groupId) continue;

                $participants = $group['participants'] ?? [];
                return collect($participants)
                    ->map(fn($p) => is_array($p) ? ($p['id'] ?? null) : $p)
                    ->filter(fn($jid) => $jid && str_ends_with($jid, '@s.whatsapp.net'))
                    ->values()
                    ->all();
            }
            return [];
        } catch (\Exception $e) {
            Log::error('EvolutionAPI getGroupMembers error: ' . $e->getMessage());
            return [];
        }
    }

    public function getGroups(): array
    {
        if (!$this->instanceName || !$this->apiKey) return [];
        try {
            $response = $this->http()->timeout(45)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/group/fetchAllGroups/{$this->instanceName}", [
                'getParticipants' => 'false',
            ]);
            if ($response->failed()) return [];
            $data = $response->json();
            
            if (!is_array($data)) {
                return [];
            }
            
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }
            if (isset($data['groups']) && is_array($data['groups'])) {
                return $data['groups'];
            }
            if (isset($data['instances']) && is_array($data['instances'])) {
                return $data['instances'];
            }
            
            if (array_keys($data) === range(0, count($data) - 1)) {
                return $data;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('EvolutionAPI getGroups error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna cliente HTTP com SSL configurado corretamente.
     * Laravel 9 usa withoutVerifying() — não existe withSslVerification().
     */
    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        $shouldVerify = app()->environment('production')
            && !str_contains($this->baseUrl, 'localhost')
            && !str_contains($this->baseUrl, '127.0.0.1');

        return $shouldVerify
            ? \Illuminate\Support\Facades\Http::withOptions(['verify' => true])
            : \Illuminate\Support\Facades\Http::withoutVerifying();
    }
}
