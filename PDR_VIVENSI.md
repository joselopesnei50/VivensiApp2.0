# PDR — Plano de Desenvolvimento e Roadmap
## Vivensi App 2.0
**Data:** Abril 2026 | **Versão:** 1.0 | **Status:** Beta

---

## 1. VISÃO GERAL DO PRODUTO

**Vivensi** é uma plataforma SaaS multi-tenant voltada para gestão empresarial, ONGs e o terceiro setor. Combina comunicação omnichannel (WhatsApp), gestão financeira, projetos, CRM, rifas e automação com IA em um único painel.

**Stack Técnica**
| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 9 + PHP 8.1 |
| Banco de dados | MySQL (single-database multi-tenant) |
| Frontend | Bootstrap 5 + AlpineJS + ApexCharts |
| Real-time | Soketi / Pusher |
| Filas | Redis + Laravel Horizon |
| IA | Google Gemini 1.5 Pro + DeepSeek (fallback) |
| Infra | VPS Linux + XAMPP local |

**Arquitetura:** Multi-tenant via `BelongsToTenant` trait — todos os 50+ models são isolados por `tenant_id`. 117 migrations, 173 controllers/models/services.

---

## 2. MÓDULOS IMPLEMENTADOS

### 2.1 WhatsApp Omnichannel ✅
- Chat em tempo real com contatos (Evolution API v2)
- Histórico de conversas por contato
- Cérebro do Bruce — treinamento de IA por tenant
- Disparo em massa com suporte a grupos e relatório de campanhas
- Automações (respostas automáticas por gatilho)
- Blacklist / opt-out de contatos
- Múltiplas instâncias por tenant
- Webhook de recebimento de mensagens

### 2.2 Financeiro ✅
- Lançamentos (receitas/despesas) com categorias
- Dashboard com gráficos ApexCharts
- Reconciliação bancária (importação OFX)
- Orçamentos e metas financeiras
- CFO Virtual "Bruce AI" (análise com Gemini + DeepSeek)
- Relatórios de exportação
- Smart Analysis (tendências)

### 2.3 Gestão de Projetos ✅
- CRUD de projetos com equipe e membros
- Kanban de tarefas (todo/doing/done)
- Timeline e histórico de saúde do projeto
- Calendário de compromissos pessoal
- Geocodificação de projetos
- Health Score automático

### 2.4 CRM & Prospecção ✅ (parcial)
- Pipeline de prospects com status
- Análise de prospect via Gemini AI
- Busca de leads (Serper API)
- CRM de patrocínios (kanban premium)
- Marketing Strategy AI

### 2.5 ONGs / Terceiro Setor ✅
- Gestão de voluntários e certificados
- Gestão de doadores (portal público)
- Editais (grants) com Bruce AI Inspector
- Beneficiários e familiares
- Portal de transparência público
- Parcerias e convênios
- Diretoria
- Gestão de doações

### 2.6 Rifas Online ✅
- Criação e gestão de rifas
- Página pública com seleção de números
- PIX estático (BRCode/EMV QRCPS)
- Upload de comprovante pelo comprador
- E-mail de confirmação ao comprador
- Alerta ao tenant por nova reserva
- Rastreamento de visitas

### 2.7 Recursos Humanos ✅
- Cadastro de funcionários e voluntários
- Controle de ponto e assiduidade
- Edição e exclusão de registros

### 2.8 SaaS & Billing ✅
- Planos de assinatura (Asaas V3)
- Checkout e webhooks de pagamento
- Gestão de tenants pelo super_admin
- Configurações de branding por tenant (logo, cores, PIX)

### 2.9 Academia / LMS ✅
- Cursos, módulos e lições
- Certificados PDF
- Progresso do aluno

### 2.10 Redes Sociais ✅
- Agendamento de posts (calendário)
- Geração de legenda por IA (Gemini)
- Edição e exclusão de posts

### 2.11 Landing Pages ✅
- Gerador dinâmico de páginas
- Captura de leads
- Métricas de acesso

### 2.12 Outros ✅
- Chat interno entre usuários do tenant
- Notificações em tempo real
- Auditoria de ações (AuditLog)
- Blog público
- Busca global
- Inventário de ativos
- Contratos
- Reserva de reuniões (MeetingBooking)

---

## 3. INTEGRAÇÕES EXTERNAS

| Serviço | Função | Status |
|---------|--------|--------|
| Evolution API v2 | WhatsApp omnichannel | ✅ Operacional |
| Google Gemini 1.5 Pro | IA principal (análise, texto) | ✅ Operacional |
| DeepSeek | IA fallback / chat Bruce | ✅ Operacional |
| Asaas V3 | Cobranças SaaS + PIX | ✅ Operacional |
| OpenPix | PIX para rifas | ✅ Operacional |
| AbacatePay | Pagamentos alternativos | ✅ Integrado |
| PagSeguro | Pagamentos | ✅ Operacional |
| Brevo | E-mail transacional | ✅ Operacional |
| Soketi / Pusher | Real-time WebSocket | ✅ Operacional |
| Sentry | Rastreamento de erros | ✅ Operacional |
| Meta Cloud API | WhatsApp Business oficial | 🔄 70% — em desenvolvimento |
| Serper API | Busca de leads | ✅ Operacional |
| Unsplash | Imagens para posts | ✅ Operacional |
| Google Geocoding | Localização de projetos | ✅ Operacional |

---

## 4. PERFIS DE USUÁRIO (ROLES)

| Role | Acesso |
|------|--------|
| `super_admin` | Acesso total — painel admin, todos os tenants |
| `manager` | Gestão completa do tenant (equipe, projetos, financeiro) |
| `ngo` | Igual ao manager com módulos ONG ativos |
| `employee` | Acesso limitado (tarefas próprias, chat) |

---

## 5. PENDÊNCIAS CRÍTICAS

### 🔴 Alta Prioridade
| # | Item | Descrição |
|---|------|-----------|
| 1 | **Meta Cloud API** | Finalizar webhook validation, templates HSM, billing tracking — bloqueio para aprovação Meta Business |
| 2 | **WhatsApp delivery status** | Webhook de atualização de status (entregue/lido) não processa atualizações nas mensagens |
| 3 | **Queue worker no VPS** | Worker precisa reiniciar após deploy — configurar Supervisor para manter ativo permanentemente |

### 🟡 Média Prioridade
| # | Item | Descrição |
|---|------|-----------|
| 4 | **Prospecção AI** | Módulo 30% implementado — pipeline visual e análise Gemini funcionam mas UI incompleta |
| 5 | **Relatório de delivery** | Broadcast tem histórico de envio mas não mostra status real (entregue/lido) por falta de webhook |
| 6 | **Cobertura de testes** | PestPHP configurado mas cobertura < 10% — risco em refactors |
| 7 | **Smart Analysis** | Gráficos de tendência implementados mas exportação avançada ausente |

### 🟢 Baixa Prioridade
| # | Item | Descrição |
|---|------|-----------|
| 8 | **Módulo Banners** | Removido por instabilidade — pode ser reintroduzido com Fabric.js estável |
| 9 | **Social/Accounts** | Integração Meta (Facebook/Instagram OAuth) pausada aguardando aprovação do app |
| 10 | **Exportação financeira avançada** | PDF/Excel de relatórios financeiros complexos |

---

## 6. ROADMAP

### Fase 1 — Estabilização (Maio 2026)
- [ ] Finalizar Meta Cloud API (webhook + templates)
- [ ] Configurar Supervisor no VPS para queue worker
- [ ] Webhook de delivery status WhatsApp
- [ ] Corrigir migration `social_accounts` conflitante no VPS

### Fase 2 — Funcionalidades (Junho 2026)
- [ ] Completar UI de Prospecção (pipeline drag-and-drop)
- [ ] Relatório de entrega real no broadcast (lido/entregue)
- [ ] Exportação PDF do financeiro
- [ ] Aumentar cobertura de testes para 40%+

### Fase 3 — Crescimento (Q3 2026)
- [ ] App mobile (React Native ou Flutter)
- [ ] API pública para integradores
- [ ] Marketplace de automações WhatsApp
- [ ] Integração com Google Calendar
- [ ] Reintroduzir módulo de banners (versão estável)

---

## 7. ARQUIVOS-CHAVE

| Arquivo | Função |
|---------|--------|
| `app/Services/EvolutionApiService.php` | Toda a comunicação com Evolution API |
| `app/Services/GeminiService.php` | IA Gemini (análise, texto, legenda) |
| `app/Services/GeminiAnalysisService.php` | Análise de prospects por IA |
| `app/Traits/BelongsToTenant.php` | Multi-tenancy — global scope por tenant_id |
| `app/Providers/AuthServiceProvider.php` | Gates e Policies |
| `routes/web.php` | Todas as rotas web (auth + público) |
| `routes/api.php` | API para webhooks e mobile |
| `app/Http/Controllers/WhatsappController.php` | Hub WhatsApp (chat, instâncias, config) |
| `app/Http/Controllers/Admin/WhatsappBroadcastController.php` | Disparo em massa |
| `app/Http/Controllers/TransactionController.php` | Financeiro |
| `app/Http/Controllers/PublicRaffleController.php` | Rifas públicas |

---

## 8. MÉTRICAS DO PROJETO

| Métrica | Valor |
|---------|-------|
| Migrations | 117 |
| Controllers | 60+ |
| Models | 50+ |
| Services | 18 |
| Módulos funcionais | 12 |
| Integrações externas | 14 |
| Roles de usuário | 4 |

---

*Documento gerado automaticamente — Vivensi App 2.0 — Abril 2026*
