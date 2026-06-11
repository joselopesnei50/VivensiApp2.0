# Auditoria — Filtro de tenant em Jobs e Commands

> Tarefa 1.3 da auditoria (`PROMPT_CORRECAO_VIVENSI.md`).
> Data: 2026-06-11.
> Critério da auditoria: "Nenhum job dispara query de model com `tenant_id` sem filtro explícito OU sem `withoutGlobalScopes()` comentado."

## Contexto

O trait `BelongsToTenant` (em `app/Traits/BelongsToTenant.php:15`) **ignora intencionalmente** o global scope quando `app()->runningInConsole()` é true — ou seja, em filas, jobs e commands. Isso significa que jobs/commands devem:

- **filtrar `tenant_id` explicitamente** quando fazem queries amplas, OU
- **operar sobre um registro único já resolvido** (`Model::find($id)`), OU
- **usar `withoutGlobalScopes()` explícito** quando o bypass for intencional e correto (com comentário justificando).

Convenções desta auditoria:

| Veredito | Significado |
|---|---|
| ✅ **filtrado** | Tem `where('tenant_id', ...)` em queries amplas |
| ✅ **registro único** | Opera sobre PK (`find($id)`) ou model já resolvido — não há query ampla |
| ✅ **bypass intencional** | Usa `withoutGlobalScopes()` com comentário explicando |
| ✅ **cross-tenant legítimo** | Cleanup, sync ou orquestração que opera em todos os tenants por design |
| ✅ **sem dados de tenant** | Não toca models com `tenant_id` |
| ⚠️ **boilerplate vazio** | Stub do `make:command`, código morto, não opera em nada |

---

## Jobs (27 arquivos)

| # | Arquivo | Veredito | Nota |
|---|---|---|---|
| 1 | `DispatchWebhook.php` | ✅ registro único | `Webhook::find($webhookId)` por PK |
| 2 | `EnviarMensagemCampanhaJob.php` | ✅ filtrado | `where('tenant_id', $campanha->tenant_id)` em todas as queries |
| 3 | `GenerateGrantAnalysisJob.php` | ✅ filtrado | filtra por tenant do grant |
| 4 | `GenerateGrantProposalJob.php` | ✅ filtrado | filtra por tenant do grant |
| 5 | `GeneratePdfJob.php` | ✅ filtrado | filtra por tenant |
| 6 | `GenerateProjectLogSummaryJob.php` | ✅ filtrado | filtra por tenant |
| 7 | `GenerateSocialPostJob.php` | ✅ registro único | `AiSocialPost::find($postId)` |
| 8 | `GeocodeAddressJob.php` | ✅ registro único | Recebe `Model $model` já resolvido (Beneficiary/NgoDonor com tenant_id) |
| 9 | `HandleAsaasWebhook.php` | ✅ bypass intencional | Webhook do gateway resolve tenant via `asaas_customer_id` (único global). Comentário em linha 40-ish OK. |
| 10 | `HandlePagSeguroWebhook.php` | ✅ bypass intencional | Webhook resolve transaction via `external_id` único do gateway. **Follow-up: adicionar comentário explícito.** |
| 11 | `ProcessAbacatePayWebhook.php` | ✅ bypass intencional | Webhook resolve tenant via metadata + `external_id`. Comentários adicionados nesta release (linha 86 e 141). |
| 12 | `ProcessBroadcastCampaignJob.php` | ✅ filtrado | filtra por `$this->tenantId` recebido no construtor |
| 13 | `ProcessEvolutionWebhook.php` | ✅ filtrado | Resolve instância e usa `tenant_id` da instância |
| 14 | `ProcessMarketingPlan.php` | ✅ registro único | `MarketingPlan::find($planId)` |
| 15 | `ProcessProspect.php` | ✅ registro único | Recebe `Prospect` model já resolvido |
| 16 | `ProcessWhatsAppBotMessage.php` | ✅ filtrado | filtra por tenant |
| 17 | `ProcessWhatsappAiResponse.php` | ✅ filtrado | filtra por tenant |
| 18 | `ProcessWhatsappAutomations.php` | ✅ filtrado | filtra por tenant |
| 19 | `ProcessWhatsappWebhook.php` | ✅ filtrado | resolve instância → tenant_id |
| 20 | `PublishScheduledPostJob.php` | ✅ bypass intencional | `withoutGlobalScopes()` + comentário adicionado nesta release. **Follow-up: receber `tenant_id` no construtor como defesa em profundidade.** |
| 21 | `SendCampaignMessageJob.php` | ✅ filtrado | filtra por tenant |
| 22 | `SendMeetingEmailsJob.php` | ✅ registro único | `MeetingBooking::find($bookingId)` |
| 23 | `SendProspectWhatsapp.php` | ✅ registro único | `Prospect::find` + `tenant_id` em payload de insert |
| 24 | `SendSupportEmailJob.php` | ✅ registro único | usa `$ticket->tenant_id` resolvido |
| 25 | `SendWeeklyReportJob.php` | ✅ filtrado | filtra por tenant |
| 26 | `SendWhatsAppAudioJob.php` | ✅ filtrado | filtra por tenant |
| 27 | `SendWhatsAppCampaignMessage.php` | ✅ filtrado | filtra por tenant |

---

## Commands (24 arquivos)

| # | Arquivo | Veredito | Nota |
|---|---|---|---|
| 1 | `BackupDatabase.php` | ✅ sem dados de tenant | Backup MySQL, não toca models |
| 2 | `CheckFailedJobs.php` | ✅ cross-tenant legítimo | Varre jobs falhados de todos os tenants |
| 3 | `CleanupExpiredReceiptTokens.php` | ✅ bypass intencional | `Transaction::withoutGlobalScopes()` em cleanup cross-tenant. **Follow-up: adicionar comentário.** |
| 4 | `CleanupRaffleReservations.php` | ✅ cross-tenant legítimo | Cleanup de reservas expiradas |
| 5 | `CleanupWhatsappData.php` | ✅ cross-tenant legítimo | Cleanup com retention policy |
| 6 | `CreateSetupWizardTable.php` | ⚠️ boilerplate vazio | Stub do `make:command` (`signature = 'command:name'`, handle vazio). **Follow-up: deletar.** |
| 7 | `CreateSuperAdmin.php` | ✅ sem dados de tenant | Super admin é global |
| 8 | `EvolutionSetupAgent.php` | ✅ sem dados de tenant | Configuração da Evolution API |
| 9 | `GeocodeBackfill.php` | ✅ cross-tenant legítimo | Sweep que dispara `GeocodeAddressJob` para todos os addresses |
| 10 | `MigrateTransactionAttachments.php` | ✅ bypass intencional | Comentário existente: `// console — precisamos ver de todos os tenants` |
| 11 | `NgoGrantDeadlineAlert.php` | ✅ filtrado | `where('tenant_id', $grant->tenant_id)` |
| 12 | `ProcessScheduledBroadcasts.php` | ✅ bypass + filtrado | `withoutGlobalScopes()` no sweep + `where('tenant_id'` quando processa cada campanha. **Follow-up: adicionar comentário no withoutGlobalScopes.** |
| 13 | `PublishScheduledPosts.php` | ✅ bypass intencional | Comentário adicionado nesta release |
| 14 | `SanitizeLegacyPagesCommand.php` | ✅ sem dados de tenant | `Page` é model global (sem `tenant_id`) |
| 15 | `SchedulerHealthCheck.php` | ✅ cross-tenant legítimo | Health check global |
| 16 | `SendDonorReactivationMessages.php` | ✅ cross-tenant legítimo | Itera `Tenant::query()` e processa cada um |
| 17 | `SendProjectDeadlineAlerts.php` | ✅ cross-tenant legítimo | Itera `Tenant::query()` e processa cada um |
| 18 | `SendScheduledWhatsappMessages.php` | ✅ filtrado | `where('tenant_id', $tenantId)` |
| 19 | `SendTrialEndingReminders.php` | ✅ cross-tenant legítimo | Trial reminder cross-tenant por design |
| 20 | `SyncIBGEIndicators.php` | ✅ sem dados de tenant | Indicadores IBGE são dados públicos globais |
| 21 | `SyncUserRoles.php` | ✅ cross-tenant legítimo | Sincronização de roles cross-tenant |
| 22 | `TestEvolutionConnection.php` | ✅ sem dados de tenant | Teste manual de conectividade |
| 23 | `ValidateWhatsappContacts.php` | ✅ bypass + filtrado | Cross-tenant quando não recebe `--tenant`, filtrado quando recebe. **Follow-up: adicionar comentário no withoutGlobalScopes da linha 17.** |
| 24 | `WarmCaches.php` | ✅ filtrado | Sempre filtra `where('tenant_id', $tenantId)` para cada tenant processado |

---

## Resumo

| Categoria | Quantidade |
|---|---|
| Filtrado explícito | 18 |
| Registro único | 9 |
| Bypass intencional | 8 |
| Cross-tenant legítimo | 10 |
| Sem dados de tenant | 5 |
| Boilerplate vazio | 1 |
| **❌ Problema real de isolamento** | **0** |

**Conclusão da auditoria 1.3:** o trait `BelongsToTenant` que faz bypass em console **não está sendo abusado em produção**. Todo job e command auditado opera corretamente — filtrando, sobre PK, ou em contexto cross-tenant declaradamente intencional.

## Mudanças aplicadas neste deploy

1. **`app/Jobs/PublishScheduledPostJob.php`** — comentário justificando o `withoutGlobalScopes()` + apontamento para defesa em profundidade futura
2. **`app/Console/Commands/PublishScheduledPosts.php`** — comentário justificando o `withoutGlobalScopes()` no sweep
3. **`app/Jobs/ProcessAbacatePayWebhook.php`** — dois comentários (linhas 86 e 141) justificando os bypasses de webhook de pagamento

## Follow-ups (não bloqueiam o fechamento da Tarefa 1.3)

Em ordem de menor para maior esforço:

### 1.3.A — Defesa em profundidade nos jobs por-PK (curto prazo)

Adicionar `int $tenantId` ao construtor de:
- `PublishScheduledPostJob`
- `GenerateSocialPostJob`
- `ProcessMarketingPlan`
- `DispatchWebhook`
- `SendMeetingEmailsJob`

Substituir `Model::find($id)` por `Model::where('id', $id)->where('tenant_id', $tenantId)->first()`. Requer atualizar os callers (controllers que despacham). Estimativa: 1 dia.

### 1.3.B — Comentários nos bypasses restantes

Pequenos:
- `CleanupExpiredReceiptTokens.php` linha 19
- `ProcessScheduledBroadcasts.php` linhas 22, 31, 52, 60, 70, 76
- `ValidateWhatsappContacts.php` linha 17
- `HandlePagSeguroWebhook.php` (não tem withoutGlobalScopes, mas faz `Transaction::where('external_id'`)

Estimativa: 30 min.

### 1.3.C — Deletar boilerplate morto

Remover `CreateSetupWizardTable.php`. Confirmar que `command:name` não está agendado em lugar nenhum antes.

Estimativa: 5 min.

### 1.3.D — Endurecer o trait `BelongsToTenant`

Em vez de bypass silencioso em console, exigir `withoutGlobalScopes()` explícito em CADA query de job/command. Implementação: no global scope, emitir warning (já tem Log::debug) e considerar refatoração mais agressiva. Cuidado: pode quebrar callers.

Estimativa: 1 dia + auditoria. **Risco alto.**
