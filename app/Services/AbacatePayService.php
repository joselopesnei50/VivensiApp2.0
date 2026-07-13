<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AbacatePayService — Integração com a AbacatePay API v2
 * REST + JSON, autenticação via Bearer Token.
 * Documentação: https://docs.abacatepay.com
 */
class AbacatePayService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.abacatepay.com/v2';
    protected bool   $devMode;

    public function __construct()
    {
        $this->apiKey  = (string) (SystemSetting::getValue('abacatepay_api_key')
                      ?? config('services.abacatepay.api_key', ''));
        $this->devMode = (SystemSetting::getValue('abacatepay_environment') ?? 'sandbox') === 'sandbox';
    }

    // ─── Checkout ─────────────────────────────────────────────────────────────

    /**
     * Cria um checkout (PIX + Cartão).
     *
     * @param array $items       [['externalId' => '...', 'quantity' => 1]]
     * @param string $externalId  Referência do seu sistema (ex: TENANT_5_1234567)
     * @param string $returnUrl   URL de retorno após pagamento
     * @param string $completionUrl URL de sucesso após pagamento
     * @param array  $methods     ['PIX', 'CARD'] — padrão ambos
     * @param array  $metadata    Dados extras (ex: tenant_id, plan_id)
     * @return array|null         ['url' => '...', 'id' => '...', 'status' => '...']
     */
    public function createCheckout(
        array  $items,
        string $externalId,
        string $returnUrl     = '',
        string $completionUrl = '',
        array  $methods       = ['PIX', 'CARD'],
        array  $metadata      = []
    ): ?array {
        $payload = [
            'items'         => $items,
            'externalId'    => $externalId,
            'returnUrl'     => $returnUrl ?: config('app.url') . '/dashboard',
            'completionUrl' => $completionUrl ?: config('app.url') . '/checkout/sucesso',
            'methods'       => $methods,
            'metadata'      => $metadata,
        ];

        Log::info('AbacatePay: createCheckout', ['externalId' => $externalId, 'devMode' => $this->devMode]);

        $response = $this->post('/checkouts/create', $payload);

        if ($response && ($response['success'] ?? false)) {
            return $response['data'];
        }

        // Log defensivo: gravamos apenas metadata de erro, nao o payload cru.
        // Endpoints da AbacatePay podem retornar dados do checkout (billing info,
        // customer email) em algumas condicoes — resto vai pro Log::error de rede
        // no metodo post() abaixo, com granularidade menor.
        Log::error('AbacatePay: createCheckout falhou', [
            'error'       => $response['error']   ?? null,
            'error_code'  => $response['code']    ?? null,
            'success'     => $response['success'] ?? null,
            'externalId'  => $externalId,
        ]);
        return null;
    }

    /**
     * Busca um checkout pelo ID.
     */
    public function getCheckout(string $checkoutId): ?array
    {
        $response = $this->get('/checkouts/get', ['id' => $checkoutId]);
        return ($response['success'] ?? false) ? $response['data'] : null;
    }

    /**
     * Lista todos os checkouts.
     */
    public function listCheckouts(): ?array
    {
        $response = $this->get('/checkouts/list');
        return ($response['success'] ?? false) ? $response['data'] : null;
    }

    // ─── Clientes ─────────────────────────────────────────────────────────────

    /**
     * Cria ou retorna um cliente existente na AbacatePay.
     */
    public function createOrGetCustomer(string $name, string $email, string $taxId, string $phone = ''): ?array
    {
        $payload = array_filter([
            'name'  => $name,
            'email' => $email,
            'taxId' => preg_replace('/\D/', '', $taxId),
            'phone' => $phone ?: null,
        ]);

        $response = $this->post('/customers/create', $payload);
        return ($response['success'] ?? false) ? $response['data'] : null;
    }

    // ─── Webhooks ─────────────────────────────────────────────────────────────

    /**
     * Valida a assinatura HMAC do webhook.
     * Header: X-Webhook-Signature
     * Algoritmo: HMAC-SHA256 com a chave pública da AbacatePay, digest em base64.
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $expected = base64_encode(
            hash_hmac('sha256', $rawBody, $this->getHmacKey(), true)
        );
        return hash_equals($expected, $signature);
    }

    private function getHmacKey(): string
    {
        // Prioridade: .env → SystemSetting (painel admin) → erro
        return config('services.abacatepay.hmac_key')
            ?? \App\Models\SystemSetting::getValue('abacatepay_hmac_key')
            ?? throw new \RuntimeException('ABACATEPAY_HMAC_KEY not configured');
    }

    /**
     * Valida o secret na query string do webhook.
     * URL: /api/abacatepay/webhook?webhookSecret=SEU_SECRET
     */
    public function verifyWebhookSecret(string $providedSecret): bool
    {
        $expected = SystemSetting::getValue('abacatepay_webhook_secret')
                 ?? config('services.abacatepay.webhook_secret', '');
        return $expected && hash_equals($expected, $providedSecret);
    }

    // ─── HTTP Helpers ─────────────────────────────────────────────────────────

    private function post(string $endpoint, array $payload): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withToken($this->apiKey)
                ->post($this->baseUrl . $endpoint, $payload);

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("AbacatePay POST {$endpoint} exception", ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function get(string $endpoint, array $query = []): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withToken($this->apiKey)
                ->get($this->baseUrl . $endpoint, $query);

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("AbacatePay GET {$endpoint} exception", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
