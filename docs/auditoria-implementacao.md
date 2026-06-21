# Auditoria de Implementação — Painel Gestor de Projetos

**Data:** 2026-06-21
**Roadmap auditado:** `docs/ROADMAP_GESTOR_PROJETOS.md`
**Método:** verificação contra o código real (arquivo:linha). Git log/branches **não** foram usados como prova; só como pista.
**Escopo:** Painel do Gestor de Projetos (não tocou NGO, VivensiCT, Mobiliza).

Legenda: ✅ FEITO · 🟡 PARCIAL · ❌ NÃO ENCONTRADO

---

## Fase 0 — Bug das mensagens vazias (OmniChannel)

| Item | Status | Evidência (arquivo:linha) | Pendências |
|---|---|---|---|
| Webhook persiste antes de broadcast | ✅ | `app/Http/Controllers/Api/EvolutionWebhookController.php:69`; `app/Jobs/ProcessEvolutionWebhook.php:138–189` | — |
| Extração de corpo para todos os tipos Baileys | ✅ | `app/Jobs/ProcessEvolutionWebhook.php:381–540` (conversation, extendedText, image/video caption, audio, document, sticker, location, contact, buttonsResponse, listResponse, interactiveResponse, reaction, fallback) | — |
| `tenant_id` scoping na persistência e reload | ✅ | `app/Jobs/ProcessEvolutionWebhook.php:65,78–80,139,164,191,309–312`; trait `BelongsToTenant` em `WhatsappChat`/`WhatsappMessage` | — |
| Feature test (payload → persist → reload thread) | 🟡 | Unit cobre extração em `tests/Unit/Jobs/ProcessEvolutionWebhookExtractionTest.php:53–237` (incl. invariante `test_invariante_content_nunca_vazio` :211–237); `tests/Feature/Webhooks/EvolutionWebhookTest.php:30–42` só verifica dispatch | Falta feature end-to-end: POST webhook → `assertDatabaseHas` em `whatsapp_messages` com `content` não-vazio e tenant correto, depois recarregar a thread |

**LGPD:** OK — `location` e `contact` são persistidos como marcadores, sem coordenadas e sem vCard (`ProcessEvolutionWebhook.php:459–477`).

---

## Fase 1 — Perfil Operacional

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Migration `tenant_operational_profiles` com `categoria` (enum), `instrucao`, `vocabulario` | ✅ | `database/migrations/2026_06_17_000001_create_tenant_operational_profiles_table.php:16–26`; enum em `app/Models/TenantOperationalProfile.php:13–17` | — |
| Model com escopo de tenant | ✅ | `app/Models/TenantOperationalProfile.php:27–46`; relação `Tenant::operationalProfile()` em `Tenant.php:64` | — |
| Tela de edição no painel | ✅ | `resources/views/manager/perfil-operacional/edit.blade.php:39–93`; controller `app/Http/Controllers/Manager/PerfilOperacionalController.php:30,49–82` (audit log de mudança em :58–82) | — |
| `PerfilOperacionalService` (KPIs, contexto Bruce, templates) | ✅ | `app/Services/PerfilOperacionalService.php:85–262` (KPIs :173–200; vocabulário :206–227; contexto Bruce com Lei 9.504/97 e TSE :229–262) | — |
| 2.1 — KPI configurável na Central de Comando | ✅ | `app/Http/Controllers/DashboardController.php:333–376` (`resolveKpiSource()` :362–376 lê `whatsapp_inbound_total` :365); `resources/views/dashboards/manager.blade.php:103–129` | ⚠️ ver bug de fonte de `leads_total` registrado na Fase 5 |
| Testes | ✅ | `tests/Unit/Services/PerfilOperacionalServiceTest.php` (KPIs por categoria :46–64, vocabulário :73–88, contexto eleitoral :90–102); `tests/Unit/Http/Requests/Manager/UpdatePerfilOperacionalRequestTest.php:15–123` (bloqueia CPF/telefone/e-mail no `instrucao`) | — |

**LGPD:** OK — bloqueio de PII no campo `instrucao` evita vazamento via system prompt do Bruce; mudanças de categoria/instrução geram audit log com IP.

---

## Fase 2 — Proteções rápidas (anti-ban + cota e-mail)

### 4.2 — Aceite anti-ban antes de criar instância

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Migração `whatsapp_anti_ban_acceptances` versionada | ✅ | `database/migrations/2026_06_17_120001_create_whatsapp_anti_ban_acceptances_table.php:19–32` (tenant_id, user_id, version, terms_hash, ip_address, user_agent, accepted_at; unique [tenant_id, version]) | — |
| Service `AntiBanTermService` | ✅ | `app/Services/AntiBanTermService.php:23,29–45,64–72,90–95,102–124` | — |
| Gate na criação da instância | ✅ | `app/Http/Controllers/Api/WhatsappInstanceController.php:54–65` (HTTP 423 quando `!hasAcceptedCurrent()`) | — |
| Endpoint para exibir/aceitar | ✅ | `app/Http/Controllers/WhatsappAntiBanController.php:26–47, 54–77` | — |
| Exibição no Super Admin | ✅ | `app/Http/Controllers/AdminController.php:297–319` | — |
| Testes | 🟡 | Unit em `tests/Unit/Services/Messaging/AntiBanManagerOptOutTest.php` e tuning; sem feature test do HTTP 423 no fluxo de criação de instância | Adicionar feature test `WhatsappInstanceController@store` sem aceite → 423 |

### 3.3 — Cota diária de e-mail

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Migration `daily_email_quota` default 50 (idempotente) | ✅ | `database/migrations/2026_06_17_120002_add_daily_email_quota_to_tenants_table.php:21–26,33` | — |
| Service `EmailQuotaService` (consume/refund/reset TTL 26h) | ✅ | `app/Services/EmailQuotaService.php:23,25–29,58–74,80–93,98,107` | — |
| Enforcement no envio + refund em falha | ✅ | `app/Http/Controllers/Manager/ManagerEmailCampaignController.php:129–151` (`tryConsume` :134; refund em falha Brevo :158) | ⚠️ enforcement está no controller, não em Job — válido funcionalmente, mas roadmap especifica "Job de envio" |
| Edição da cota no Super Admin | ✅ | `app/Http/Controllers/AdminController.php:325–348` (range 0–100000, AdminAuditLog) | — |
| Testes | ✅ | `tests/Unit/Services/EmailQuotaServiceTest.php:1–174` (default :62–75, consume/refund :101–148, isolamento tenant :150–159) | — |

**LGPD:** OK — termos versionados com IP/UA/timestamp; cota escopada por tenant; admin actions auditadas.

---

## Fase 3 — Transferência + Kanban geral

### Transferência de atendimento

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| `assigned_to` em `whatsapp_chats` | ✅ | `database/migrations/2026_01_30_173516_create_whatsapp_tables.php:39`; `app/Models/WhatsappChat.php:32,49` | — |
| Ação "Transferir para…" listando usuários do tenant | ✅ | `app/Services/Messaging/ChatTransferService.php:119–127` (`eligibleAgents`); endpoints `app/Http/Controllers/WhatsappController.php:184–233` | — |
| Audit log + notificação | ✅ | `ChatTransferService.php:49–60` (`WhatsappAuditLog` event `chat_transferred`); :62–72 cria `Notification` broadcast `notifications.{user_id}` (`app/Events/NotificationCreated.php:34–37`) | — |
| Filtros minhas/não atribuídas/todas | ✅ | `resources/views/whatsapp/chat.blade.php:769–798, 1525–1543` | — |
| Testes | ✅ | `tests/Unit/Services/Messaging/ChatTransferServiceTest.php:44–101` | — |

### Kanban geral (2.2)

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Migrations boards/columns/cards com tenant + FK conversa | ✅ | `2026_06_17_120003_create_kanban_boards_table.php:17–27`; `120004_create_kanban_columns_table.php:15–26`; `120005_create_kanban_cards_table.php:20–37` (com `whatsapp_chat_id` :index [tenant_id, whatsapp_chat_id]) | — |
| Models com trait `BelongsToTenant` | ✅ | `app/Models/KanbanBoard.php:11`; `KanbanColumn.php:11`; `KanbanCard.php:12,49–52` | — |
| `KanbanService` | ✅ | `app/Services/KanbanService.php:32–66, 71–83, 93–118, 120–124, 131–165, 173–178` | — |
| UI com drag-and-drop | 🟡 | `resources/views/manager/kanban/index.blade.php:1–78` (atributos SortableJS, Blade + vanilla JS) | Roadmap pede Livewire 3 — implementação atual é Blade+JS. Equivalente funcional, mas arquitetura diverge |
| Ação "criar card a partir de mensagem" | ✅ | `app/Http/Controllers/Manager/KanbanController.php:152–174`; rota em `routes/partials/shared.php`; UI em `resources/views/whatsapp/chat.blade.php:1260–1298, 2766–2815` | — |
| Templates de colunas por Perfil Operacional | ✅ | `KanbanService.php:131–165` (eleitoral/mobilização/cultural/empresarial/outro) | — |
| Tempo real ao mover card | ❌ | DOM atualiza no cliente via JS, mas não há broadcast — outros usuários não veem em tempo real | Adicionar event `KanbanCardMoved` + Echo no front |
| Feature test mover card | ❌ | Só `tests/Unit/Services/KanbanServiceTest.php:102–105` (POSITION_GAP) | Adicionar feature test do endpoint `moveCard` |

**LGPD:** OK — tenant scoping consistente; transferência audita IP via `WhatsappAuditLog` (verificar campo IP); notificações por private channel.

---

## Fase 4 — Camada de IA (Bruce)

### 2.3 — Qualificação + Kanban

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Classifica intenção e cria card | ✅ | `app/Services/Messaging/LeadQualificationService.php:58, 105, 119–120`; `app/Http/Controllers/WhatsappController.php:394, 434, 437` (KanbanService::createCard com meta) | — |
| Prompt recebe contexto do Perfil Operacional | ✅ | `LeadQualificationService.php:43, 119–120` (injeta `PerfilOperacionalService::bruceContextForCategoria()`) | — |
| Roteamento LGPD na qualificação | ✅ | `LeadQualificationService.php:105` (`chooseProvider`) — eleitoral → Gemini BR; demais → DeepSeek | — |
| Testes | ✅ | `tests/Unit/Services/Messaging/LeadQualificationServiceTest.php:37–50` | — |

### 2.4 — Áudio (STT)

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Coluna `transcription` em `whatsapp_messages` | ✅ | `database/migrations/2026_06_17_120006_add_transcription_to_whatsapp_messages_table.php:20` | — |
| Download de áudio | ✅ | `app/Jobs/ProcessEvolutionWebhook.php:82–93` (`MetaCloudApiService::downloadMedia`) | — |
| STT (Gemini multimodal) | ✅ | `app/Services/Messaging/AudioTranscriptionService.php:46, 78, 101, 131` | — |
| Roteamento por risco LGPD | 🟡 | Sempre Gemini; sem `chooseProvider(categoria)` análogo ao da qualificação | Espelhar o roteamento do `LeadQualificationService` |
| Bot **responde** ao áudio (critério de aceite) | ❌ | Só transcrição é persistida; resposta segue fluxo normal de chat | Roadmap pede "transcrita e respondida corretamente" — sem fluxo TTS/auto-reply |
| Testes | ✅ | `tests/Unit/Services/Messaging/AudioTranscriptionServiceTest.php` | — |

### 2.5 — Formulário conversacional

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Migrations forms/questions/sessions/answers | ✅ | `2026_06_17_120007..120010` (todas com FK + índices) | — |
| Models | ✅ | `app/Models/WhatsappForm.php`, `WhatsappFormQuestion.php`, `WhatsappFormSession.php`, `WhatsappFormAnswer.php` | — |
| FSM (`WhatsappFormEngine`) | ✅ | `app/Services/Messaging/WhatsappFormEngine.php:51, 89, 108, 174, 338` | — |
| Webhook integrado à FSM | ✅ | `ProcessEvolutionWebhook.php:209–262` | — |
| Botões/listas interativas Evolution | 🟡 | `WhatsappFormEngine.php:191, 289` (fallback texto numerado; nativo "postponed") | Implementar interactive messages do Evolution |
| Iniciar form pelo chat | ✅ | `WhatsappController::startForm` (via `form_id`) | — |
| Testes | ✅ | `tests/Unit/Services/Messaging/WhatsappFormEngineTest.php`; `tests/Feature/WhatsApp/LeadCaptureFromFormTest.php` | — |

**LGPD:** OK — qualificação roteia eleitoral → Gemini BR (risco); ⚠️ áudio não roteia por categoria, embora Gemini já seja BR-friendly.

---

## Fase 5 — CRM de leads + Hub de Planejamento

### 5.2 — CRM de leads

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Migrations `leads`, `lead_consents`, `lead_timeline_items` | ✅ | `2026_06_18_120001:24–55`; `120002:14–31`; `120003:15–36` | — |
| Models com tenant scope + status constants | ✅ | `app/Models/Lead.php:11–73`; `LeadConsent.php:10–39`; `LeadTimelineItem.php:10–44` | — |
| `LeadService` (normalização, idempotência, consent, timeline) | ✅ | `app/Services/LeadService.php:31–260` | — |
| Captura via formulário WhatsApp (Fase 4 → Lead) | ✅ | `app/Services/Messaging/LeadCaptureFromForm.php:36–224` (origin `whatsapp_form:{formId}`, idempotente via `session.lead_id`) | — |
| Formulários públicos com opt-in **explícito por página** | ❌ | Não encontrado — não há landing page pública com checkbox de consentimento que bloqueie submissão sem aceite | Implementar página pública de cadastro com opt-in obrigatório |
| Double opt-in via WhatsApp | 🟡 | Schema suporta (`double_opt_in_at`, status `confirmed`); `LeadService::recordConsent(TYPE_DOUBLE_OPT_IN)` existe; falta fluxo de envio/validação | Implementar Job que dispara confirmação no WhatsApp e endpoint que confirma |
| Dashboard: total, cidade, segmentação | 🟡 | KPI `leads_total` existe em `DashboardController.php:366–374` mas resolve para `Prospect::count()` **(BUG — não Lead)**; sem gráficos por cidade/tag | Corrigir source de `leads_total` para `Lead::count()` (escopado por tenant); adicionar widgets por cidade e segmentação |
| Evolução do lead (timeline a partir do WhatsApp + sugestões Bruce) | 🟡 | `LeadTimelineItem` suporta tipos `whatsapp_message`, `ai_suggestion`, `next_action`; apenas `form_completed` é populado automaticamente | Wire-up: persistir `whatsapp_message` na timeline quando há lead vinculado; ligar `LeadQualificationService` a `ai_suggestion`/`next_action` |
| Testes | ✅ | `tests/Feature/WhatsApp/LeadCaptureFromFormTest.php:1–344` (18 cenários); `tests/Unit/Services/LeadServiceTest.php:14–109` | — |

### 5.1 — Hub de Planejamento Estratégico

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Mapear implementação atual e integrar a Perfil Op. + Kanban | ❌ | Nenhum controller/model/view "Hub" ou "Planejamento" do gestor — feature ainda não existe (o próprio roadmap reconhece) | Decidir escopo e implementar do zero |

**LGPD (alta sensibilidade — esta fase):**
- `lead_consents` é log imutável com origin/IP/UA — bom.
- ⚠️ Sem encryption at-rest visível para PII dos leads.
- ⚠️ Sem rejeição explícita de cadastro sem opt-in (form completion = opt-in implícito) — viola Art. 7º/8º em contexto eleitoral.

---

## Fase 6 — Trilha paralela (Cloud API + tema/AB de e-mail)

### 4.3 — WhatsApp Cloud API (Meta)

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| `MetaCloudApiService` (send text/template, webhook HMAC, download media) | ✅ | `app/Services/Messaging/MetaCloudApiService.php:1–329` | — |
| Camada de abstração por tenant (Evolution vs Cloud API) | ❌ | Não há interface unificada; `MetaCloudApiService` é isolado | Criar `ChannelInterface` e router por tenant |
| Onboarding Meta Business (verificação, registro, billing) | ❌ | — | Implementar fluxo de onboarding |
| Templates aprovados pela Meta (gerenciamento) | 🟡 | `getTemplates()` existe; sem CRUD/sincronização | Persistir templates + estado de aprovação |
| Janela 24h | ❌ | Não há lógica explícita de janela | Validar/enforce no envio |
| Testes | ❌ | — | Cobrir signature + send |

### 3.1 — Tema de e-mail por IA + cota 2/mês

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Geração HTML por IA (DeepSeek) | ❌ | `DeepSeekService.php:1–76` só tem `chat()` genérico; sem `generateEmailTheme()` | Implementar prompt + persistência |
| Cota "2 grátis/mês" | ❌ | `EmailQuotaService` é diária (50/dia) — escopo diferente | Tabela/coluna mensal separada para temas gerados |
| Builder visual (MJML ou blocos) | ❌ | — | Decidir tecnologia e implementar |
| Testes | ❌ | — | — |

### 3.2 — A/B de campanha + tracking Brevo

| Item | Status | Evidência | Pendências |
|---|---|---|---|
| Schema com variant_a/variant_b/split | ❌ | `EmailCampaign.php:1–73` e migration `create_email_campaigns_table` não têm colunas de variant | Migration + colunas |
| Split list + envio das variantes | ❌ | — | Job de split |
| Webhook Brevo (open/click) | ❌ | Sem rota `/webhook/brevo` em `routes/partials/public.php` nem `api.php` | Rota + handler + persistência de eventos |
| Auto-envio do vencedor | ❌ | — | Scheduler + lógica de winner |
| Testes | ❌ | — | — |

---

## Auditoria LGPD/Eleitoral transversal (regras §3 do roadmap)

| Regra | Avaliação | Notas |
|---|---|---|
| 1. Persistir antes de transmitir | ✅ | Confirmado em `ProcessEvolutionWebhook.php:138–189`; nenhum broadcast precede o `create()` |
| 2. Isolamento multi-tenant | ✅ na maioria | Trait `BelongsToTenant` em modelos auditados; ⚠️ `MetaCloudApiService` sem isolamento por tenant ainda |
| 3. Opt-in explícito e registrado | 🟡 | OK em `lead_consents` com IP/UA/origin; ❌ falta opt-in **explícito por página** em cadastro público; ❌ double opt-in inacabado |
| 4. Dado sensível / contexto eleitoral | ✅ | PII bloqueada em `instrucao` (Fase 1); `location`/`contact` persistidos como marcadores sem PII (Fase 0); contexto eleitoral injeta Lei 9.504/97/TSE no prompt Bruce |
| 5. Roteamento de IA por risco | 🟡 | OK em `LeadQualificationService`; ❌ em `AudioTranscriptionService` (sempre Gemini hardcoded — aceitável porque Gemini multimodal, mas não condicional) |
| 6. Cotas no Super Admin | ✅ | `daily_email_quota` editável em `AdminController.php:325–348` com audit log |
| 7. Termos versionados | ✅ | Anti-ban grava versão + hash + IP + UA + timestamp |
| 8. Convenções de código | ✅ | Lógica em Services; Jobs com retry; migrations idempotentes (idempotência confirmada em `daily_email_quota`); testes presentes em quase todos os itens FEITO |

**PII em log:** auditores não encontraram CPF/telefone em logs — controles preventivos no `UpdatePerfilOperacionalRequest`. Recomendado teste automático que falhe se PII vazar em logs.

---

## Lista priorizada do que falta

### P0 — bloqueadores LGPD/produção
1. **Cadastro público sem opt-in explícito** (Fase 5.2). Implementar landing/Livewire pública que **rejeita** submissão sem checkbox de consentimento por página. Risco LGPD Art. 7º/8º em contexto eleitoral.
2. **Double opt-in via WhatsApp** (Fase 5.2). Job que envia confirmação + endpoint que registra `TYPE_DOUBLE_OPT_IN`. Sem isso, "confirmed" nunca acontece.
3. **Bug do KPI `leads_total`** (Fase 5.2 / Fase 1). `DashboardController.php:366–374` resolve para `Prospect::count()` em vez de `Lead::count()` escopado por tenant. **Métrica errada já em produção.**

### P1 — features incompletas mas anunciadas como prontas
4. **Bot responder áudio** (Fase 4 / 2.4). Hoje só transcreve; o critério de aceite pede resposta. Decidir se entra TTS ou se basta resposta-texto via fluxo do Bruce.
5. **Real-time no Kanban** (Fase 3). Adicionar evento `KanbanCardMoved` + Echo. Sem isso, "reflete em tempo real" do critério não vale para multi-usuário.
6. **Dashboards de cidade/segmentação dos leads** (Fase 5.2). Widgets faltando.
7. **Timeline puxando histórico WhatsApp + sugestões Bruce** (Fase 5.2). Wire-up entre `LeadQualificationService` → `LeadTimelineItem(TYPE_AI_SUGGESTION/NEXT_ACTION)` e entre webhook → `TYPE_WHATSAPP_MESSAGE`.

### P2 — testes/qualidade
8. **Feature test Fase 0**: webhook → `assertDatabaseHas('whatsapp_messages', ['content' => ...])` → reload thread.
9. **Feature test Fase 2 4.2**: `WhatsappInstanceController@store` sem aceite → 423.
10. **Feature test Fase 3 Kanban**: endpoint `moveCard` persiste ordem.
11. **Testes para `MetaCloudApiService`** (signature, send).
12. **Roteamento LGPD no `AudioTranscriptionService`** (espelhar `LeadQualificationService.chooseProvider`).

### P3 — épicos da Fase 6 (trilha paralela)
13. Camada de abstração de canal por tenant (Evolution × Cloud API).
14. Onboarding Meta Business + gestão de templates + janela 24h.
15. Geração de tema de e-mail por IA + cota mensal "2 grátis".
16. A/B de campanha + webhook Brevo + auto-envio do vencedor.

### P4 — feature ainda não escopada
17. Hub de Planejamento Estratégico (Fase 5.1) — mapear código atual e propor incrementos. Não existe nada hoje.

---

## Apêndice — divergências de arquitetura vs roadmap

- **Kanban UI**: roadmap pede Livewire 3, implementação é Blade + vanilla JS com SortableJS. Funcional, mas diverge do stack declarado.
- **Enforcement da cota de e-mail**: roadmap pede "no Job de envio"; implementação está no controller `ManagerEmailCampaignController`. Funciona, mas pode escapar de outras origens de envio.
- **Áudio STT**: roadmap sugere Gemini multimodal **ou** Whisper/Groq; implementação é Gemini-only. Aceitável, mas não há fallback nem roteamento por risco.
