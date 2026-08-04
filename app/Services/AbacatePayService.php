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
     * Cria um checkout de assinatura.
     * Endpoint: POST /subscriptions/create
     *
     * @param array $items       [['id' => '...', 'quantity' => 1]]
     * @param string $externalId  Referência do seu sistema (ex: TENANT_5_1234567)
     * @param string $returnUrl   URL de retorno após pagamento
     * @param string $completionUrl URL de sucesso após pagamento
     * @param array  $methods     ['PIX', 'CARD'] — padrão ambos
     * @param array  $metadata    Dados extras (ex: tenant_id, plan_id)
     * @return array|null         ['url' => '...', 'id' => '...', 'status' => '...']
     */
    public function createSubscriptionCheckout(
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

        Log::info('AbacatePay: createSubscriptionCheckout', ['externalId' => $externalId, 'devMode' => $this->devMode]);

        $response = $this->post('/subscriptions/create', $payload);

        if ($response && ($response['success'] ?? false)) {
            return $response['data'];
        }

        Log::error('AbacatePay: createSubscriptionCheckout falhou', [
            'error'       => $response['error']   ?? null,
            'error_code'  => $response['code']    ?? null,
            'success'     => $response['success'] ?? null,
            'externalId'  => $externalId,
            'payload'     => $payload,
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

    // ─── PIX Transparente (Checkout Transparente) ────────────────────────────

    /**
     * Cria uma cobrança PIX embutida com QR code gerado direto — sem redirect,
     * sem precisar Produto pré-cadastrado. Ideal pra invoices com valor
     * arbitrário. Só PIX (não suporta cartão).
     *
     * Endpoint: POST /transparents/create
     * Docs: https://docs.abacatepay.com/pages/pix-qrcode/create
     *
     * @param int    $amountCents  Valor em centavos (ex: 10000 = R$ 100,00)
     * @param string $description  Descrição que aparece no app do banco do pagador
     * @param array  $customer     [name, email, taxId?, cellphone?] — opcional
     * @param array  $metadata     Dados extras retornados no webhook (invoice_id, tenant_id, etc)
     * @param int    $expiresIn    Segundos até QR code expirar (default: 24h)
     * @return array|null   ['id' => 'pix_xxx', 'brCode' => '000201...', 'brCodeBase64' => 'iVBOR...']
     */
    public function createPixCharge(
        int    $amountCents,
        string $description  = '',
        array  $customer     = [],
        array  $metadata     = [],
        int    $expiresIn    = 86400
    ): ?array {
        // ⚠️ ENDPOINT COM PROBLEMA — SEM USO ATIVO desde 2026-08-03.
        //
        // 6 formatos de payload testados retornaram HTTP 422 com msg
        // 'Value should be one of object, object'. Ver dossier completo
        // em docs/ABACATEPAY_INTEGRATION_STUCK.md.
        //
        // Solução adotada: usar /subscriptions/create (createSubscriptionCheckout)
        // com produto cadastrado no painel Abacate (cycle=MONTHLY) — AbacatePay
        // cuida da recorrência automática, dispensa esse endpoint.
        //
        // Este método fica aqui pra caso o suporte AbacatePay resolva o 422.
        $payload = ['amount' => $amountCents];

        if ($description !== '') {
            $payload['description'] = mb_substr($description, 0, 140);
        }
        if ($expiresIn > 0) {
            $payload['expiresIn'] = $expiresIn;
        }

        $hasFullCustomer = !empty($customer['name'])
                       && !empty($customer['email'])
                       && !empty($customer['taxId'])
                       && !empty($customer['cellphone']);

        if ($hasFullCustomer) {
            $payload['customer'] = [
                'name'      => $customer['name'],
                'email'     => $customer['email'],
                'taxId'     => preg_replace('/\D/', '', $customer['taxId']),
                'cellphone' => preg_replace('/\D/', '', $customer['cellphone']),
            ];
        }

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        Log::info('AbacatePay: createPixCharge', [
            'amount'       => $amountCents,
            'has_customer' => $hasFullCustomer,
            'metadata'     => $metadata,
            'devMode'      => $this->devMode,
        ]);

        $response = $this->post('/transparents/create', $payload);

        if ($response && ($response['success'] ?? false)) {
            return $response['data'];
        }

        Log::error('AbacatePay: createPixCharge falhou', [
            'error'      => $response['error']   ?? null,
            'error_code' => $response['code']    ?? null,
            'success'    => $response['success'] ?? null,
            'metadata'   => $metadata,
        ]);
        return null;
    }

    /**
     * Verifica status de pagamento de um PIX pelo ID.
     * Endpoint: GET /transparents/check
     */
    public function checkPixCharge(string $pixId): ?array
    {
        $response = $this->get('/transparents/check', ['id' => $pixId]);
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
