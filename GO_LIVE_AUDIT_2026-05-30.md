# Auditoria de Go-Live — Vivensi SaaS
**Data:** 2026-05-30 · **Escopo:** leitura estática de todo o código (nada alterado, nada publicado)
**Stack real:** Laravel 9 · Sanctum 2.14 · PHP ^8.0 · MySQL · Redis · queue=redis · AWS S3
**Tamanho:** 510 rotas web · 89 models · 108 controllers · 33 services · 21 jobs · 169 migrations

---

## Veredito em uma frase
O sistema está **muito melhor do que os relatórios antigos sugerem**. O isolamento multi-tenant
— o maior risco de um SaaS — está **consistente e bem feito**. Não há vazamento de dados entre
clientes explorável no código auditado. Os bloqueadores reais são **operacionais e de configuração**,
não falhas estruturais. Dá para lançar com uma lista curta de ajustes.

---

## ✅ Já está pronto (validado contra o código atual)
- `.env` fora do Git; `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` no `.env.example`
- HMAC AbacatePay vem de config (não hardcoded) — `AbacatePayService.php:123`
- Idempotência + `lockForUpdate` no webhook AbacatePay — `ProcessAbacatePayWebhook.php:57`
- Webhooks Asaas e PagSeguro validam token com `hash_equals` — `AsaasWebhookController.php:18`, `PagSeguroWebhookController.php:23`
- OpenPix valida assinatura — `OpenPixWebhookController.php:29`
- `SecurityHeaders` ativo (HSTS + CSP) — middleware global no `Kernel.php`
- Models sensíveis com `$hidden`: `WhatsappInstance` (token), `Tenant` (pix_key, openpix_app_id)
- Rotas públicas com slug/UUID + throttle: `/t/{slug}`, `/validar-certificado/{uuid}`
- Guard anti-SSRF no proxy WhatsApp — `WhatsappInstanceController::isPrivateOrMetadataHost()`
- Isolamento multi-tenant via `BelongsToTenant` em ~60 models + checagem de ownership nos controllers
- **Fila/queue VERIFICADA EM PRODUÇÃO (2026-05-30):** `config('queue.default')=redis`; workers Supervisor
  rodando `queue:work redis` (default x2, whatsapp, emails) todos `RUNNING`; scheduler 19 dias no ar;
  `LLEN queues:default/whatsapp = 0`; zero failed jobs. PIX/WhatsApp/automações processando normalmente.

---

## 🚨 P0 — Antes de cobrar o primeiro cliente
1. **Throttle nos webhooks Asaas/PagSeguro** — `routes/api.php:43-44` não têm rate limit (AbacatePay tem).
   Adicionar `->middleware('throttle:200,1')`. (Auth já está OK.)

> NOTA: o item "queue workers via Supervisor" foi VERIFICADO e está saudável (ver seção acima) — não é mais bloqueador.

## ⚠️ P1 — Primeira semana pós-launch
3. **Tokens Sanctum nunca expiram** — `config/sanctum.php:47` (`expiration => null`) + abilities `['*']`.
   Definir expiração (ex: 30–90 dias) e restringir abilities.
4. **`User` sem `BelongsToTenant`** — hoje protegido por filtro manual nos controllers, mas é
   bomba-relógio. Adicionar o trait (com `withoutGlobalScopes()` para super_admin).
   Endurecer `WhatsAppBotController::findUserByPhone()` (`:90`) para match exato de telefone.
5. **Política de senha inconsistente** — registro/reset/perfil ainda em `min:8` sem complexidade
   (`RegisterController.php:40`, `ResetPasswordController.php:27`, `ProfileController.php:48`).
   Team/Manager já estão em `min:12`. Unificar numa regra `Password::min(12)->mixedCase()->numbers()->uncompromised()`.

## 🔧 P2 — Próxima sprint
6. **SSRF DNS-rebinding** no proxy WhatsApp — guard valida DNS no save, Guzzle re-resolve no envio.
7. **Uploads sensíveis no disco `public`** — comprovantes PIX, anexos financeiros, docs de editais
   deveriam ir para disco `private` servido por controller com checagem de tenant.
8. **XSS armazenado** em conteúdo de páginas/blog (autorado por admin; risco menor) — sanitizar com HTMLPurifier.
9. **`hash_equals` no bot_token** — `WhatsAppBotController.php:45`.
10. **`supervisord.conf` do repo desatualizado** — diz `queue:work database` e `--tries=3`, mas a produção
    roda `queue:work redis` e `--tries=1`. Não quebra nada hoje, mas é armadilha em deploy futuro. Alinhar o
    arquivo do repo com a realidade da produção.
11. **`--tries=1` nos workers de pagamento/WhatsApp** — falha transitória (ex: Evolution API piscou) não é
    re-tentada. Hoje `failed_jobs` está zerado, mas considerar `--tries=3` com backoff para jobs financeiros.

## 🧹 Higiene de repositório
- Remover do repo: `evolution-api-main (1).zip` (1.5MB), screenshots `.jpeg`, PDFs, `create_settings.sql`,
  `local_files.txt`, `remote_files.txt`, `cookies.txt`.
- Fixar versões wildcard no `composer.json` (`pestphp/pest-plugin-laravel: "*"`).

## 📌 Estratégico (não trava o launch, mas planeje)
- **Laravel 9 e PHP 8.0 estão fora do suporte de segurança** (desde fev/2024 e nov/2023). Para um
  produto que processa pagamentos e dados pessoais (LGPD), planejar upgrade para versão LTS suportada.
- A documentação interna (`CLAUDE.md`) diz "Laravel 11 / Livewire 3 / Filament" — **isso é incorreto**,
  não existem no projeto. Corrigir a doc para não enganar o time.

---

## Conclusão
**Pode lançar.** O único P0 restante é uma linha de `throttle` nos webhooks Asaas/PagSeguro — o item
operacional de fila foi verificado em produção e está saudável. Os P1 dão para fazer na primeira semana.
Os relatórios antigos na raiz (`AUDIT_SUMMARY.md`, etc.) estão **desatualizados** — descrevem problemas
já corrigidos. Use este documento como referência atual.
