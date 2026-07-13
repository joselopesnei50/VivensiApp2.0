# Plano de Auditoria Vivensi — 2026-07-12

Origem: `analise-segurança.md` (8 itens). Reordenado por risco real, com base na auditoria
`AUDIT_SECURITY_2026-07-11.md` e nos módulos já fechados (AbacatePay 06-30, C2 canários, C3 LGPD, seguranca26).

> **Restrição de sessão:** C4.6 (encryption at-rest) roda em paralelo tocando `Lead`, `User` e migrations.
> Este plano NÃO altera esses models. Fixes que esbarrem neles ficam em fila até o C4 fechar e ser deployado.
> Nenhuma migration prevista em P0–P2.

---

## P0 — Isolamento de tenants (item 1)

**Objetivo:** garantir que nenhum dos 68 usos de `withoutGlobalScope('tenant')` (16 arquivos, contagem 2026-07-12)
permita acesso cruzado de dados entre tenants.

Inventário:

| Arquivo | Ocorrências | Risco a priori |
|---|---|---|
| TransparencyController | 28 | Alto — público |
| ProspectingController | 7 | Alto — leads |
| LgpdSelfServiceController | 6 | Médio — token-based (C3) |
| AntibanComputeResponseRates / ProcessCloudApiWebhook / ExportUserLgpdDataJob | 4 cada | Médio — jobs sem request context |
| SicController | 3 | Alto — público |
| CloudApiTemplateService | 3 | Médio |
| PublicRaffleController | 2 | Alto — público |
| PurgeScheduledLgpdDeletions, SendDoubleOptInWhatsapp, CloudApiOnboardingService, EvolutionWebhookController, OpenPixWebhookController, WhatsappBillingController, WhatsappConversation | 1 cada | Baixo/verificar |

Passos:
1. Classificar cada ocorrência: ✅ legítimo / ⚠️ legítimo mas frágil (sem `where('tenant_id')` na mesma query) / 🔴 vulnerável.
2. Para cada 🔴: PoC de teste (tenant A acessa recurso do tenant B) ANTES do fix — vira regressão.
3. Fix mínimo por ocorrência (where tenant_id explícito ou resolução correta).
4. ✅ Helper criado como `Model::forTenantUnscoped(int $tenantId)` no trait BelongsToTenant — par bypass+filtro atômico.
   Migração em massa dos 61 call sites NÃO feita de propósito (churn + risco de conflito com sessão C4.6);
   usar o helper daqui pra frente em código novo.
5. ✅ Suíte `tests/Feature/Security/TenantIsolationRegressionTest.php` — 8 testes: docs Transparency cross-tenant,
   portal não publicado, SIC cross-tenant, Prospecting destroy/bulk-delete cross-tenant, helper.
   (Rifa coberta por PublicRaffleStatusTest; LGPD por IdorRegressionTest do commit fdfbb04.)

**P0 CONCLUÍDO em 2026-07-12** — commits 398bcd0 (fixes export LGPD + rifa) + task 3.
Complementar ao fdfbb04 (auditoria C1 ampla da sessão paralela, foco cross-tenant; este P0 achou o intra-tenant).

**Saída:** zero 🔴; ocorrências restantes usam helper ou têm justificativa.

### Resultado da classificação (2026-07-12)

Dos 68 hits do grep, 3 são comentários (Antiban L45, EvolutionWebhook L27, WhatsappConversation docblock L19) → **65 usos reais**.

| Arquivo | Usos | Veredicto |
|---|---|---|
| TransparencyController | 28 | ✅ todos — lookup por slug único+published; demais com `where('tenant_id', $portal->tenant_id)`. Nota: eager load `category` (L673) confia no `category_id` da transação do próprio tenant — risco desprezível |
| ProspectingController | 7 | ✅ todos — `where('tenant_id', Auth::user()->tenant_id)` imediato em todos |
| LgpdSelfServiceController | 6 | ✅ todos — filtro por `user_id` ou `export_token` opaco (design C3) |
| ExportUserLgpdDataJob | 4 | 🔴 **2 vulneráveis** (L133 whatsapp_chats, L138 whatsapp_messages): export individual inclui até 200 chats + 5.000 mensagens do TENANT INTEIRO (PII de terceiros). Intra-tenant, não cross-tenant. L52 e L147 ✅ |
| ProcessCloudApiWebhook | 4 | ✅ todos — instância por `phone_number_id` (único Meta); demais keyed por tenant da instância |
| AntibanComputeResponseRates | 3 | ✅ todos — comando console cross-tenant por design, agrega por tenant com filtro explícito |
| SicController | 3 | ✅ todos — lookup por slug único+published; SicRequest filtrado por tenant do portal |
| CloudApiTemplateService | 3 | ✅ todos — keyed por instance_id/waba_id+meta_template_id (únicos Meta) |
| PublicRaffleController | 2 | ✅ show (slug tem UNIQUE global — migration 2026_03_31_144631). ⚠️ reserve (L40): sem vazamento, mas **falta filtro `status='active'`** — permite reservar em rifa pausada/encerrada (gap funcional) |
| CloudApiOnboardingService | 1 | ✅ updateOrCreate keyed por tenant_id+phone_number_id |
| EvolutionWebhookController | 1 | ✅ lookup por blind index HMAC do token |
| OpenPixWebhookController | 1 | ✅ find por FK interno (ticket validado pelo webhook) |
| WhatsappBillingController | 1 | ✅ cross-tenant por design (dashboard super admin; rota sob middleware `super_admin` confirmado) |
| PurgeScheduledLgpdDeletions | 1 | ✅ comando console cross-tenant por design |
| SendDoubleOptInWhatsapp | 1 | ✅ `forTenant($token->tenant_id)` |

**Resumo: 61 ✅ · 2 ⚠️ · 2 🔴** (mesma causa raiz, no ExportUserLgpdDataJob).

**Conclusão-chave:** NÃO há vazamento cross-tenant. O único 🔴 é superexposição intra-tenant de PII de terceiros
no export LGPD self-service. `WhatsappMessage` não tem `user_id` — mensagens não são atribuíveis a um usuário
individual, então as opções de fix são: (a) remover chats/mensagens do export individual, ou (b) incluir apenas
metadados agregados. Decisão pendente com José.

---

## P1.a — Integração DeepSeek (item 3)

Ponto de partida: `DeepSeekService` lê key de `SystemSetting deepseek_api_key`, `Http::timeout(60)->retry(2)`.
13 serviços consomem (Bruce, Bruno, 7 Sala de Estratégia, LeadQualification, SocialAI, MarketingAI).

1. Vazamento de PII nos prompts (CPF/telefone/e-mail de beneficiários/leads) → mascaramento antes do envio.
2. Tratamento de erro: timeout/429/500 vaza stack/mensagem crua? Backoff nas filas?
3. Key nunca em logs nem no HTML do /admin/settings (verificar se canário C2 cobre `deepseek_api_key`).
4. Quota de IA por tenant (estilo `EmailQuotaService`) — hoje um tenant pode drenar a conta.
5. Prompt injection via conteúdo do usuário — replicar padrão ANTI-ALUCINACAO do Bruno nos demais serviços.

**Saída:** matriz serviço × dados × risco; fixes; canário da key.

### Resultado P1.a (2026-07-12)

**Verificado:** key isolada em `SystemSetting` (canário C2 já cobre `deepseek_api_key`), HTTPS+timeout OK, anti-injection presente onde importa (LeadQualification enum+normalize, Financial/Intelligence "proibe fora da lista", Bruno ANTI-ALUCINACAO).

**Corrigido:**
- `DeepSeekService::chat()` retorna erro genérico + `error_code` (`ai_not_configured`, `ai_invalid_key`, `ai_upstream_error`, `ai_connection_error`, `ai_quota_exceeded`). Body cru fica só no Log. Fecha vazamento em 6 controllers (BruceAi, Chat, PersonalBudget, BrunoSandbox, NgoGrant, Whatsapp) sem tocá-los.
- `retry(2, 100, null, false)` — preserva Response final pra diferenciar 401/5xx (antes retry lançava exception e apagava a distinção UX).
- `AiCallQuotaService` novo: cap diário por tenant (default 500, override via `SystemSetting ai_daily_quota_per_tenant`), TTL até fim do dia UTC.
- Assinatura `DeepSeekService::chat($messages, $model=null, $tools=null, ?int $tenantId=null)` — propagado nos 8 call sites que conhecem o tenant: BruceAiService (chat + dailyInsight), LeadQualificationService, 5 StrategyRoom (Programs, Mobilization, Intelligence, Financial, ChiefStrategist). ChatController e PersonalBudgetController mantêm sem — já limitados por `web_ai` per-user, não são vetor de background.

**Testes:** `DeepSeekHardeningTest` (8 verdes): sanitização de body upstream (500/401), sem key, cota per-tenant, isolamento de cota, bypass sem tenantId, override via SystemSetting. Security 62/62, IA 102/102.

**Reclassificado:** "prompt injection via WhatsApp inbound" (agent marcou CRÍTICO) → BAIXO. Output do LeadQualification é enum forte + normalize com max 300 chars; sem canal pra exfil de PII do sistema.

**Não implementado (fora de escopo mínimo):** ChatController/PersonalBudgetController sem tenantId propagado (já cobertos por `web_ai` per-user); PII masking nos prompts (não é vazamento cross-tenant — a DeepSeek é operador em conformidade com a política LGPD do próprio tenant).

## P1.b — Campanhas de e-mail (item 5)

Ponto de partida: `EmailQuotaService`, controllers Admin/Manager/Ngo EmailCampaignController, `BrevoService`.

1. Quota aplicada nos 3 controllers E no job de envio (sem bypass).
2. Anti-abuso: opt-in na importação de destinatários; rate limit por tenant/dia.
3. HTML da campanha via Purifier; header injection (subject/reply-to).
4. Tenant scope nas listas de destinatários (cruza com P0).
5. SPF/DKIM/DMARC — checklist manual de DNS/Brevo (infra, não código).
6. Webhook de bounce/unsubscribe da Brevo respeitado nos próximos envios.

**Saída:** relatório de gaps + fixes + checklist DNS.

### Resultado P1.b (2026-07-12)

**Verificado:** Manager já usa EmailQuotaService com refund em todas as ramificações de falha; NGO donors_optins já filtra opt-in.

**Corrigido:**
- **NGO `send()`**: aplica `EmailQuotaService::tryConsume()` + refund em cada branch de erro (mesmo padrão do Manager). Antes um user NGO comum podia disparar ilimitadamente e drenar reputação Brevo.
- **NGO `resolveRecipients()`**: forca `email_marketing_opt_in=true` também no tipo legado `donors` (não só em `donors_optins`). LGPD art. 8: consentimento sempre. Doadores sem opt-in devem receber transacionais via `BrevoService::sendEmail`, nunca via módulo campanha.
- **`iframe sandbox=""`** nos 3 views (`admin/manager/ngo email_campaigns/show.blade.php`) — `srcdoc` sem sandbox execute JS inline com origem herdada; era XSS armazenado real intra-tenant (manager cria campanha com `<script>fetch('/api/...', {credentials:'include'})</script>` e outros users do tenant que abrem o show ficam expostos). Sandbox só com `allow-popups` — email pode abrir link em nova aba, mas JS não roda.
- **CRLF injection**: `not_regex:/[\r\n]/` em `subject` e `sender_name` nos 3 controllers store (Admin, Manager, NGO). Bloqueia forjar Bcc:, From:.

**Testes:** `EmailCampaignHardeningTest` (12 verdes) — resolveRecipients em 3 cenários (com/sem opt-in, isolamento tenant), quota consume+refund, CRLF em NGO+Manager+Admin store, sandbox nas 3 views. Security 74/74 sem regressão.

**Reclassificado:**
- **HTMLPurifier no `html_content`**: NÃO implementado de propósito. HTMLPurifier quebra CSS inline de emails; defesa correta é o `sandbox=""` no iframe do painel (feito). No envio, o Brevo tem sua própria validação. Purifier aqui traria dano ao produto sem ganho real.
- Agente overhype: agent classificou "srcdoc é seguro" (ERRADO — sem `sandbox=` explícito, srcdoc executa JS inline com origem herdada).

**Recomendação p/ sprint futuro (não implementado — escopo maior):**
- **Webhook Brevo** para bounces/unsubscribes em tempo real: nova rota `/webhook/brevo` com HMAC (`webhook_key` do Brevo), tabela `email_bounces` (`email`, `tenant_id`, `type`, `reason`, `received_at`), filtro em `resolveRecipients` para excluir bounces/unsubs conhecidos. Hoje o sistema depende de polling `getBrevoEmailCampaignStats()` — métricas ficam horas/dias defasadas e a próxima campanha reenvia pra quem já se desinscreveu (LGPD art. 18 II).
- **`landing_page_leads.unsubscribed_at`**: coluna não existe hoje; NGO/Manager que enviam pra `leads` não conseguem respeitar opt-out via link do email. Requer migration + link `unsubscribe` no template Brevo.
- **SPF/DKIM/DMARC**: checklist manual de infra fora do código — validar registros DNS do domínio de envio no painel Brevo e no provedor de DNS.

## P1.c — Super Admin: segurança (item 6, parte auditoria)

(2FA forçado e canário de secrets já existem.)

1. Teste automatizado: 100% das rotas `admin/*` com middleware `EnsureSuperAdmin`.
2. AuditLog em ações sensíveis (impersonation, edição de tenant, settings, `admin/bot/users/{id}/phone`).
3. Mass assignment nos POSTs do admin (ex.: `BotController::save`).

### Resultado P1.c (2026-07-12)

**Verificado:**
- Cobertura EnsureSuperAdmin: teste automatizado ITERA `Route::getRoutes()` e prova que 100% das rotas com nome `admin.*` (GET, sem params) recusam user comum. Falso positivo do agent: rotas `whatsapp/broadcast/*` e `whatsapp/optin/*` usam namespace `Admin\` MAS são controllers **tenant** (usam `tenant_id` do user autenticado) — nomenclatura confusa, não vulnerabilidade.
- Mass assignment: nenhum caso — todos os POSTs admin validam antes de create/update ou usam atribuição campo-a-campo.
- AuditLog já existia em: `tenant.suspend/activate/delete/create/email_quota_updated`.

**Corrigido — 6 pontos passaram a gravar `AdminAuditLog`:**
- `AdminSettingsController::store` → `settings.updated` — logs `keys_touched` mas NUNCA valores (secret rotation é auditável, valor bruto fica só na tabela `system_settings`).
- `BotController::save` → `bot.config_updated` — logs `fields_changed`.
- `BotController::saveAtendimento` → `bot.attendance_updated` — logs contadores (`faq_entries`, `ai_enabled`).
- `BotController::updateUserPhone` → `bot.user_phone_updated` — logs `target_id` + `target_name`, sem o número em si.
- `AdminTeamController::store/update/destroy` → `team.member_added/updated/removed` — logs `target_id`, `role`, `department`, sem senha.

**Testes:** `AdminAuditCoverageTest` (8 verdes) — cobertura de middleware (iteração dinâmica), sanity de super_admin, AuditLog em cada um dos 6 pontos + 3 canários (deepseek/brevo keys não aparecem no log, senha não aparece, telefone não aparece). Security 82/82 sem regressão.

---

## P2 — AbacatePay: regressão (item 4)

Módulo fechado 2026-06-30 (idempotência + HMAC + AuditLog + reconcile 5/5min). Apenas:
1. Rodar suite existente do módulo.
2. Diff dos arquivos desde o fechamento; se nada mudou e suite verde → encerrar.
3. Check novo: logs de webhook sem payload de pagamento em plaintext desnecessário.

### Resultado P2 (2026-07-12)

**Regressão limpa:**
- **Zero commits** no módulo AbacatePay desde o fechamento em 2026-06-30 (`git log --since` nos 8 arquivos do módulo).
- **`AbacatePayWebhookTest` 10/10 verde** — HMAC, secret via header/query, dispatch, dev mode, event ausente.
- Sem drift desde o fechamento.

**Fixes leves de log (defesa em profundidade LGPD):**
- `ProcessAbacatePayWebhook.php:337`: `email` do fallback → `email_hash` SHA-256. Mantém correlação pra debug sem gravar PII em log de aplicação.
- `AbacatePayService.php:65`: `createCheckout` erro logava `$response` inteiro; agora grava só metadata (`error`, `error_code`, `success`, `externalId`). Se a Abacate retornar dados sensíveis no `error` no futuro, o payload cru não vaza.

**Suite 10/10 verde após os fixes**, sem novos testes (mudanças são só na estrutura do log, sem lógica).

---

## Sprint separado (produto — fora da auditoria)

- Item 7 — Bruno (`BrunoSandboxController`/`BrunoMetricsController`): NLU, KB, dúvidas complexas.
- Item 8 — Bot interno (`BotController`): qualidade de suporte (endpoints cobertos em P1.c).
- Item 6 (parte produto) — melhorias no painel executivo do super admin.

## Itens do documento original descartados/reinterpretados

- Item 2 ("todos os módulos funcionando") → substituído por suite de testes (617+) + smoke test; inexecutável como auditoria.
- PCI (item 4) → não se aplica diretamente: cartão fica no gateway AbacatePay.

## Ordem e regras

P0 → P1.a → P1.b → P1.c → P2. Cada fase: testes verdes + commit próprio (sem co-autor).
Fixes tocando `Lead`/`User` aguardam deploy do C4.
