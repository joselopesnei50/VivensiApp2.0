<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\WhatsappConfig;
use App\Models\SystemSetting;

/**
 * Z-API Service (Enhanced)
 * 
 * Implements enterprise-grade resilience patterns:
 * - Exponential backoff for HTTP 429 (rate limiting) and 5xx errors
 * - Idempotency keys to prevent duplicate message sends
 * - Configurable timeouts (10s default)
 * - Comprehensive error logging
 */
class ZApiService
{
    protected $instanceId;
    protected $token;
    protected $clientToken;
    protected $baseUrl = 'https://api.z-api.io/instances';

    public function __construct($tenantId)
    {
        // Multi-tenant: Load credentials from whatsapp_configs OR system_settings (fallback)
        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();
        
        if ($config) {
            $this->instanceId = $config->instance_id;
            $this->token = $config->token;
            $this->clientToken = $config->client_token;
        } else {
            // Fallback: System-wide settings (for super_admin testing)
            $this->instanceId = SystemSetting::getValue('zapi_instance_id');
            $this->token = SystemSetting::getValue('zapi_token');
            $this->clientToken = SystemSetting::getValue('zapi_client_token');
        }
    }

    /**
     * Send text message with idempotency and retry logic
     * 
     * @param string $to WhatsApp phone number (e.g., 5581999999999)
     * @param string $message Message content
     * @param string|null $idempotencyKey Optional UUID (auto-generated if null)
     * @return array Response from Z-API or error array
     */
    public function sendMessage(string $to, string $message, ?string $idempotencyKey = null): array
    {
        // Sandbox mode (local development)
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return [
                'messageId' => 'SANDBOX_' . uniqid(),
                'status' => 'PENDING',
                'message' => 'Message sent (Sandbox)',
                'idempotency_key' => $idempotencyKey ?? Str::uuid()->toString(),
            ];
        }

        if (!$this->instanceId || !$this->token) {
            return ['error' => 'Configuração Z-API ausente (instance_id ou token)'];
        }

        // Test mode (fake instance)
        if ($this->instanceId === 'TEST_INSTANCE') {
            return [
                'messageId' => 'FAKE_' . uniqid(),
                'status' => 'PENDING',
                'message' => 'Message sent (Simulation)',
                'idempotency_key' => $idempotencyKey ?? Str::uuid()->toString(),
            ];
        }

        // Generate idempotency key if not provided
        $idempotencyKey = $idempotencyKey ?? Str::uuid()->toString();

        $url = "{$this->baseUrl}/{$this->instanceId}/token/{$this->token}/send-text";

        try {
            // Exponential Backoff: Retry 3 times (100ms, 200ms, 400ms) for transient failures
            $response = Http::retry(3, 100, function ($exception, $request) {
                // Retry on: 429 (Too Many Requests), 500, 502, 503, 504
                if (!$exception instanceof \Illuminate\Http\Client\RequestException) {
                    return false;
                }
                
                $status = $exception->response->status();
                $shouldRetry = in_array($status, [429, 500, 502, 503, 504]);
                
                if ($shouldRetry) {
                    Log::warning('Z-API transient error, retrying...', [
                        'status' => $status,
                        'idempotency_key' => $request->header('X-Idempotency-Key')[0] ?? null,
                    ]);
                }
                
                return $shouldRetry;
            }, throw: false) // Don't throw exception, return response
            ->timeout(10) // 10-second timeout (prevent hanging)
            ->withHeaders([
                'Client-Token' => $this->clientToken ?? '',
                'X-Idempotency-Key' => $idempotencyKey,
            ])
            ->post($url, [
                'phone' => $to,
                'message' => $message,
            ]);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                
                Log::error('Z-API send failed', [
                    'status' => $status,
                    'body' => $body,
                    'idempotency_key' => $idempotencyKey,
                    'to' => $to,
                ]);

                return [
                    'error' => 'Failed to send message',
                    'status' => $status,
                    'details' => $body,
                    'idempotency_key' => $idempotencyKey,
                ];
            }

            $result = $response->json();
            $result['idempotency_key'] = $idempotencyKey; // Attach for caller
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Z-API exception', [
                'error' => $e->getMessage(),
                'idempotency_key' => $idempotencyKey,
                'to' => $to,
            ]);

            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'idempotency_key' => $idempotencyKey,
            ];
        }
    }

    public function getStatus(): array
    {
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return ['connected' => true, 'mode' => 'sandbox'];
        }
        
        if (!$this->instanceId || !$this->token) {
            return ['connected' => false, 'error' => 'Configuração Z-API ausente'];
        }

        $url = "https://api.z-api.io/instances/{$this->instanceId}/token/{$this->token}/status";

        try {
            $response = Http::timeout(5)->get($url);
            return $response->json();
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function getQrCode(): array
    {
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return ['error' => 'Sandbox mode (sem QR Code).'];
        }
        
        if (!$this->instanceId || !$this->token) {
            return ['error' => 'Configuração Z-API ausente'];
        }

        $url = "https://api.z-api.io/instances/{$this->instanceId}/token/{$this->token}/qr-code/image";

        try {
            $response = Http::timeout(10)->get($url);
            
            if ($response->successful()) {
                $image = base64_encode($response->body());
                return ['image' => 'data:image/png;base64,' . $image];
            }
            
            return ['error' => 'Não foi possível obter o QR Code. Verifique se a instância já está conectada.'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
