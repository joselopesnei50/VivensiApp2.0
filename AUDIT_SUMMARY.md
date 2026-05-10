# AUDIT_SUMMARY — Vivensi Security & Functional Audit
**Data**: 10/05/2026 | **Escopo**: Laravel 9, PHP 8, MySQL | **Status**: READ-ONLY

---

## TOP 10 — ITENS CRÍTICOS ORDENADOS POR RISCO

### #1 — CRÍTICO | Secrets reais no arquivo .env commitado no repositório
**Agente**: Security | **Arquivo**: `.env`
- `APP_KEY=base64:V0kweZuxYCnlIpOW8n1UhgqgQaG5tfLJWmB2Jp6oAjA=`
- `EVOLUTION_GLOBAL_KEY=e838f5d5...` (chave real exposta)
- `PUSHER_APP_SECRET=vivensi-secret`
**Ação imediata**: `git rm --cached .env && echo ".env" >> .gitignore` + revogar todas as chaves + `php artisan key:generate`

---

### #2 — CRÍTICO | WEBHOOK_HMAC_KEY hardcoded no código-fonte
**Agente**: Data Leak + Functional | **Arquivo**: `app/Services/AbacatePayService.php:21`
```php
const WEBHOOK_HMAC_KEY = 't9dXRhHHo3yDEj5pVDYz0frf7...'; // chave real no Git
```
**Ação**: Mover para `.env` como `ABACATEPAY_HMAC_KEY=`, revogar e gerar nova chave no painel AbacatePay.

---

### #3 — CRÍTICO | Idempotência ausente no webhook de pagamento AbacatePay
**Agente**: Functional | **Arquivo**: `app/Jobs/ProcessAbacatePayWebhook.php:42-88`
Webhook reprocessado marca transação como paga novamente e reativa assinatura duplicada. Sem `lockForUpdate()` nem verificação de `status === 'paid'` antes de atualizar.
**Ação**: Adicionar `->where('status', '!=', 'paid')->lockForUpdate()` dentro de `DB::transaction()`.

---

### #4 — CRÍTICO | Queue workers não rodando (QUEUE_CONNECTION=database sem supervisor)
**Agente**: Functional | **Arquivo**: `config/queue.php` + `.env:21`
Todos os jobs (WhatsApp, PIX, automações) são enfileirados mas nunca executados sem worker ativo. Nenhum Procfile ou supervisord.conf encontrado.
**Ação**: Configurar Supervisor no VPS com `php artisan queue:work --queue=default,whatsapp`.

---

### #5 — CRÍTICO | SecurityHeaders middleware criado mas desativado no Kernel
**Agente**: Attack Surface | **Arquivo**: `app/Http/Kernel.php` (linha comentada)
HSTS, X-Frame-Options, CSP e demais headers de segurança estão implementados mas nunca enviados ao browser pois o middleware está comentado.
**Ação**: Descomentar middleware em `$middlewareGroups['web']`. Aumentar HSTS de `max-age=300` para `max-age=31536000`.

---

### #6 — CRÍTICO | User model sem BelongsToTenant (isolamento de tenant quebrado)
**Agente**: Tenant Isolation | **Arquivo**: `app/Models/User.php`
Queries em User sem filtro explícito de `tenant_id` retornam usuários de outros tenants. Único modelo central sem o global scope de isolamento.
**Ação**: Adicionar `use BelongsToTenant;` ao model User (verificar impacto em super_admin que usa `withoutGlobalScopes()`).

---

### #7 — CRÍTICO | Rota `/t/{tenant_id}` com ID numérico permite enumeration attack
**Agente**: Attack Surface | **Arquivo**: `routes/web.php:638`
```php
Route::get('/t/{tenant_id}', [TransparencyController::class, 'publicView']);
```
Sem throttle + ID sequencial = qualquer pessoa pode iterar 1, 2, 3... e listar todos os tenants do sistema.
**Ação**: Substituir `tenant_id` por `tenant_slug` ou UUID; adicionar `throttle:10,1`.

---

### #8 — CRÍTICO | Evolution Instance Token exposto em resposta JSON da API
**Agente**: Data Leak | **Arquivo**: `app/Http/Controllers/Api/WhatsappInstanceController.php:78-90`
```php
return response()->json(['instance' => $instance]); // instance_token incluso!
```
`WhatsappInstance` model não tem `$hidden` para `instance_token`.
**Ação**: Adicionar `protected $hidden = ['instance_token'];` ao model `WhatsappInstance`.

---

### #9 — CRÍTICO | /validar-certificado/{id} usa ID numérico sequencial (enumeration)
**Agente**: Attack Surface | **Arquivo**: `routes/web.php:642`
Throttle 60/min = 3.600 certificados por hora podem ser enumerados por qualquer IP.
**Ação**: Usar UUID para certificados; reduzir throttle para `5,1`.

---

### #10 — CRÍTICO | Senha mínima de 6 caracteres em criação de colaboradores
**Agente**: Security | **Arquivo**: `app/Http/Controllers/ManagerController.php:214`, `TeamController.php:37`
```php
'password' => 'required|min:6' // muito fraco para sistema com dados financeiros
```
**Ação**: `'password' => ['required', 'min:12', Rules\Password::defaults()]`

---

## CHECKLIST DE PRÉ-PRODUÇÃO

### 🔴 BLOQUEANTE (não subir sem corrigir)
- [ ] Remover `.env` do Git e revogar todas as chaves expostas
- [ ] Mover `WEBHOOK_HMAC_KEY` do AbacatePay para `.env`
- [ ] Adicionar idempotência em `ProcessAbacatePayWebhook` com `lockForUpdate`
- [ ] Configurar Supervisor para queue workers no VPS
- [ ] Ativar `SecurityHeaders` middleware no Kernel.php
- [ ] Aumentar HSTS para `max-age=31536000`
- [ ] Adicionar `$hidden = ['instance_token']` ao model `WhatsappInstance`
- [ ] Adicionar `$hidden = ['pix_key', 'openpix_app_id']` ao model `Tenant`
- [ ] Corrigir `/t/{tenant_id}` para usar slug/UUID
- [ ] Corrigir `/validar-certificado/{id}` para usar UUID
- [ ] `SESSION_SECURE_COOKIE=true` no `.env` de produção
- [ ] `APP_DEBUG=false` e `APP_ENV=production` confirmados no VPS

### 🟡 ALTA PRIORIDADE (corrigir na próxima sprint)
- [ ] Senha mínima `min:12` em ManagerController e TeamController
- [ ] Adicionar `lockForUpdate` em `CleanupRaffleReservations`
- [ ] Adicionar idempotência no webhook OpenPix (`lockForUpdate`)
- [ ] Adicionar `BelongsToTenant` ao model `User`
- [ ] Adicionar `$hidden` em Beneficiary (cpf, nis), Client (document), NgoDonor
- [ ] Isolar paths de upload por tenant: BannerController, TransactionController, ScheduledPostController
- [ ] Reduzir throttle de `/validar-recibo` de 30/min para 5/min
- [ ] Adicionar throttle `10,1` na rota `/sign/{token}` POST
- [ ] Remover wildcards `"*"` de `composer.json` (doctrine/dbal, guzzlehttp, open-pix/php-sdk)
- [ ] Mover AbacatePay `webhookSecret` de query string para header

### 🟢 MÉDIO PRAZO
- [ ] Adicionar `tenant_id` ao path de uploads: BannerController, ProjectTimelineController
- [ ] Mascarar CPF em respostas do WhatsApp Bot (`***.***.***-99`)
- [ ] Remover `amount` de logs em `ProcessAbacatePayWebhook`
- [ ] Sanitizar logs de response de APIs externas (AbacatePay, Meta)
- [ ] Criar páginas de erro customizadas (`resources/views/errors/404.blade.php`, `500.blade.php`)
- [ ] Adicionar CAPTCHA em `/agendar` e `/rifa/{slug}/reserve`
- [ ] Reduzir throttle de `/evo/webhook` de 500/min para valor real esperado
- [ ] Adicionar `tenant_id` ao model `Notification`
- [ ] Implementar audit logs para ações com `withoutGlobalScopes()` no admin

---

## ESTIMATIVA DE ESFORÇO POR SEVERIDADE

| Severidade | Qtd Itens | Esforço Estimado | Responsável |
|-----------|----------|-----------------|------------|
| CRÍTICO   | 10       | 2-3 dias        | Dev Sênior |
| ALTO      | 12       | 3-4 dias        | Dev        |
| MÉDIO     | 14       | 1 semana        | Dev        |
| INFO      | 8        | Backlog         | Qualquer   |
| **TOTAL** | **44**   | **~2 semanas**  |            |

---

## PONTOS POSITIVOS ENCONTRADOS ✅

- CSRF protection bem configurado (apenas webhooks excluídos)
- Senhas com `Hash::make()` em todos os pontos
- SQL Injection: zero concatenações de string em queries — 100% parametrizado
- XSS: zero uso de `{!! !!}` sem whitelist em Blade
- Webhooks de pagamento com HMAC validation (OpenPix, PagSeguro, Asaas, Meta)
- `AppServiceProvider` com try-catch protetor nas queries de boot
- `session.encrypt = true` e `http_only = true`
- `ProcessWhatsappWebhook` com idempotência por `message_id`
- Rate limiting em login (throttle:10,1), forgot-password (throttle:5,1)
- Políticas de tenant corretas em TransactionController, ProjectController, TaskController

