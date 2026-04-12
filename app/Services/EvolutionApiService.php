<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Evolution API Service 2.0 (Optimized for VPS Local Integration)
 * 
 * Versão re-implementada focada em performance local e compatibilidade v2.1.1.
 */
class EvolutionApiService
{
    protected $instanceName;
    protected $apiKey; 
    protected $globalApiKey;
    protected $baseUrl;
    protected $contextModel;

    /**
     * @param \Illuminate\Database\Eloquent\Model|null $contextModel (App\Models\Tenant ou App\Models\User)
     */
    public function __construct($contextModel = null)
    {
        // Prioridade: Localhost (mesma VPS) -> Config -> Env
        $this->baseUrl = config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br'));
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
     * Cria uma instância atômica.
     */
    public function createInstance(string $name, string $clientToken = null, ?string $number = null): array
    {
        $webhookUrl  = config('app.url') . "/api/evo/webhook/" . $clientToken;

        $payload = [
            'instanceName' => $name,
            'qrcode'       => true,
            'integration'  => 'WHATSAPP-BAILEYS',
        ];

        if ($number) {
            $payload['number'] = preg_replace('/\D/', '', $number);
        }

        $payload = array_merge($payload, [
            'rejectCall'   => false,
            'groupsIgnore' => true,
            'alwaysOnline' => true,
            'readMessages' => true,
            'readStatus'   => true,
            'webhook' => [
                'enabled' => true,
                'url'     => $webhookUrl,
                'byEvents' => false,
                'base64'   => true,
                'events'   => ['qrcode.updated', 'connection.update', 'messages.upsert', 'messages.update', 'send.message']
            ]
        ]);

        try {
            $response = Http::timeout(45)->withSslVerification($this->sslVerify())->withHeaders([
                'apikey' => $this->globalApiKey
            ])->post("{$this->baseUrl}/instance/create", $payload);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return ['error' => 'Falha na criação da instância', 'details' => $response->body()];
        } catch (\Exception $e) {
            return ['error' => 'Exceção na criação (API Fora?): ' . $e->getMessage()];
        }
    }

    /**
     * Busca o código de conexão de forma resiliente.
     */
    public function fetchConnectionCode(string $instanceName): array
    {
        try {
            // 1. Tenta buscar o QR (Rápido)
            $response = Http::timeout(3)->withSslVerification($this->sslVerify())->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");

            if ($response->successful()) {
                $data = $response->json();
                $qrBase64 = $data['base64'] ?? ($data['qrcode']['base64'] ?? ($data['code'] ?? null));

                if ($qrBase64) {
                    return [
                        'qrcode'      => $qrBase64,
                        'status'      => 'open',
                    ];
                }
            }

            // 2. Se falhar ou não tiver QR, tenta "acordar" em background (Curto Timeout)
            Http::timeout(1)->withSslVerification($this->sslVerify())->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");

            return ['error' => 'Gerando QR Code...', 'status' => 'generating'];
        } catch (\Exception $e) {
            return ['error' => 'Aguardando API...', 'status' => 'generating'];
        }
    }

    public function getConnectionState(): array
    {
        if (!$this->instanceName) return ['error' => 'Not configured'];

        try {
            $response = Http::timeout(5)->withSslVerification($this->sslVerify())->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function sendMessage(string $to, string $message, ?string $idempotencyKey = null, int $delaySeconds = 0): array
    {
        if (!$this->instanceName || !$this->apiKey) return ['error' => 'Evolution API Missing Config'];

        $number = (string) $to;
        $renderedMessage = $this->applySpintax($message);

        $payload = [
            'number' => $number,
            'text'   => $renderedMessage,
            'delay'  => $delaySeconds > 0 ? $delaySeconds * 1000 : 1200,
            'linkPreview' => false,
        ];

        try {
            $response = Http::timeout(15)->withSslVerification($this->sslVerify())->withHeaders([
                'apikey' => $this->apiKey
            ])->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", $payload);

            if ($response->failed()) {
                try {
                    Log::error('EVOLUTION API REJEITOU O ENVIO', ['status' => $response->status(), 'body' => $response->json()]);
                } catch (\Exception $le) {}
                return ['error' => 'Failed to send', 'details' => $response->body()];
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
     * Usado como alternativa ao QR Code.
     *
     * @param string $phone Número no formato E.164 sem '+' (ex: 5511999999999)
     */
    public function getPairingCode(string $phone): array
    {
        $instanceName = $this->instanceName;
        if (!$instanceName) return ['error' => 'No instance configured'];

        $cleanPhone = preg_replace('/\D/', '', $phone);

        try {
            $response = Http::timeout(15)
                ->withSslVerification($this->sslVerify())
                ->withHeaders(['apikey' => $this->globalApiKey])
                ->post("{$this->baseUrl}/instance/pairingCode/{$instanceName}", [
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
        $response = Http::timeout(10)
            ->withSslVerification($this->sslVerify())
            ->withHeaders(['apikey' => $this->globalApiKey])
            ->delete("{$this->baseUrl}/instance/logout/{$this->instanceName}");
        return $response->json() ?? [];
    }

    /**
     * SSL verification: habilitada em produção, desabilitada em localhost/dev.
     */
    protected function sslVerify(): bool
    {
        if (!app()->environment('production')) return false;
        return !str_contains($this->baseUrl, 'localhost') && !str_contains($this->baseUrl, '127.0.0.1');
    }
}
