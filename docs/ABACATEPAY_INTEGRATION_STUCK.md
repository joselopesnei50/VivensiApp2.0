# AbacatePay — Integração travada (2026-08-03)

## Contexto

**Vivensi** (Laravel 9 multi-tenant SaaS) precisa cobrar assinaturas mensais dos clientes via AbacatePay. Integração já existe há meses (endpoint `/checkouts/create` funciona pra fluxo antigo), mas estamos adicionando cobrança automática mensal de invoices via **PIX Transparente** (`/transparents/create`) e **não conseguimos fazer o endpoint responder 2xx** — sempre retorna HTTP 422 com a mesma mensagem estranha.

Este documento traz **tudo que foi testado** para que alguém com experiência na AbacatePay (ou o suporte deles) possa resolver rapidamente.

---

## Estado da integração

- **Ambiente:** `production`
- **API key:** `abc_prod_...YMdC` (33 chars) — configurada em `SystemSetting.abacatepay_api_key`
- **Webhook Secret:** configurado (41 chars) em `SystemSetting.abacatepay_webhook_secret`
- **Webhook URL cadastrada no painel:** `https://vivensi.app.br/api/abacatepay/webhook`
- **Eventos webhook cadastrados:** `checkout.completed`, `checkout.refunded`, `transparent.completed`, `transparent.refunded`

### O que já funciona ✅

- `GET https://api.abacatepay.com/v2/checkouts/list` → **HTTP 200** com lista vazia
  (confirma que a API key é válida e autenticação Bearer está OK)
- Webhook endpoint HTTPS público responde 200 (`ProcessAbacatePayWebhook` com HMAC + idempotência)
- Handlers de eventos `checkout.completed`, `subscription.completed/renewed/cancelled` prontos

### O que não funciona ❌

- **`POST https://api.abacatepay.com/v2/transparents/create`** — sempre retorna:

```
HTTP 422
{
  "success": false,
  "data": null,
  "error": "Value should be one of 'object', 'object'"
}
```

**A mensagem `"Value should be one of 'object', 'object'"` repete `'object'` duas vezes** — parece bug de serialização de Zod/discriminated union no lado da AbacatePay.

---

## Todas as variações de payload testadas

Todas com header `Authorization: Bearer <api_key>` + `Content-Type: application/json`. Todas retornaram o mesmo 422 com a mesma mensagem:

### 1) Sem wrapper, mínimo
```json
{ "amount": 500 }
```

### 2) Sem wrapper, completo
```json
{
  "amount": 500,
  "description": "Teste",
  "expiresIn": 3600
}
```

### 3) Sem wrapper, com customer completo (4 campos)
```json
{
  "amount": 500,
  "description": "Teste",
  "expiresIn": 3600,
  "customer": {
    "name": "Teste Diagnostico",
    "email": "diag@vivensi.app.br",
    "taxId": "123.456.789-01",
    "cellphone": "(11) 4002-8922"
  },
  "metadata": { "origin": "test" }
}
```

### 4) Com wrapper `data`
```json
{
  "data": { "amount": 500 }
}
```

### 5) Com wrapper `data` + `method`
```json
{
  "method": "PIX",
  "data": {
    "amount": 500,
    "description": "Teste",
    "expiresIn": 3600
  }
}
```

### 6) Com wrapper `data` + `metadata` fora
```json
{
  "data": { "amount": 500 },
  "metadata": { "origin": "test" }
}
```

**Todas as 6 variações retornaram exatamente:**
```
HTTP 422
{"success":false,"data":null,"error":"Value should be one of 'object', 'object'"}
```

---

## Fontes consultadas (todas divergentes)

Cada fonte oficial mostra um formato diferente pro mesmo endpoint:

### 1. Documento `abacate.md` fornecido pelo cliente
> Campo obrigatório: `data.amount` (em centavos)

Sugere wrapper `data` — testamos (variação 4), 422.

### 2. `docs.abacatepay.com` (via WebFetch)
Menciona `v2CreatePixPayment` como tool MCP, mas o path REST não é claro.

### 3. Repo oficial `github.com/abacatepay/skills`

**a) `examples/go/transparents.go`** — Go client oficial:
```go
type CreateTransparentRequest struct {
    Data     TransparentData        `json:"data"`
    Metadata map[string]interface{} `json:"metadata,omitempty"`
}
type TransparentData struct {
    Amount int `json:"amount"`  // in cents
}

reqBody := CreateTransparentRequest{Data: TransparentData{Amount: amount}}
// → serializa pra { "data": { "amount": ... } }
```

Sugere `{"data": {"amount": N}}` — testamos (variação 4), 422.

**b) `tools/api-reference.md`** — reference doc:
```json
{
  "amount": 10000,
  "expiresIn": 3600,
  "description": "Payment",
  "customer": { "name": "...", "cellphone": "...", "email": "...", "taxId": "..." },
  "metadata": {}
}
```

Sugere payload plano sem wrapper. Testamos (variações 2, 3), 422.

**c) `rules/agent.md`** — curl real:
```bash
curl -X POST -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000}' \
  https://api.abacatepay.com/v2/transparents/create
```

Sugere `{"amount": 1000}` puro. Testamos (variação 1), 422.

---

## Código atual da integração (Laravel/PHP)

### Service — `app/Services/AbacatePayService.php`

```php
public function createPixCharge(int $amountCents, string $description = '', ...): ?array
{
    $payload = ['amount' => $amountCents];
    // ... optionally add description, expiresIn, customer, metadata

    $response = $this->post('/transparents/create', $payload);

    if ($response && ($response['success'] ?? false)) {
        return $response['data'];
    }
    return null;
}

private function post(string $endpoint, array $payload): ?array
{
    try {
        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->post($this->baseUrl . $endpoint, $payload);
        return $response->json();
    } catch (\Throwable $e) {
        Log::error(...);
        return null;
    }
}
```

Base URL: `https://api.abacatepay.com/v2`

### Script de diagnóstico standalone

`scripts/abacate-pix-test.php` — testa em 4 passos:
1. Confirma config (key + env + secret setados)
2. `GET /checkouts/list` → HTTP 200 OK
3. `POST /transparents/create` com payload mínimo → HTTP 422
4. Chama `AbacatePayService::createPixCharge()` → retorna null

Comando: `sudo -u www-data php scripts/abacate-pix-test.php`

---

## Hipóteses ainda não testadas

1. **Escopo/permissão da API key** — talvez a key gerada tem só permissão de leitura (list), não de criar PIX transparente
2. **Modalidade "transparents" não habilitada na conta** — talvez precise aprovação/habilitação separada no painel AbacatePay
3. **Algum header obrigatório não documentado** — tipo `X-Merchant-Id`, `X-Environment`, `Accept-Version` etc
4. **A API v2 mudou/está deprecada** — talvez v3 é o atual e o v2 retorna erro genérico
5. **Bug no lado da AbacatePay** com validação Zod que renderiza mensagem mal
6. **O endpoint mudou de path** — testar `/pixQrCode/create`, `/pix/create`, `/pixPayments/create`

---

## O que preciso descobrir

**Formato exato do payload JSON aceito por `POST /transparents/create`** ou o endpoint correto atual.

Pergunta objetiva pra suporte AbacatePay:

> "Minha API key `abc_prod_n...YMdC` autentica corretamente
> (`GET /checkouts/list` retorna 200), mas todo `POST /transparents/create`
> retorna 422 com `'Value should be one of object, object'`. Testei 6 variações
> de payload diferentes seguindo abacate.md, api-reference.md, exemplos Go do
> repo skills e o curl do agent.md. Qual é o formato JSON correto pra criar
> um PIX QR Code hoje?"

---

## Fluxo completo desejado

```
1. Vivensi cria Invoice mensal pra cada tenant ativo (dia 1)
   ↓
2. InvoiceService chama AbacatePayService::createPixCharge($amountCents, ...)
   ↓
3. AbacatePay retorna { id, brCode, brCodeBase64 }
   ↓
4. Vivensi salva na invoice: abacatepay_charge_id + pix_url + pix_qr_base64
   ↓
5. Cliente vê em /minha-conta/faturas
   ↓
6. Clica "Pagar" → modal com QR code + código copia-e-cola
   ↓
7. Cliente paga via app do banco
   ↓
8. AbacatePay envia webhook transparent.completed
   ↓
9. Vivensi marca Invoice como paid
```

**Passos 4-9 estão prontos.** Trava no passo 3 por causa do 422.

---

## Arquivos do repo Vivensi relacionados

- `app/Services/AbacatePayService.php` — cliente HTTP da AbacatePay
- `app/Services/Billing/InvoiceService.php` — geração + payment marking de invoices
- `app/Http/Controllers/Api/AbacatePayWebhookController.php` — recebe webhooks
- `app/Jobs/ProcessAbacatePayWebhook.php` — processa eventos (handlers prontos)
- `app/Models/Invoice.php` — model das faturas
- `scripts/abacate-pix-test.php` — script de diagnóstico
- `config/services.php` — bloco `abacatepay`

---

**Commit atual da tentativa:** `dd75b90` (main branch)
