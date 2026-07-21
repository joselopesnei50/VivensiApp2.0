# Inventário — Motor de Conformidade Contínua (Vivensi)
> Gerado em: 2026-07-21 | Fase 1 do prompt-analise-motor-conformidade.md

---

## A. ESTRATÉGIA MULTI-TENANT

### Modelo implementado: Coluna discriminadora (tenant_id) + Global Scope + Trait

- **Pacote externo:** nenhum (stancl/tenancy não usado). Implementação manual.
- **Trait central:** `app/Traits/BelongsToTenant.php`
  - `bootBelongsToTenant()`: adiciona `addGlobalScope('tenant', ...)` em SELECT
  - Console: scope ignorado + log em debug (linha 34-41)
  - Auth: super_admin sem filtro; usuário comum filtra por `tenant_id` (linha 46-47)
  - Unauthenticated web: `WHERE 1 = 0` (fail-closed, linha 55)
  - INSERT: hook `creating` copia `tenant_id` do usuário logado (linhas 59-67)
  - Bypass auditado: `forTenantUnscoped(int $tenantId)` (linhas 23-28)
- **Tenant model:** `app/Models/Tenant.php` — campos: id, name, document (CNPJ), subdomain, type (ngo/business/personal/common), business_type, subscription_status, plan_id, brand_*, pix_key (encrypted)
- **~159 models** com o trait (confirmado por grep)

### Onde o escopo NÃO é aplicado — lacunas críticas

| Contexto | Motivo | Risco |
|---|---|---|
| Console/Jobs (todos) | Scope desabilitado intencionalmente | ALTO — cada job deve filtrar por tenant_id explicitamente |
| `ProcessWhatsappWebhook` (linha 46) | `withoutGlobalScopes()` + check explícito | OK — padrão correto |
| `ProcessAbacatePayWebhook` | `withoutGlobalScopes()` + check explícito | OK — padrão correto |
| `ExportUserLgpdDataJob` | `withoutGlobalScope('tenant')` + check explícito | OK |
| `User` model | **Sem BelongsToTenant** — tem tenant_id mas sem global scope | MÉDIO |
| `FinancialCategory` | Sem BelongsToTenant confirmado | VERIFICAR |
| Rotas públicas `/transparencia/{slug}`, `/portal-doador/{token}`, `/r/{token}` | Sem auth — acesso por token/slug | OK — blind index HMAC-SHA256 |

**Padrão de bypass:** Todos os jobs usam `withoutGlobalScopes()` + verificação explícita de `tenant_id`. Sem IDOR detectado em jobs auditados.

---

## B. MODELO DE DADOS POR DOMÍNIO

### 1. Organização / Tenant

| Campo | Status |
|---|---|
| name, document (CNPJ), type, subscription_status | ✅ Existe |
| Certificações (CEBAS, OSCIP, OS) | ❌ **NÃO EXISTE** |
| Validade CEBAS, número inscrição | ❌ **NÃO EXISTE** |
| Área de atuação preponderante (AS/Saúde/Educação) | ❌ **NÃO EXISTE** |
| Porte da organização (receita bruta anual) | ❌ **NÃO EXISTE** |
| Inscrição SUAS / CNEAS | ❌ **NÃO EXISTE** |
| Natureza jurídica detalhada | ❌ **NÃO EXISTE** |

**Model:** `app/Models/Tenant.php` | **Migration:** `2026_01_01_000000_create_tenants_table.php`

### 2. Beneficiários

| Campo | Status |
|---|---|
| name, cpf (encrypted+bidx), nis (encrypted+bidx), birth_date, gender, race_color, education | ✅ Existe |
| phone, address, address_* (cidade, estado, CEP) | ✅ Existe (plaintext) |
| status (active/inactive/graduated) | ✅ Existe |
| Família / FamilyMember (kinship, birth_date) | ✅ Existe |
| Renda familiar mensal | ❌ **NÃO EXISTE** |
| Índice de vulnerabilidade socioassistencial | ❌ **NÃO EXISTE** |
| Data cadastro CadÚnico / NIS familiar | ❌ **NÃO EXISTE** |
| Motivo de saída do programa | ❌ **NÃO EXISTE** |

**Model:** `app/Models/Beneficiary.php` (linhas 1-113) | Criptografia: CPF e NIS (AES-256-CBC + blind index)

### 3. Atendimentos

| Campo | Status |
|---|---|
| beneficiary_id, user_id, date, type (string), description | ✅ Existe |
| Atendimento gratuito vs. pago | ❌ **NÃO EXISTE** |
| Encaminhamento (para onde, status, retorno) | ❌ **NÃO EXISTE** |
| Integração com indicadores SUAS (RMA) | ❌ **NÃO EXISTE** |

**Model:** `app/Models/Attendance.php` (linhas 1-31)

### 4. Atividades / Presença

| Elemento | Status |
|---|---|
| ProjectClass (turmas): name, weekdays, start/end_date, max_students, status | ✅ Existe |
| ClassSession (sessões/chamadas): session_date, start/end_time, status | ✅ Existe |
| ClassAttendance (presença): status (presente/ausente/justificado), justification | ✅ Existe |
| ProjectPerson (matrícula em projeto) | ✅ Existe |
| Indicador de resultado por beneficiário | ❌ **NÃO EXISTE** |
| Data início / saída do participante por atividade | ❌ Parcial (via ProjectPerson — verificar campos) |

### 5. Projetos e Convênios

| Campo | Status |
|---|---|
| name, description, budget, start_date, end_date, status | ✅ Existe |
| general_objective, specific_objectives, target_audience | ✅ Existe |
| ProjectStage (etapas com planned_value, status, target_date) | ✅ Existe |
| ProjectGoal (metas com status) | ✅ Existe |
| NgoGrant (title, agency, contract_number, value, start_date, deadline) | ✅ Existe |
| Plano de trabalho versionado (PDF) | ❌ **NÃO EXISTE** |
| Código SICONV / SIGCON do órgão concedente | ❌ **NÃO EXISTE** |
| Fonte de recurso por transação | ❌ Parcial (project_id em Transaction, mas sem campo fonte_recurso) |
| Workflow de aprovação de etapas | ❌ **NÃO EXISTE** |

**Model:** `app/Models/Project.php` (linhas 1-189) + `NgoGrant.php`

### 6. Financeiro

| Campo | Status |
|---|---|
| type (income/expense), amount, date, status, description | ✅ Existe |
| project_id, stage_id (vínculo com projeto/etapa) | ✅ Existe |
| nfse_numero, nfse_url_pdf, nfse_emitida_em | ✅ Existe |
| approval_status | ✅ Existe |
| Centro de custo (modelo separado) | ❌ **NÃO EXISTE** |
| Fonte de recurso (campo discriminado: CEBAS/SUAS/Próprio/Convênio) | ❌ **NÃO EXISTE** |
| Elegibilidade CEBAS (despesa qualifica como gratuidade?) | ❌ **NÃO EXISTE** |
| Comprovante de despesa (attachment vinculado) | ✅ Parcial (attachment_path em Transaction) |

**Model:** `app/Models/Transaction.php` (linhas 1-160) — Criptografia: public_receipt_token (AES-256-CBC + blind index)

### 7. Doações e Recibos

| Campo | Status |
|---|---|
| NgoDonor: name, email, phone, document (encrypted+bidx), type, portal_token | ✅ Existe |
| Recibo: via Transaction + public_receipt_token (auto-gerado) + receipt_auth_code | ✅ Existe |
| PDF de recibo de doação | ✅ Existe (resources/views/pdf/donor/ir_pdf.blade.php) |
| Portal do doador (histórico, IR) | ✅ Existe (rota /portal-doador/{token}) |
| Recorrência da doação (mensal/anual/única) | ❌ **NÃO EXISTE** |
| Data de emissão do recibo | ❌ Parcial (infer via transaction.date) |
| Numeração sequencial de recibo (série/número) | ❌ Parcial (receipt_auth_code, 16 hex — não é sequencial) |

### 8. RH e Voluntários

| Campo | Status |
|---|---|
| Employee: name, position, salary (encrypted), work_hours_weekly, contract_type, hired_at | ✅ Existe |
| Volunteer: name, email, phone, skills, hours_logged, availability | ✅ Existe |
| VolunteerHourLog (registro de horas) | ✅ Existe |
| VolunteerCertificate | ✅ Existe (sem BelongsToTenant — verificar) |
| Horas voluntárias qualificadas CEBAS | ❌ **NÃO EXISTE** |
| Vínculo empregatício detalhado (CLT/PJ/MEI) | ❌ Parcial (contract_type existe mas sem enum) |
| Integração com CAGED/eSocial | ❌ **NÃO EXISTE** |

### 9. Documentos

| Campo | Status |
|---|---|
| Attachment: path (hidden), mime_type, size_bytes, uploaded_by, attachable (polimórfico) | ✅ Existe |
| SoftDeletes | ✅ Existe |
| Armazenamento privado por tenant | ✅ Existe (storage/app/private/tenants/{id}/...) |
| Data de validade / vencimento | ❌ **NÃO EXISTE** |
| Versionamento (v1, v2, histórico) | ❌ **NÃO EXISTE** |
| Tipo de documento (estatuto, ata, certidão...) | ❌ **NÃO EXISTE** |
| Assinatura digital | ❌ **NÃO EXISTE** |
| Vinculação a processo de conformidade | ❌ **NÃO EXISTE** |

**Model:** `app/Models/Attachment.php` — Migration: `2026_07_17_000001_create_attachments_table.php`

### 10. Transparência

| Campo | Status |
|---|---|
| TransparencyPortal: slug, title, cnpj, mission, vision, values, sic_email, is_published | ✅ Existe |
| SicRequest: protocol, status, response_text, response_at | ✅ Existe |
| PDF de relatório público | ✅ Existe (resources/views/pdf/transparency/report_pdf.blade.php) |
| CSV de dados públicos | ✅ Existe (rota /transparencia/{slug}/dados.csv) |
| Publicação automática de SIC no portal | ❌ **NÃO EXISTE** |
| Auditoria de downloads | ❌ **NÃO EXISTE** |
| Integração com plataformas governamentais (CGU, e-SIC) | ❌ **NÃO EXISTE** |

---

## C. INFRAESTRUTURA REUTILIZÁVEL

### PDF
- **Biblioteca:** `barryvdh/laravel-dompdf` ^2.2
- **12 templates** em `resources/views/pdf/`
- **Geração assíncrona:** `app/Jobs/GeneratePdfJob.php`
- **Lacuna:** Sem templates CEBAS, SUAS, MROSC

### Uploads
- **Modelo:** `app/Models/Attachment.php` — polimórfico, disco privado por tenant
- **Lacuna crítica:** Sem `expires_at`, sem versionamento, sem tipo de documento

### Notificações
- **Email:** Via `BrevoService` (transacional) + jobs de campanha
- **WhatsApp:** 4+ jobs específicos (áudio, opt-in, prospecção, campanha)
- **Interno:** InternalMessage / InternalChat
- **SMS:** Não encontrado

### BruceIA
- **Serviço:** `app/Services/BruceAiService.php` (855 linhas) + `DeepSeekService.php`
- **Memória:** Redis, TTL 1h, max 20 mensagens por contexto
- **Contexto injetado:** `TenantContextService` — só contagens e datas, sem valores brutos
- **Controle de dados sensíveis:** Prompt nunca recebe valores financeiros em reais
- **Lacuna:** Sem custo por tenant, sem rate limit por tenant, sem rotação de API key

### Scheduler (23 agendamentos em `app/Console/Kernel.php`)
- Críticos multi-tenant: `abacatepay:reconcile` (5min), `lgpd:purge-scheduled-deletions` (03:00), `whatsapp:send-scheduled` (1min), `broadcast:process-scheduled` (1min)
- Sem agendamento de conformidade hoje

### Motor de Regras
- **Não existe** motor genérico. Lógica dispersa em controllers e models.
- Automações WhatsApp em `WhatsappAutomation` (gatilhos configuráveis)
- `AntiBanManager` é o componente mais próximo de um motor de regras

---

## D. QUALIDADE E RISCO

### Criptografia at-rest

| Modelo | Campos criptografados | Campos PII plaintext |
|---|---|---|
| Beneficiary | cpf, nis | address, phone, birth_date, FamilyMember.name/birth_date |
| User | phone, 2FA secrets | name, email |
| NgoDonor | document, portal_token | name, email, phone, address |
| Transaction | public_receipt_token | — |
| Employee | salary, bonus | name, position |
| Tenant | pix_key, pix_key_type | — |

### Débito técnico crítico para este projeto

| Item | Arquivo | Linhas | Impacto |
|---|---|---|---|
| BeneficiaryController monolítico | app/Http/Controllers/BeneficiaryController.php | 926 | Dificulta inserção de lógica de conformidade |
| AdminController monolítico | app/Http/Controllers/AdminController.php | 24K+ | — |
| LandingPageController | app/Http/Controllers/LandingPageController.php | 44K+ | — |
| User sem BelongsToTenant | app/Models/User.php | — | Risco de query cross-tenant |
| FinancialCategory sem BelongsToTenant | app/Models/FinancialCategory.php | — | Verificar isolamento |
| Sem motor de regras genérico | app/Services/ | — | Conformidade exige criar do zero |

### Cobertura de testes
- ~40% dos domínios cobertos
- Beneficiários, Transações, LGPD, Attachments têm testes Feature
- **Zero testes de conformidade** (CEBAS/MROSC/SUAS)

---

## E. LACUNAS CONSOLIDADAS (por severidade)

| # | Lacuna | Severidade | Ação necessária |
|---|---|---|---|
| L1 | Tenant sem campos de certificação (CEBAS, SUAS, MROSC) | CRÍTICA | Migration + UI de cadastro |
| L2 | Attachment sem validade, versionamento e tipo | CRÍTICA | Migration + lógica de alerta |
| L3 | Sem motor de regras de conformidade | CRÍTICA | `ComplianceValidationService` novo |
| L4 | Beneficiary sem renda, vulnerabilidade, CadÚnico | ALTA | Migration + campos novos |
| L5 | Attendance sem gratuidade e encaminhamento | ALTA | Migration + campos novos |
| L6 | Transaction sem fonte de recurso e elegibilidade | ALTA | Migration + campo fonte_recurso |
| L7 | Sem dashboard de conformidade | ALTA | Novo módulo UI |
| L8 | Sem templates PDF para CEBAS/SUAS/MROSC | ALTA | Novos blade templates |
| L9 | FamilyMember e NgoDonor com PII plaintext | MÉDIA | Migração de criptografia |
| L10 | User sem BelongsToTenant | MÉDIA | Adicionar trait (cuidado: super_admin) |
| L11 | Sem integração CadÚnico | BAIXA (curto prazo) | API Gov.br (longo prazo) |
| L12 | Sem integração SICONV/SIGCON | BAIXA (curto prazo) | API Transferências Gov (longo prazo) |
