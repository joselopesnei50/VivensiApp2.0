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
            $this->instanceName = $contextModel->evolution_instance_name;
            $this->apiKey = $contextModel->evolution_instance_token;
        }
    }

    /**
     * Cria uma nova instância na Evolution API.
     */
    public function createInstance(string $name): array
    {
        $token = Str::random(32); // Usado para autenticar Webhooks e a própria Instância

        $response = Http::timeout(15)->withHeaders([
            'apikey' => $this->globalApiKey
        ])->post("{$this->baseUrl}/instance/create", [
            'instanceName' => $name,
            'token' => $token,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        if ($response->successful()) {
            return array_merge($response->json(), ['generated_token' => $token]);
        }

        Log::error('Evolution API: Error creating instance', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return ['error' => 'Failed to create instance', 'details' => $response->body()];
    }

    /**
     * Obtém o QR Code ou status de pareamento da instância atual.
     */
    public function getConnectStatus(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");

            return $response->json();
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

        $payload = [
            'number' => $to,
            'textMessage' => [
                'text' => $renderedMessage
            ]
        ];

        // Se tiver delay > 0, usar o recurso nativo da Evolution para simular digitação "composing"
        if ($delaySeconds > 0) {
            $payload['options'] = [
                'delay' => $delaySeconds * 1000,
                'presence' => 'composing'
            ];
        }

        try {
            $response = Http::retry(3, 200, function ($exception, $request) {
                if (!$exception instanceof \Illuminate\Http\Client\RequestException) return false;
                $status = $exception->response->status();
                return in_array($status, [429, 500, 502, 503, 504]);
            }, throw: false)
            ->timeout(15)
            ->withHeaders([
                'apikey' => $this->apiKey // Recomenda-se autenticar ações da instância com o token da própria
            ])
            ->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", $payload);

            if ($response->failed()) {
                Log::error('Evolution API send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);

                return [
                    'error' => 'Failed to send message',
                    'status' => $response->status(),
                    'details' => $response->body(),
                ];
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Evolution API exception', [
                'error' => $e->getMessage()
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
}
