<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Exception;

class AsaasService
{
    protected ?string $apiKey = null;
    protected ?string $baseUrl = null;

    public function __construct()
    {
        // Intentionally do not hit the database here.
        // Artisan commands (e.g., route:list) may instantiate controllers/services without DB connectivity.
    }

    protected function resolveApiKey(): string
    {
        if ($this->apiKey) {
            return $this->apiKey;
        }

        $this->apiKey = (string) SystemSetting::getValue('asaas_api_key');
        if (!$this->apiKey) {
            throw new Exception('Asaas API key not configured (system_settings: asaas_api_key).');
        }

        return $this->apiKey;
    }

    protected function resolveBaseUrl(): string
    {
        if ($this->baseUrl) {
            return $this->baseUrl;
        }

        $env = (string) SystemSetting::getValue('asaas_environment', 'sandbox');
        $this->baseUrl = ($env === 'production')
            ? 'https://www.asaas.com/api/v3'
            : 'https://sandbox.asaas.com/api/v3';

        return $this->baseUrl;
    }

    protected function headers()
    {
        return [
            'access_token' => $this->resolveApiKey(),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Create or retrieve a customer in Asaas
     */
    public function createCustomer($tenant)
    {
        $response = Http::withHeaders($this->headers())->post($this->resolveBaseUrl() . '/customers', [
            'name' => $tenant->name,
            'cpfCnpj' => $tenant->document,
            'externalReference' => 'TENANT_' . $tenant->id,
            'notificationDisabled' => false,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new Exception('Erro ao criar cliente no Asaas: ' . $response->body());
    }

    /**
     * Create a Subscription
     */
    public function createSubscription($customerId, $plan, $paymentMethod = 'UNDEFINED', $nextDueDate = null)
    {
        $dueDate = $nextDueDate ? $nextDueDate->format('Y-m-d') : now()->addDays(3)->format('Y-m-d');

        $response = Http::withHeaders($this->headers())->post($this->resolveBaseUrl() . '/subscriptions', [
            'customer' => $customerId,
            'billingType' => $paymentMethod, // PIX, BOLETO, CREDIT_CARD, UNDEFINED
            'value' => $plan->price,
            'nextDueDate' => $dueDate,
            'cycle' => ($plan->interval === 'monthly') ? 'MONTHLY' : 'YEARLY',
            'description' => 'Assinatura Vivensi - ' . $plan->name,
            'externalReference' => 'PLAN_' . $plan->id,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new Exception('Erro ao criar assinatura no Asaas: ' . $response->body());
    }

    /**
     * Get Payment/Subscription details (to show QR Code or billing info)
     */
    public function getSubscriptionPayments($subscriptionId)
    {
        $response = Http::withHeaders($this->headers())->get($this->resolveBaseUrl() . "/subscriptions/{$subscriptionId}/payments");
        
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get Pix QR Code for a payment
     */
    public function getPixQrCode($paymentId)
    {
        $response = Http::withHeaders($this->headers())->get($this->resolveBaseUrl() . "/payments/{$paymentId}/pixQrCode");
        
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get Boleto identification code (Linha digitável)
     */
    public function getBoletoCode($paymentId)
    {
        $response = Http::withHeaders($this->headers())->get($this->resolveBaseUrl() . "/payments/{$paymentId}/identificationField");
        
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
