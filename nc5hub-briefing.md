# NC5 HUB — Documento Técnico de Desenvolvimento
**Sistema SaaS Multi-Tenant para Agência de Marketing**
**Stack:** Laravel 11 · MySQL · Tailwind CSS · Alpine.js · Evolution API · Meta Cloud API

---

## 1. VISÃO GERAL DO PRODUTO

O **NC5 HUB** é uma plataforma SaaS white-label desenvolvida para a NC5 MKT HUB, com o objetivo de ser uma **máquina de vendas e operação** para seus clientes (empresas e profissionais de marketing). Cada cliente da NC5 terá acesso a uma conta isolada com todos os módulos disponíveis conforme o plano contratado.

**Público-alvo:** Clientes da agência NC5 MKT HUB — empresas, profissionais liberais e negócios que precisam de ferramentas de marketing, comunicação e gestão comercial em um único lugar.

**Modelo de negócio:**
- A NC5 contrata o sistema e revende acesso aos seus clientes
- Cada cliente tem login próprio, dados isolados e número de WhatsApp próprio
- Contratos de uso entre NC5 e seus clientes são gerados dentro do próprio sistema

---

## 2. ARQUITETURA TÉCNICA

### 2.1 Multi-Tenancy
- **Modelo:** Um banco de dados compartilhado com isolamento por `tenant_id`
- **Trait `BelongsToTenant`:** Aplicado em todos os models — adiciona global scope filtrando automaticamente por `tenant_id` do usuário autenticado
- **Proteção em jobs/queues:** Jobs devem receber IDs primitivos (não models Eloquent) e filtrar manualmente com `->where('tenant_id', $tenantId)` para evitar vazamento entre tenants
- **Middleware `EnsureTenantAccess`:** Verifica se o usuário pertence ao tenant antes de qualquer operação

### 2.2 Roles e Permissões
```
super_admin  → NC5 (acesso total ao sistema, painel admin global)
admin        → Administrador do cliente (acesso total à conta)
manager      → Gestor operacional do cliente
employee     → Colaborador com acesso limitado
```

### 2.3 Stack Tecnológica
- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** Blade + Tailwind CSS + Alpine.js (sem build step — CDN)
- **Banco:** MySQL 8.0
- **Filas:** Redis + Laravel Queue (Supervisor no servidor)
- **Realtime:** Soketi (WebSocket self-hosted) + Laravel Echo
- **Storage:** S3 ou local com Laravel Storage
- **WhatsApp:** Evolution API (self-hosted) + Meta Cloud API (oficial)
- **IA:** Google Gemini API (primary) + DeepSeek (fallback)

### 2.4 Estrutura de Pastas Relevante
```
app/
  Http/Controllers/          → Controllers por módulo
  Models/                    → Eloquent models com BelongsToTenant
  Services/                  → Serviços de negócio (WhatsApp, IA, etc.)
  Jobs/                      → Jobs assíncronos (mensagens, social posts)
  Traits/
    BelongsToTenant.php      → Global scope de isolamento
database/
  migrations/                → Uma migration por feature
resources/views/
  layouts/
    app.blade.php            → Layout autenticado com sidebar
    public.blade.php         → Layout público (landing, contratos)
  [modulo]/                  → Views por módulo
routes/
  web.php                    → Todas as rotas organizadas por grupo de middleware
```

---

## 3. MÓDULOS DO SISTEMA

### 3.1 AUTENTICAÇÃO E ONBOARDING
**Funcionalidades:**
- Registro de novo cliente (cria tenant + usuário admin)
- Login com email/senha
- Recuperação de senha por e-mail
- Perfil de conta (nome, logo, cores da marca)
- Planos de assinatura (Free, Starter, Pro) com controle de limites por feature

**Tabelas:**
```sql
tenants (id, name, slug, logo, brand_color, plan_id, status, created_at)
users (id, tenant_id, name, email, password, role, phone, avatar, created_at)
subscription_plans (id, name, price, features_json, is_active)
```

---

### 3.2 MENSAGERIA WHATSAPP + BOT DE ATENDIMENTO
**Baseado em:** Módulo WhatsApp do Vivensi (Evolution API + Meta Cloud API)

**Funcionalidades:**
- Conexão de número próprio via QR Code (Evolution API) ou Meta Cloud API oficial
- Chat Omnichannel: visualização de todas as conversas em tempo real
- Bot de atendimento com IA (Bruce AI — Gemini/DeepSeek)
  - Treinamento estruturado: identidade, organização, serviços, horários, FAQ
  - **Manual de Atendimento:** campo rico (até 8.000 chars) para scripts, políticas, instruções detalhadas
  - Tom de voz configurável: amigável, formal, empático, animado
- Disparo em massa (broadcast) para listas segmentadas
- Automações: resposta automática por palavra-chave, horário de atendimento
- Templates aprovados pela Meta para envio fora da janela de 24h
- Relatório de entregas e leitura

**Tabelas:**
```sql
whatsapp_configs (id, tenant_id, ai_enabled, ai_provider, ai_training, ai_training_structured JSON,
                  evolution_instance_name, evolution_instance_token, meta_waba_id,
                  meta_phone_number_id, meta_access_token, outbound_enabled,
                  enforce_24h_window, allow_templates_outside_window)
whatsapp_instances (id, tenant_id, instance_name, phone_number, status,
                    daily_limit, messages_sent_today)
whatsapp_chats (id, tenant_id, instance_id, contact_number, contact_name,
                last_message, unread_count, updated_at)
whatsapp_messages (id, tenant_id, chat_id, direction[in/out], content,
                   type, status, sent_at)
whatsapp_broadcasts (id, tenant_id, instance_id, name, message, status,
                     total_sent, total_failed, scheduled_at)
whatsapp_automations (id, tenant_id, trigger_keyword, response_text,
                      is_active, match_type[exact/contains])
```

**Fluxo do Bot:**
1. Mensagem chega via webhook Evolution API (eventos UPPERCASE: MESSAGES_UPSERT)
2. Job `ProcessEvolutionWebhook` identifica o tenant pelo `instanceName`
3. Job `ProcessWhatsappAiResponse` monta o prompt com o treinamento do tenant
4. Resposta da IA enviada via Evolution API

**Segurança crítica:**
- Webhook valida `instanceName` para identificar o tenant (nunca confiar em dados sem validar)
- Jobs armazenam apenas IDs primitivos (não models) para evitar deserialização incorreta
- Todas queries em jobs usam `->where('tenant_id', $tenantId)` explicitamente

---

### 3.3 CONSTRUTOR DE LANDING PAGE
**Funcionalidades:**
- Criador visual de landing pages por blocos (hero, features, depoimentos, CTA, formulário, rodapé)
- Publicação em subdomínio ou domínio próprio do cliente
- Formulário de captura com integração automática ao CRM de leads
- Integração com WhatsApp: CTA abre conversa direta no número do cliente
- Analytics básico: visitas, conversões, taxa de captura
- Templates pré-prontos por segmento (serviços, e-commerce, profissional liberal)

**Tabelas:**
```sql
landing_pages (id, tenant_id, title, slug, status[draft/published],
               custom_domain, visits_count, conversions_count)
lp_sections (id, landing_page_id, type, content_json, order, is_visible)
lp_leads (id, tenant_id, landing_page_id, name, email, phone,
          whatsapp, source, created_at)
```

---

### 3.4 AGENDAMENTO PARA REDES SOCIAIS
**Plataformas:** Instagram, Facebook, YouTube

**Funcionalidades:**
- Calendário visual de publicações (mês/semana/dia)
- Upload de imagem/vídeo + legenda + hashtags
- Agendamento por data e hora exata
- Publicação imediata ou agendada via job
- Aprovação de conteúdo (fluxo: rascunho → aprovação → publicado)
- Análise básica: curtidas, comentários, alcance (via Graph API)
- Biblioteca de mídias do cliente
- Sugestão de legenda com IA (Bruce AI)

**Integrações:**
- Instagram/Facebook: Meta Graph API (token de página)
- YouTube: YouTube Data API v3

**Tabelas:**
```sql
social_accounts (id, tenant_id, platform[instagram/facebook/youtube],
                 account_name, access_token, page_id, expires_at, status)
social_posts (id, tenant_id, social_account_id, caption, hashtags,
              media_paths JSON, status[draft/scheduled/published/failed],
              scheduled_at, published_at, external_post_id,
              likes_count, comments_count, reach)
social_media_library (id, tenant_id, file_path, file_type, name, size, created_at)
```

**Job de publicação:**
```php
// Scheduled job rodando a cada minuto via cron
// Busca posts com status=scheduled e scheduled_at <= now()
// Publica via API e atualiza status
```

---

### 3.5 CADASTRO DE CLIENTES (CRM)
**Funcionalidades:**
- Cadastro completo de clientes: pessoa física e jurídica
- Campos: nome, CPF/CNPJ, e-mail, telefone/WhatsApp, endereço, observações
- Tags e segmentação por categoria
- Histórico de interações (contratos, recibos, mensagens)
- Importação via CSV
- Integração direta com módulo de WhatsApp (iniciar conversa com 1 clique)
- Integração com módulo de contratos

**Tabelas:**
```sql
contacts (id, tenant_id, type[pf/pj], name, cpf_cnpj, email, phone,
          whatsapp, address, city, state, zip, notes, tags JSON,
          created_by, created_at)
contact_activities (id, tenant_id, contact_id, type, description,
                    related_id, related_type, user_id, created_at)
```

---

### 3.6 CONTRATOS ONLINE
**Funcionalidades:**
- Templates de contrato personalizáveis com variáveis dinâmicas
  - Ex: `{{client_name}}`, `{{service_value}}`, `{{start_date}}`
- Geração de contrato em PDF renderizado via view Blade
- Link público para assinatura digital (desenho ou digitação do nome)
- E-mail automático para o cliente com link de assinatura
- Status: Rascunho → Enviado → Assinado → Cancelado
- Download do contrato assinado (PDF com hash de autenticidade)
- Validade jurídica: log de IP, data/hora e geolocalização da assinatura

**Tabelas:**
```sql
contract_templates (id, tenant_id, name, body_html, variables JSON, is_active)
contracts (id, tenant_id, contact_id, template_id, title, body_html,
           value, status, signed_at, signed_ip, signed_location,
           signature_data TEXT, hash, public_token, expires_at, created_at)
```

**Rota pública:**
```
GET /contrato/{public_token}     → Exibe o contrato para assinatura
POST /contrato/{public_token}    → Salva a assinatura e finaliza
```

---

### 3.7 RECIBOS DE PRESTAÇÃO DE SERVIÇO
**Funcionalidades:**
- Geração de recibo profissional em PDF
- Dados do prestador (tenant) e tomador (cliente do CRM)
- Campos: descrição do serviço, valor, forma de pagamento, data
- Numeração sequencial automática por tenant
- QR Code de validação pública
- Link público para visualização/download pelo cliente
- Histórico financeiro por cliente

**Tabelas:**
```sql
receipts (id, tenant_id, contact_id, number, description,
          value, payment_method, payment_date, notes,
          public_token, status[draft/issued/cancelled], created_at)
```

---

### 3.8 CONTRATO DE USO NC5 → CLIENTE
**Funcionalidades:**
- A NC5 pode enviar o contrato de prestação de serviços para o cliente assinar antes de ativar a conta
- Template padrão da NC5 configurado no super_admin
- Fluxo de onboarding: cadastro → assinar contrato → ativar conta
- Visualização pelo admin de quais clientes assinaram

---

## 4. PAINEL SUPER ADMIN (NC5)

Acesso exclusivo da NC5 para gestão de toda a plataforma:

- **Dashboard global:** total de tenants, receita, usuários ativos, WhatsApp conectados
- **Gestão de tenants:** criar, suspender, ativar contas de clientes
- **Planos e assinaturas:** criar planos, definir limites por módulo
- **Configurações globais:** chaves de API (Gemini, Evolution, Meta), SMTP, WebSocket
- **Logs de sistema:** erros, jobs, webhooks
- **Contratos NC5:** enviar e acompanhar contratos de clientes

---

## 5. SEGURANÇA E ISOLAMENTO

### 5.1 Isolamento de Dados (Multi-Tenancy)
```php
// Trait aplicado em TODOS os models de dados
trait BelongsToTenant {
    protected static function booted() {
        static::addGlobalScope('tenant', function($query) {
            if (auth()->check()) {
                $query->where(static::getModel()->getTable() . '.tenant_id',
                              auth()->user()->tenant_id);
            }
        });
        static::creating(function($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

**Regra crítica em Jobs/Console:**
```php
// NUNCA fazer isso em jobs (bypassa o global scope):
// $records = Model::all();

// SEMPRE fazer assim:
$records = Model::withoutGlobalScope('tenant')
               ->where('tenant_id', $tenantId)
               ->get();
```

### 5.2 Autenticação e Autorização
- Laravel Sanctum ou Auth padrão com sessions
- Middleware de role: `can:access-admin`, `can:access-manager`
- Gates definidos em `AuthServiceProvider`
- Rate limiting nas rotas de API e webhooks
- CSRF em todos os formulários POST

### 5.3 Webhooks
- Verificação de assinatura HMAC nos webhooks da Meta
- Validação de `instanceName` nos webhooks da Evolution API
- Rate limiting: máximo 100 requisições/minuto por IP

### 5.4 Dados Sensíveis
- Tokens de WhatsApp, Meta e redes sociais criptografados em `system_settings`
- Senhas com bcrypt (Laravel padrão)
- Assinaturas de contratos com hash SHA-256
- Logs de acesso a documentos públicos (contratos, recibos)

### 5.5 Uploads e Arquivos
- Validação de tipo MIME no backend (não confiar no Content-Type do cliente)
- Limite de tamanho por plano
- Arquivos privados fora do `public/` — acesso via URL assinada temporária
- Sanitização de nomes de arquivo

---

## 6. ESTRUTURA DE ROTAS

```php
// Públicas
Route::get('/', [PublicController::class, 'home']);
Route::get('/contrato/{token}', [ContractController::class, 'publicShow']);
Route::post('/contrato/{token}', [ContractController::class, 'publicSign']);
Route::get('/recibo/{token}', [ReceiptController::class, 'publicShow']);

// Webhooks
Route::post('/webhooks/evolution', [WebhookController::class, 'evolution']);
Route::post('/webhooks/meta', [WebhookController::class, 'meta']);
Route::get('/webhooks/meta', [WebhookController::class, 'metaVerify']); // verificação

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Autenticado
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // WhatsApp
    Route::prefix('whatsapp')->group(function () {
        Route::get('/chat', [WhatsappController::class, 'chat']);
        Route::get('/settings', [WhatsappController::class, 'settings']);
        Route::post('/settings', [WhatsappController::class, 'saveSettings']);
        Route::get('/broadcast', [BroadcastController::class, 'index']);
        Route::post('/broadcast', [BroadcastController::class, 'send']);
        Route::post('/instances', [InstanceController::class, 'store']);
        Route::delete('/instances/{id}', [InstanceController::class, 'destroy']);
    });

    // Landing Pages
    Route::resource('landing-pages', LandingPageController::class);
    Route::post('landing-pages/{id}/publish', [LandingPageController::class, 'publish']);

    // Social
    Route::prefix('social')->group(function () {
        Route::get('/calendar', [SocialController::class, 'calendar']);
        Route::get('/posts/create', [SocialController::class, 'create']);
        Route::post('/posts', [SocialController::class, 'store']);
        Route::post('/accounts/connect', [SocialController::class, 'connect']);
    });

    // CRM
    Route::resource('contacts', ContactController::class);
    Route::post('contacts/import', [ContactController::class, 'import']);

    // Contratos
    Route::resource('contracts', ContractController::class);
    Route::post('contracts/{id}/send', [ContractController::class, 'send']);

    // Recibos
    Route::resource('receipts', ReceiptController::class);
    Route::get('receipts/{id}/pdf', [ReceiptController::class, 'pdf']);
});

// Super Admin (NC5)
Route::middleware(['auth', 'can:super-admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::resource('/tenants', AdminTenantController::class);
    Route::resource('/plans', AdminPlanController::class);
    Route::get('/settings', [AdminSettingsController::class, 'index']);
    Route::post('/settings', [AdminSettingsController::class, 'save']);
});
```

---

## 7. MIGRATIONS (ordem de criação)

```
001_create_tenants_table
002_create_users_table
003_create_subscription_plans_table
004_create_system_settings_table
005_create_whatsapp_configs_table
006_create_whatsapp_instances_table
007_create_whatsapp_chats_table
008_create_whatsapp_messages_table
009_create_whatsapp_broadcasts_table
010_create_whatsapp_automations_table
011_create_landing_pages_table
012_create_lp_sections_table
013_create_lp_leads_table
014_create_social_accounts_table
015_create_social_posts_table
016_create_social_media_library_table
017_create_contacts_table
018_create_contact_activities_table
019_create_contract_templates_table
020_create_contracts_table
021_create_receipts_table
```

---

## 8. JOBS E FILAS

```
ProcessEvolutionWebhook      → Processa mensagens recebidas pelo WhatsApp
ProcessWhatsappAiResponse    → Gera resposta da IA e envia
SendWhatsappBroadcast        → Envia disparo em massa com delay entre mensagens
PublishSocialPost            → Publica post agendado nas redes sociais
SendContractEmail            → Envia e-mail com link do contrato para assinar
GenerateReceiptPdf           → Gera PDF do recibo assincronamente
```

**Configuração de filas:**
```php
// config/queue.php — usar Redis em produção
// Supervisor no servidor:
// [program:nc5hub-worker]
// command=php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

---

## 9. INTEGRAÇÕES EXTERNAS

| Serviço | Uso | Variável de Ambiente |
|---|---|---|
| Evolution API | WhatsApp (QR Code) | `EVOLUTION_API_URL`, `EVOLUTION_API_KEY` |
| Meta Cloud API | WhatsApp Oficial | configurado por tenant via admin |
| Meta Graph API | Instagram/Facebook posts | configurado por tenant |
| YouTube Data API v3 | YouTube posts | configurado por tenant |
| Google Gemini | IA do bot + sugestões | `GEMINI_API_KEY` |
| DeepSeek | Fallback da IA | `DEEPSEEK_API_KEY` |
| SMTP (Mailgun/SES) | E-mails transacionais | `MAIL_*` |
| Soketi / Pusher | WebSocket realtime | `PUSHER_*` |

---

## 10. LAYOUT E DESIGN

### Sidebar de Navegação (authenticated)
```
Dashboard
─────────────────
📱 WhatsApp
   └ Chat Omnichannel
   └ Configurar Bot
   └ Disparo em Massa
   └ Automações
─────────────────
📅 Redes Sociais
   └ Calendário
   └ Novo Post
   └ Contas Conectadas
   └ Biblioteca de Mídia
─────────────────
🌐 Landing Pages
   └ Minhas LPs
   └ Nova Landing Page
   └ Leads Capturados
─────────────────
👥 Clientes (CRM)
   └ Lista de Clientes
   └ Novo Cliente
─────────────────
📄 Contratos
   └ Meus Contratos
   └ Templates
─────────────────
🧾 Recibos
   └ Meus Recibos
─────────────────
⚙️ Configurações
```

### Diretrizes de Design
- Fundo principal: `#f8fafc` (cinza muito claro)
- Sidebar: `#0f172a` (dark navy)
- Cor primária: `#6366f1` (indigo)
- Fonte: Inter (Google Fonts CDN)
- Cards com `border-radius: 16px`, `box-shadow` suave
- Bootstrap 5.3 + Font Awesome 6.4 via CDN
- Sem build step — todo CSS/JS via CDN ou `<style>` inline nas views

---

## 11. CONFIGURAÇÃO DO AMBIENTE

```env
APP_NAME="NC5 HUB"
APP_URL=https://nc5hub.com.br
APP_KEY=

DB_CONNECTION=mysql
DB_DATABASE=nc5hub
DB_USERNAME=nc5hub_user
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@nc5hub.com.br
MAIL_FROM_NAME="NC5 HUB"

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001

EVOLUTION_API_URL=https://api.evolution.nc5hub.com.br
EVOLUTION_API_KEY=

GEMINI_API_KEY=
DEEPSEEK_API_KEY=
```

---

## 12. ORDEM DE DESENVOLVIMENTO SUGERIDA

**Fase 1 — Base (semana 1-2)**
1. Setup do projeto Laravel 11
2. Migrations e models com BelongsToTenant
3. Auth (login, registro, recuperação de senha)
4. Layout base (sidebar, topbar, responsivo)
5. Dashboard inicial com cards de resumo
6. Painel Super Admin básico

**Fase 2 — Comunicação (semana 3-4)**
7. Módulo WhatsApp (conexão, chat, bot, broadcast)
8. Webhooks Evolution API + Meta
9. Bot IA com treinamento estruturado

**Fase 3 — Vendas (semana 5-6)**
10. Landing Pages (construtor visual)
11. CRM de clientes
12. Contratos online (templates + assinatura)
13. Recibos de prestação de serviço

**Fase 4 — Redes Sociais (semana 7-8)**
14. Agendamento de posts (Instagram, Facebook, YouTube)
15. Calendário editorial
16. Biblioteca de mídias
17. Sugestão de legenda com IA

**Fase 5 — Refinamento (semana 9-10)**
18. Testes de isolamento multi-tenant
19. Rate limiting e segurança
20. Ajustes de UX e performance
21. Deploy e configuração do servidor

---

## 13. INSTRUÇÕES PARA O CLAUDE DESENVOLVER

Ao iniciar o desenvolvimento, siga estas diretrizes:

1. **Sempre criar migrations antes dos models**
2. **Aplicar `BelongsToTenant` em todos os models de dados** (exceto `Tenant`, `User`, `SystemSetting`, `SubscriptionPlan`)
3. **Jobs nunca recebem Eloquent models** — apenas IDs primitivos
4. **Todas as queries em Jobs** usam `withoutGlobalScope('tenant')->where('tenant_id', $tenantId)`
5. **Rotas organizadas por grupo de middleware** — nunca misturar rotas públicas com autenticadas
6. **Controllers slim** — lógica de negócio em Services ou Jobs
7. **Webhooks sempre validam assinatura** antes de processar qualquer dado
8. **Nunca confiar no `tenant_id` enviado pelo cliente** — sempre pegar de `auth()->user()->tenant_id`
9. **PDFs gerados via view Blade** + `barryvdh/laravel-dompdf`
10. **Upload de arquivos** validados por MIME type no backend, armazenados fora do `public/`

---

*Documento gerado em {{ date('d/m/Y') }} para uso interno NC5 MKT HUB.*
*Sistema desenvolvido com base na experiência do Vivensi Laravel SaaS.*
