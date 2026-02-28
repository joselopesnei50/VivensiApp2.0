<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Evolution API Service v2
 * 
 * Substitui o ZApiService com suporte a Multi-Tenant (ONG e Gestores) e 
 * recursos nativos Anti-Ban (Spintax, delay, presença de digitação).
 */
class EvolutionApiService
{
    protected $instanceName;
    protected $apiKey; // Token/ApiKey da instância
    protected $globalApiKey;
    protected $baseUrl;
    protected $contextModel;

    /**
     * @param \Illuminate\Database\Eloquent\Model|null $contextModel (App\Models\Tenant ou App\Models\User)
     */
    public function __construct($contextModel = null)
    {
        $this->baseUrl = config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'http://localhost:8080'));
        $this->globalApiKey = config('whatsapp.evolution_global_key', env('EVOLUTION_GLOBAL_KEY', 'global-api-key-here'));
        $this->contextModel = $contextModel;

        if ($contextModel) {
            $this->instanceName = $contextModel->evolution_instance_name ?? '';
            // Usa o token da instância se disponível, senão usa a globalApiKey como fallback
            $this->apiKey = !empty($contextModel->evolution_instance_token)
                ? $contextModel->evolution_instance_token
                : $this->globalApiKey;
        }
    }

    public function createInstance(string $name, string $clientToken = null): array
    {
        $token = Str::random(32); // Usado para autenticar Webhooks e a própria Instância

        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->globalApiKey
        ])->post("{$this->baseUrl}/instance/create", [
            'instanceName' => $name,
            'token' => $token,
            'qrcode' => false,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        if ($response->successful()) {
            $this->setWebhook($name, $clientToken);
            return array_merge($response->json(), ['generated_token' => $token]);
        }

        Log::error('Evolution API: Error creating instance', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return ['error' => 'Failed to create instance', 'details' => $response->body()];
    }

    public function setWebhook(string $instanceName, string $clientToken = null)
    {
        $webhookUrl = config('app.url') . '/api/whatsapp/webhook';
        if ($clientToken) {
            $webhookUrl .= '?token=' . urlencode($clientToken);
        }

        try {
            Http::timeout(15)->withHeaders([
                'apikey' => $this->globalApiKey
            ])->post("{$this->baseUrl}/webhook/set/{$instanceName}", [
                'webhook' => [
                    'url' => $webhookUrl,
                    'byEvents' => false,
                    'base64' => false,
                    'events' => [
                        'MESSAGES_UPSERT',
                        'MESSAGES_UPDATE',
                        'SEND_MESSAGE',
                        'CONNECTION_UPDATE'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Evolution API: Error setting webhook', ['msg' => $e->getMessage()]);
        }
    }

    /**
     * Solicita o Pairing Code para conectar a instância ao número de telefone.
     */
    public function getPairingCode(string $phoneNumber): array
    {
        if (!$this->instanceName || !$this->apiKey) {
            return ['error' => 'Instance not configured.'];
        }

        // Garante somente dígitos (DDI+DDD+número, ex: 5511999999999)
        $number = preg_replace('/\D/', '', $phoneNumber);

        // Adiciona DDI 55 (Brasil) automaticamente se o número tiver 10 ou 11 dígitos
        if (strlen($number) <= 11 && !str_starts_with($number, '55')) {
            $number = '55' . $number;
        }

        try {
            // Verifica estado atual da instância
            $stateRes = Http::timeout(10)->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");

            $state = $stateRes->json()['instance']['state'] ?? 'close';

            // Se a instância estiver fechada, inicia o processo de conexão
            if ($state === 'close') {
                Http::timeout(10)->withHeaders([
                    'apikey' => $this->apiKey,
                ])->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");
                sleep(1);
            }

            // Solicita o pairing code via GET com o número como query param
            $response = Http::timeout(30)->withHeaders([
                'apikey' => $this->apiKey,
            ])->get("{$this->baseUrl}/instance/connect/{$this->instanceName}", [
                'number' => $number,
            ]);

            Log::info('Evolution API pairingCode response', [
                'instance' => $this->instanceName,
                'number'   => $number,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Evolution API pairingCode exception', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtém o QR Code da instância em base64 (para instâncias em modo QR Code).
     */
    public function getConnectionQr(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'apikey' => $this->apiKey ?: $this->globalApiKey,
            ])->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");

            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }


    /**
     * Obtém status de conexão da instância (open, connecting, close).
     */
    public function getConnectionState(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Desloga o WhatsApp do dispositivo.
     */
    public function logout(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        $response = Http::withHeaders([
            'apikey' => $this->globalApiKey
        ])->delete("{$this->baseUrl}/instance/logout/{$this->instanceName}");

        return $response->json();
    }

    /**
     * Envia mensagem de texto com recursos Anti-Ban.
     * 
     * @param string $to Número com código do país (DDI + DDD + Num)
     * @param string $message Mensagem bruta (pode conter Spintax {Olá|Oi})
     * @param string|null $idempotencyKey Chave opcional de prevenção de duplicação
     * @param int $delaySeconds Segundos que ficará "Digitando" antes de enviar
     */
    public function sendMessage(string $to, string $message, ?string $idempotencyKey = null, int $delaySeconds = 0): array
    {
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return [
                'messageId' => 'SANDBOX_' . uniqid(),
                'status' => 'PENDING',
                'message' => 'Message sent (Sandbox)',
            ];
        }

        if (!$this->instanceName || !$this->apiKey) {
            return ['error' => 'Evolution API Configuration Missing'];
        }

        // Processa o Spintax transformando {A|B} em apenas um dos itens
        $renderedMessage = $this->applySpintax($message);

        // REGRA DE OURO: Para entrega garantida na Evolution v2, 
        // usamos o JID completo (@s.whatsapp.net ou @lid).
        // REGRA 3: Sanitização Numérica Estrita (Apenas números)
        // O campo number na requisição de saída deve conter apenas os dígitos puros.
        $cleanNumber = preg_replace('/[^0-9]/', '', $to);

        $payload = [
            'number' => $cleanNumber,
            'options' => [
                'delay' => $delaySeconds > 0 ? $delaySeconds * 1000 : 1500,
                'presence' => 'composing',
                'linkPreview' => false,
            ],
            'textMessage' => [
                'text' => $renderedMessage
            ]
        ];

        try {
            // Log detalhado do envio para diagnóstico
            Log::info('Evolution API Outbound Request', [
                'instance' => $this->instanceName,
                'target'   => $cleanNumber,
                'url'      => "{$this->baseUrl}/message/sendText/{$this->instanceName}",
                'payload'  => $payload
            ]);

            $response = Http::retry(3, 200, function ($exception, $request) {
                if (!$exception instanceof \Illuminate\Http\Client\RequestException) return false;
                $status = $exception->response->status();
                return in_array($status, [429, 500, 502, 503, 504]);
            }, throw: false)
            ->timeout(15)
            ->withHeaders([
                'apikey' => $this->apiKey 
            ])
            ->post("{$this->baseUrl}/message/send/text/{$this->instanceName}", $payload);

            if ($response->failed()) {
                Log::error('Evolution API send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $cleanNumber,
                    'instance' => $this->instanceName
                ]);

                return [
                    'error' => 'Failed to send message',
                    'status' => $response->status(),
                    'details' => $response->body(),
                ];
            }

            Log::debug('Evolution API response success', ['body' => $response->json()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error('Evolution API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Aplica a lógica de Spintax numa string.
     * Exemplo: "{Olá|Oi|Eae} tudo {bom|bem}?"
     */
    public function applySpintax(string $text): string
    {
        // Encontra tudo dentro de colchetes {...} e extrai aleatoriamente
        return preg_replace_callback('/\{(((?>[^\{\}]+)|(?R))*)\}/x', function ($match) {
            $options = explode('|', $match[1]);
            return $options[array_rand($options)];
        }, $text);
    }

    /**
     * Garante que o ID seja um JID válido.
     * Se não tiver @, adiciona @s.whatsapp.net. 
     * Se tiver, remove espaços/caracteres inválidos mantendo o sufixo.
     */
    public function formatJid(string $id): string
    {
        // Conforme a Regra 3 do usuário, para envio na Evolution v2, 
        // o campo 'number' deve conter apenas os dígitos puros.
        return preg_replace('/[^0-9]/', '', $id);
    }

    /**
     * Resolve um ID (@lid ou número) para um JID padrão via API.
     */
    public function fetchProfile(string $id): array
    {
        if (!$this->instanceName || !$this->apiKey) return [];

        try {
            // No Evolution V2, usamos o checkNumbers para validar e obter o JID real
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->apiKey
            ])->post("{$this->baseUrl}/chat/checkNumbers/{$this->instanceName}", [
                'numbers' => [$id]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data[0] ?? []; // Retorna o primeiro resultado (jid, exists, etc)
            }
        } catch (\Exception $e) {
            Log::error('Evolution API fetchProfile error', ['err' => $e->getMessage()]);
        }
        return [];
    }

    /**
     * Obtém o JID da própria instância (bot).
     */
    public function getBotJid(): ?string
    {
        try {
            $res = $this->getConnectionState();
            return $res['instance']['ownerJid'] ?? $res['instance']['owner'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
