# PLANO DE CORREÇÃO DE SEGURANÇA — Vivensi
**Gerado em**: 10/05/2026 | **Baseado em**: Auditoria Multi-Agente (5 agentes)

---

## SPRINT 1 — 48h | Bloqueantes Imediatos

### Ordem de execução
```
1. Revogar todas as chaves expostas (fora do sistema)
2. C2 — Mover WEBHOOK_HMAC_KEY para .env
3. C3 — Idempotência no webhook AbacatePay + migration
4. C4 — Configurar Supervisor no VPS
5. C1 — Confirmar .env fora do git
```

---

### C1 — .env com secrets no repositório Git
**Arquivo:** `.gitignore`

```bash
# Verificar se .env está rastreado
git ls-files --error-unmatch .env 2>/dev/null && echo "PERIGO" || echo "OK"

# Remover do índice se necessário
git rm --cached .env && git commit -m "security: remove .env from git"

# Rotacionar TODAS as chaves:
# - APP_KEY        -> php artisan key:generate  (invalida sessoes ativas)
# - EVOLUTION_GLOBAL_KEY -> painel Evolution API
# - PUSHER_APP_SECRET    -> painel Pusher
# - DB_PASSWORD          -> AWS RDS console
# - Tokens AbacatePay, OpenPix, PagSeguro
```

**Risco:** key:generate invalida todas as sessões. Executar em horário de baixo uso.

---

### C2 — WEBHOOK_HMAC_KEY hardcoded
**Arquivo:** `app/Services/AbacatePayService.php:21`

ANTES:
```php
const WEBHOOK_HMAC_KEY = 't9dXRhHH...chave_real...';
```

DEPOIS — remover a constante e alterar verifyWebhookSignature():
```php
public function verifyWebhookSignature(string $rawBody, string $signature): bool
{
    $hmacKey = config('services.abacatepay.webhook_hmac_key', '');
    if (empty($hmacKey)) {
        \Log::critical('AbacatePay: ABACATEPAY_WEBHOOK_HMAC_KEY nao configurada');
        return false;
    }
    $expected = base64_encode(hash_hmac('sha256', $rawBody, $hmacKey, true));
    return hash_equals($expected, $signature);
}
```

`config/services.php` — adicionar entrada:
```php
'abacatepay' => [
    'api_key'          => env('ABACATEPAY_API_KEY'),
    'webhook_secret'   => env('ABACATEPAY_WEBHOOK_SECRET'),
    'webhook_hmac_key' => env('ABACATEPAY_WEBHOOK_HMAC_KEY'),
    'environment'      => env('ABACATEPAY_ENV', 'sandbox'),
],
```

`.env` no VPS (após gerar nova chave no painel AbacatePay):
```
ABACATEPAY_WEBHOOK_HMAC_KEY=<nova_chave>
```

Verificar antes do deploy:
```bash
php artisan tinker --execute="echo config('services.abacatepay.webhook_hmac_key') ? 'OK' : 'MISSING';"
```

---

### C3 — Idempotência no webhook AbacatePay
**Arquivo:** `app/Jobs/ProcessAbacatePayWebhook.php:42-88`

Migration necessária — criar `database/migrations/..._create_processed_webhooks_table.php`:
```php
Schema::create('processed_webhooks', function (Blueprint $table) {
    $table->id();
    $table->string('gateway', 50);
    $table->string('webhook_id', 255);
    $table->string('event', 100);
    $table->timestamp('processed_at')->useCurrent();
    $table->unique(['gateway', 'webhook_id'], 'uq_gateway_webhook');
});
```

Método handleCheckoutCompleted — substituir por:
```php
private function handleCheckoutCompleted(array $payload): void
{
    $checkout   = $payload['data']['checkout'] ?? null;
    $externalId = $checkout['externalId'] ?? null;
    $webhookId  = $payload['id'] ?? null;

    if (!$checkout || !$externalId) return;

    if ($webhookId) {
        try {
            DB::table('processed_webhooks')->insertOrIgnore([
                'gateway'      => 'abacatepay',
                'webhook_id'   => $webhookId,
                'event'        => 'checkout.completed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::info('AbacatePay: webhook duplicado ignorado', ['id' => $webhookId]);
            return;
        }
    }

    $tenant = $this->findTenantByExternalId($externalId);
    if (!$tenant) return;

    DB::transaction(function () use ($externalId, $tenant, $checkout, $payload) {
        $transaction = Transaction::withoutGlobalScopes()
            ->where('external_id', $externalId)
            ->lockForUpdate()
            ->first();

        if ($transaction && $transaction->status !== 'paid') {
            $transaction->update([
                'status'          => 'paid',
                'approval_status' => 'approved',
                'paid_at'         => now(),
            ]);
        }

        $tenant->subscription_status = 'active';
        $planId = $checkout['metadata']['plan_id'] ?? null;
        if ($planId && \App\Models\SubscriptionPlan::find($planId)) {
            $tenant->plan_id = $planId;
        }
        $tenant->save();
    });
}
```

---

### C4 — Supervisor para queue workers
Criar no VPS: `/etc/supervisor/conf.d/vivensi-worker.conf`

```ini
[program:vivensi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vivensi/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --queue=default
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vivensi/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
```

```bash
sudo apt-get install -y supervisor
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start vivensi-worker:*
sudo supervisorctl status
```

---

## SPRINT 2 — 1 semana | Isolamento e Proteção de Dados

### Ordem de execução
```
1. $hidden nos models (sem migration, sem downtime)
2. Race condition CleanupRaffleReservations
3. Upload paths com tenant_id
4. Senha mínima 12 caracteres
5. AbacatePay webhookSecret no header
```

---

### $hidden em models sensíveis

`app/Models/WhatsappInstance.php`:
```php
protected $hidden = ['instance_token'];
```

`app/Models/Tenant.php`:
```php
protected $hidden = ['pix_key', 'openpix_app_id'];
```

`app/Models/Beneficiary.php`:
```php
protected $hidden = ['cpf', 'nis', 'birth_date'];
```

---

### Race condition CleanupRaffleReservations
**Arquivo:** `app/Console/Commands/CleanupRaffleReservations.php:32-46`

Substituir get()+foreach por UPDATE atômico:
```php
$count = RaffleTicket::where('status', 'pending')
    ->where('reserved_at', '<', Carbon::now()->subMinutes(30))
    ->update([
        'status'      => 'available',
        'buyer_name'  => null,
        'buyer_email' => null,
        'buyer_phone' => null,
        'reserved_at' => null,
    ]);
Log::info("CleanupRaffleReservations: {$count} bilhetes liberados.");
```

---

### Upload paths com tenant_id

`BannerController.php:290`:
```php
$path = $request->file('image')->store("banners/{$tenantId}/uploads", 'public');
```

`TransactionController.php:99`:
```php
$path = $request->file('attachment')->store("attachments/{$tenantId}", 'public');
```

`ScheduledPostController.php:48`:
```php
$path = $request->file('media')->store("social-media/{$tenantId}", 'public');
```

`ProjectTimelineController.php:31`:
```php
$path = $request->file('media')->store("projects/{$tenantId}/timeline", 'public');
```

---

### Senha mínima 12 caracteres
`ManagerController.php:214` e `TeamController.php:37`:

ANTES:
```php
'password' => 'required|min:6',
```
DEPOIS:
```php
'password' => ['required', 'string', 'min:12', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
```

---

### AbacatePay webhookSecret no header
`app/Http/Controllers/Api/AbacatePayWebhookController.php:26`:
```php
$secret = $request->header('X-Webhook-Secret')
       ?? $request->query('webhookSecret', '');
```

---

## SPRINT 3 — 2 semanas | Hardening Estrutural

---

### C5 — Reativar SecurityHeaders
`app/Http/Kernel.php` — descomentar:
```php
\App\Http\Middleware\SecurityHeaders::class,
```

`app/Http/Middleware/SecurityHeaders.php:29` — corrigir HSTS:
```php
$response->header('Strict-Transport-Security', 'max-age=3600; includeSubDomains');
// Após 30 dias confirmando SSL estável:
// max-age=31536000; includeSubDomains; preload
```

---

### C7 — /t/{tenant_id} enumeration
`routes/web.php:638` — adicionar throttle:
```php
Route::get('/t/{tenant_id}', [TransparencyController::class, 'publicView'])
    ->where('tenant_id', '[0-9]+')
    ->middleware('throttle:10,1');
```

---

### C9 — /validar-certificado/{id} enumeration
Migration: adicionar coluna `public_token VARCHAR(64) UNIQUE` em `volunteer_certificates`
com backfill de `Str::random(48)` para registros existentes.
Atualizar rota de `{id}` para `{token}` e ajustar busca no controller.

---

### C6 — User model (isolamento de tenant)
NAO adicionar global scope — quebraria o sistema de autenticação.
Adicionar apenas o hook creating:
```php
protected static function booted(): void
{
    static::creating(function (User $user) {
        if (auth()->check() && !$user->tenant_id && auth()->user()->tenant_id) {
            $user->tenant_id = auth()->user()->tenant_id;
        }
    });
}
```

---

### Wildcards em composer.json
```json
"doctrine/dbal": "^3.6",
"guzzlehttp/guzzle": "^7.8",
"open-pix/php-sdk": "^1.0"
```

---

## Checklist de Verificação Pré-Deploy

```bash
php artisan tinker --execute="echo config('services.abacatepay.webhook_hmac_key') ? 'HMAC: OK' : 'HMAC: MISSING';"
php artisan migrate:status | grep "Pending"
sudo supervisorctl status
php artisan queue:monitor database --max=50
```

---

## Estimativa de Esforço

| Sprint | Prazo    | Esforço dev | Risco de regressão                      |
|--------|----------|-------------|------------------------------------------|
| 1      | 48h      | ~1 dia      | MÉDIO — key:generate invalida sessões    |
| 2      | 1 semana | ~2 dias     | BAIXO                                    |
| 3      | 2 semanas| ~3 dias     | BAIXO-MÉDIO — HSTS exige SSL estável     |
