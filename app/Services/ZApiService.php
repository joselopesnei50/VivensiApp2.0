<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WhatsappConfig;

class ZApiService
{
    protected $instanceId;
    protected $token;
    protected $baseUrl = 'https://api.z-api.io/instances';

    public function __construct($tenantId)
    {
        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();
        if ($config) {
            $this->instanceId = $config->instance_id;
            $this->token = $config->token;
        }
    }

    public function sendMessage($to, $message)
    {
        // Local/dev sandbox: allow testing without a real Z-API instance.
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return [
                'messageId' => 'SANDBOX_' . uniqid(),
                'status' => 'PENDING',
                'message' => 'Message sent (Sandbox)'
            ];
        }

        if (!$this->instanceId || !$this->token) return ['error' => 'Configuração Z-API ausente'];

        // Sandbox/Test Mode
        if ($this->instanceId === 'TEST_INSTANCE') {
            return [
                'messageId' => 'FAKE_' . uniqid(),
                'status' => 'PENDING',
                'message' => 'Message sent (Simulation)'
            ];
        }

        $url = "{$this->baseUrl}/{$this->instanceId}/token/{$this->token}/send-text";

        $response = Http::post($url, [
            'phone' => $to,
            'message' => $message
        ]);

        return $response->json();
    }
    public function getStatus()
    {
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return ['connected' => true, 'mode' => 'sandbox'];
        }
        if (!$this->instanceId || !$this->token) return ['connected' => false, 'error' => 'Configuração Z-API ausente'];

        // Real Mode: Call API directly
        // URL: https://api.z-api.io/instances/{instanceId}/token/{token}/status
        $url = "https://api.z-api.io/instances/{$this->instanceId}/token/{$this->token}/status";

        try {
            $response = Http::get($url);
            return $response->json();
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function getQrCode()
    {
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return ['error' => 'Sandbox mode (sem QR Code).'];
        }
        if (!$this->instanceId || !$this->token) return ['error' => 'Configuração Z-API ausente'];

        // Real Mode: Call API directly
        $url = "https://api.z-api.io/instances/{$this->instanceId}/token/{$this->token}/qr-code/image";

        try {
            $response = Http::get($url);
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
