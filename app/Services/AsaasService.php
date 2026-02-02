<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Exception;

class AsaasService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = SystemSetting::getValue('asaas_api_key');
        $env = SystemSetting::getValue('asaas_environment', 'sandbox');
        
        $this->baseUrl = ($env === 'production') 
            ? 'https://www.asaas.com/api/v3' 
            : 'https://sandbox.asaas.com/api/v3';
    }

    protected function headers()
    {
        return [
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Create or retrieve a customer in Asaas
     */
    public function createCustomer($tenant)
    {
        $response = Http::withoutVerifying()->withHeaders($this->headers())->post($this->baseUrl . '/customers', [
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
    public function createSubscription($customerId, $plan, $paymentMethod = 'UNDEFINED')
    {
        $response = Http::withoutVerifying()->withHeaders($this->headers())->post($this->baseUrl . '/subscriptions', [
            'customer' => $customerId,
            'billingType' => $paymentMethod, // PIX, BOLETO, CREDIT_CARD, UNDEFINED
            'value' => $plan->price,
            'nextDueDate' => now()->addDays(3)->format('Y-m-d'),
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
        $response = Http::withoutVerifying()->withHeaders($this->headers())->get($this->baseUrl . "/subscriptions/{$subscriptionId}/payments");
        
        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
