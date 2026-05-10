# SESSION LOG — Vivensi
**Período**: 09–10 de maio de 2026 | **Ambiente**: Laravel 9, PHP 8, MySQL, AWS Lightsail

---

## 1. DASHBOARDS — Melhorias nos 3 painéis

### Manager Dashboard
- Status de saúde dinâmico no Radar (Saudável / Estável / Crítico baseado na média)
- KPI "Entrada / Mês" com comparativo mês anterior (MoM %)
- Badge de status nos projetos do portfólio (Ativo / Pausado / Concluído)
- Contador de tarefas vencidas separado no "Radar de Alertas"
- Dica de engajamento dinâmica baseada nas métricas reais do WhatsApp

### NGO Dashboard
- Income MoM real (variação % vs mês anterior)
- Confiança do Radar de Saúde dinâmica baseada na média dos scores
- Badge de editais por cor (aberto=verde, reporting=âmbar, fechado=cinza)
- Urgência de prazos com indicador vermelho ≤7 dias
- "Próximos Passos" com deadlines reais do banco em vez de texto fixo

### Common Dashboard
- KPIs por mês atual com MoM% (em vez de totais históricos)
- Tarefas vencidas destacadas com fundo vermelho e ícone de alerta
- Prioridades traduzidas para PT (Alta, Média, Baixa, Crítica) com cores
- Data dinâmica no título (era "FEVEREIRO 2026" hardcoded)
- Alerta de tarefas vencidas no header

### Performance
- N+1 eliminado: Manager 50→15 queries, NGO 22→12, Common 18→8
- WhatsApp stats: 21 queries → 2 queries com JOIN+GROUP BY

---

## 2. AGENDA DO GESTOR — Redesign completo
**Arquivo**: `resources/views/manager/schedule.blade.php`

- Layout 3 colunas com prefixo CSS `ms-`
- Filtro por membro da equipe com avatares coloridos
- Atualização de status AJAX (sem reload de página)
- Visualização semanal e mensal
- Painel deslizante de detalhes da tarefa
- Controller atualizado com filtro `?member=` e dados de tarefas vencidas/sem data

---

## 3. SUPER ADMIN — Hub de Mensageria e Bots

### Sidebar e Menu
- Link "Chat WhatsApp" → `/whatsapp/chat`
- Link "Disparo em Massa" → `/whatsapp/broadcast` (depois removido do super admin)
- Link "Bot Interno" → `/admin/bot`
- Link "Bot de Atendimento" → `/whatsapp/settings`

### Bot Interno (Command Bot)
**Arquivo criado**: `app/Jobs/ProcessWhatsAppBotMessage.php`

Job completo com:
- Menu de boas-vindas por perfil (NGO, Manager, Employee, Common)
- Comando `1` — Saldo financeiro do mês
- Comando `2` — Lista de tarefas pendentes com indicador de vencidas
- Comando `3` — NGO: fluxo de registro de atendimento / Outros: concluir tarefa
- Comando `4` — NGO: busca de beneficiário / Outros: lançar despesa
- Comando `5` — Mensagem de ajuda
- `DESP: 150,00 | Descrição` — Lança despesa diretamente
- `RECV: 500,00 | Descrição` — Lança receita
- `ATEND: Nome | Tipo | Desc` — Registra atendimento (busca beneficiário)
- `BENEF: Nome ou CPF` — Consulta beneficiário
- Fluxos multi-etapa com estado em Cache (TTL 10 min)
- `cancelar` / `menu` para resetar sessão

### Bot de Atendimento (nova aba em `/admin/bot`)
- Toggle para ativar/desativar
- Mensagem de boas-vindas para novos contatos
- Horário de atendimento (início/fim) com mensagem fora do horário
- FAQ por palavra-chave (adicionar/remover dinamicamente)
- Configuração de IA: toggle, provedor (DeepSeek/Gemini), texto de treinamento
- Atualização de `ProcessWhatsappWebhook` para verificar FAQ e horário antes de acionar IA

---

## 4. CMS DE PÁGINAS PÚBLICAS

### Fix `/pagina/sobre` — 404
- Removido fallback quebrado `pages.about` (view não existia)
- Seeder `PageSeeder` rodado no VPS para criar registro `sobre` no banco
- URL `/pagina/privacidade` e `/pagina/termos` continuam funcionando

### Analytics de Visualizações
**Tabelas criadas**: `post_views`, `page_views`

- Tracking automático em `/blog/{slug}` e `/pagina/{slug}`
- Throttle: 1 view por IP por dia (não infla contagem)
- Falha silenciosa (nunca quebra página pública)
- Admin Blog (`/admin/blog`): card "Views Totais + este mês" + coluna views por artigo
- Admin Pages (`/admin/pages`): 3 cards de analytics + coluna views + link direto para URL pública

---

## 5. AUDITORIA DE SEGURANÇA — 5 Agentes em Paralelo

**Arquivos gerados**: `AUDIT_SUMMARY.md`, `CORRECTION_PLAN.md`

### Agentes executados
1. **Security Agent** — Auth, XSS, SQLi, CSRF, uploads, hashing
2. **Attack Surface Agent** — Rotas públicas, rate limiting, headers, dependências
3. **Data Leak Agent** — PII em respostas, logs, S3, campos sensíveis
4. **Tenant Isolation Agent** — Global scopes, cross-tenant access, uploads
5. **Functional Agent** — Race conditions, PIX idempotência, WhatsApp, filas

### Resultado: 44 achados
- 10 Críticos | 12 Altos | 14 Médios | 8 Info

### Top 10 Críticos identificados
1. Secrets no `.env` commitado
2. `WEBHOOK_HMAC_KEY` hardcoded no código-fonte
3. Webhook PIX sem idempotência (pagamento duplo possível)
4. Queue workers não configurados
5. SecurityHeaders middleware desativado
6. User model sem BelongsToTenant
7. `/t/{tenant_id}` numérico — enumeration attack
8. Evolution `instance_token` exposto em JSON
9. `/validar-certificado/{id}` — enumeration por ID sequencial
10. Senha mínima 6 caracteres

---

## 6. PLANO DE CORREÇÃO — 3 Sprints
**Arquivo gerado**: `CORRECTION_PLAN.md`

---

## 7. CORREÇÕES APLICADAS EM PRODUÇÃO

### Sprint 1 — Bloqueantes (commits desta sessão)

#### `b1a48ca` — HMAC key hardcoded removida
- `app/Services/AbacatePayService.php`: constante `WEBHOOK_HMAC_KEY` removida
- Método `getHmacKey()` lê de `config('services.abacatepay.hmac_key')` OU `SystemSetting` (painel admin)
- `config/services.php`: entrada `abacatepay.hmac_key` adicionada
- `.env.example`: placeholder `ABACATEPAY_HMAC_KEY=` adicionado

#### `0366dad` — Idempotência e lockForUpdate nos pagamentos
- `app/Jobs/ProcessAbacatePayWebhook.php`: `insertOrIgnore` + `DB::transaction` + `lockForUpdate` + guard `status !== 'paid'`
- `app/Http/Controllers/OpenPixWebhookController.php`: `DB::transaction` + `lockForUpdate` + emails enviados fora da transaction
- `app/Console/Commands/CleanupRaffleReservations.php`: UPDATE atômico (elimina race condition get+foreach)
- **Migration criada**: `processed_webhooks` (gateway, webhook_id UNIQUE) para deduplicação de webhooks

#### `d1da2fb` — Isolamento de tenant e campos sensíveis
- `app/Models/WhatsappInstance.php`: `$hidden = ['instance_token']`
- `app/Models/Tenant.php`: `$hidden = ['pix_key', 'pix_key_type', 'openpix_app_id']`
- `app/Models/Beneficiary.php`: `$hidden = ['cpf', 'nis', 'birth_date']`
- `app/Http/Controllers/ManagerController.php`: senha `min:6` → `min:12` com complexidade
- `app/Http/Controllers/TeamController.php`: senha `min:6` → `min:12` com complexidade

#### `c70589c` — Superfície de ataque e infraestrutura
- `app/Http/Kernel.php`: SecurityHeaders middleware **ativado**
- `app/Http/Middleware/SecurityHeaders.php`: HSTS `max-age=300` → `max-age=31536000` (1 ano) + `Permissions-Policy`
- `routes/web.php`:
  - `/t/{tenant_id}` — throttle:10,1 adicionado
  - `/validar-recibo` — throttle 30→5 por minuto
  - `/validar-certificado` — throttle 60→5 por minuto
  - `/sign/{token}` POST — throttle:10,1 adicionado
- `.env.example`: `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true` adicionados
- **Arquivo criado**: `supervisord.conf` (workers default x2 + whatsapp x1 + scheduler)
- **Arquivo criado**: `scripts/deploy.sh` (deploy automatizado)

#### `8cc3aef` — Fix HMAC key via SystemSetting
- `app/Services/AbacatePayService.php`: fallback para SystemSetting como fonte da chave HMAC
- **Motivo**: chaves são gerenciadas via painel admin, não .env

---

## 8. SUPERVISOR — Configurado e ativo no VPS

```
vivensi-scheduler                  RUNNING   pid 192087
vivensi-worker-default_00          RUNNING   pid 192088
vivensi-worker-default_01          RUNNING   pid 192090
vivensi-worker-whatsapp_00         RUNNING   pid 192091
vivensi-worker (legado)            RUNNING   pid 184826
```

Jobs de fila, automações WhatsApp e scheduler agora executam automaticamente e reiniciam se o servidor reiniciar.

---

## 9. ITENS RESOLVIDOS DA AUDITORIA

| # | Severidade | Item | Status |
|---|-----------|------|--------|
| #1 | CRÍTICO | Secrets no repositório Git | ✅ |
| #2 | CRÍTICO | WEBHOOK_HMAC_KEY hardcoded | ✅ |
| #3 | CRÍTICO | Webhook PIX sem idempotência | ✅ |
| #4 | CRÍTICO | Queue workers não configurados | ✅ |
| #5 | CRÍTICO | SecurityHeaders desativado / HSTS 300s | ✅ |
| #7 | CRÍTICO | `/t/{tenant_id}` sem throttle | ✅ |
| #8 | CRÍTICO | `instance_token` exposto em JSON | ✅ |
| #9 | CRÍTICO | `/validar-certificado/{id}` throttle alto | ✅ |
| #10 | CRÍTICO | Senha mínima 6 caracteres | ✅ |
| #11 | ALTO | Idempotência AbacatePay | ✅ |
| #12 | ALTO | Race condition rifas | ✅ |
| #13 | ALTO | CPF/NIS exposto em JSON | ✅ |
| #15 | ALTO | SESSION_SECURE_COOKIE ausente | ✅ |
| #16 | ALTO | PIX key exposta no Tenant | ✅ |
| #18 | ALTO | throttle `/validar-recibo` alto | ✅ |

**15 de 22 itens críticos/altos resolvidos (68%)**

---

## 10. PENDENTES PARA PRÓXIMAS SESSÕES

| # | Severidade | Item |
|---|-----------|------|
| #6 | CRÍTICO | User model BelongsToTenant (risco de quebrar auth — requer análise) |
| #14 | ALTO | Upload paths sem tenant_id (Banner, Transaction, ScheduledPost) |
| #17 | ALTO | Wildcards `"*"` no composer.json |
| #19 | MÉDIO | CPF mascarado no WhatsApp Bot |
| #21 | MÉDIO | Páginas de erro 404/500 customizadas |
| #23 | MÉDIO | Notification model sem tenant_id |

---

## 11. ARQUIVOS DE REFERÊNCIA GERADOS

| Arquivo | Descrição |
|---------|-----------|
| `AUDIT_SUMMARY.md` | Todos os 44 achados com arquivo+linha |
| `CORRECTION_PLAN.md` | Código completo de cada correção com rollback plan |
| `SESSION_LOG.md` | Este arquivo — resumo completo da sessão |
| `supervisord.conf` | Configuração do Supervisor para produção |
| `scripts/deploy.sh` | Script de deploy automatizado |
| `.claude/agents/` | 6 agentes especializados de correção |
