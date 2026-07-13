# Relatório Executivo — Auditoria de Segurança Vivensi

**Data:** 2026-07-12
**Origem:** `analise-segurança.md` (8 itens solicitados)
**Plano técnico:** `PLANO_AUDITORIA_2026-07-12.md`

---

## TL;DR

Cinco fases de segurança fechadas em um dia, seis commits, zero downtime, **zero migrations** — nenhum
conflito com a sessão paralela do C4.6 (PII encryption). A suíte de testes de segurança saiu de
**~46 para 82 testes verdes**. Nenhum vetor cross-tenant encontrado. Um problema real de **superexposição
intra-tenant no export LGPD** foi corrigido. Cinco outros pontos de risco foram fechados (quota de IA,
XSS no preview de e-mail, trilha de mudanças em settings/bot/team, opt-in obrigatório em campanhas NGO,
sanitização de erros de IA). Três recomendações ficaram documentadas para sprint futuro por escopo.

---

## Estado antes × depois

| Métrica | Antes | Depois |
|---|---|---|
| Testes de segurança verdes | 46 | **82** |
| Ações super_admin com trilha de auditoria | 5 | **11** (+6) |
| Serviços de IA com quota per-tenant | 0 | **1 serviço central + 8 call sites** |
| Módulos com quota per-tenant em envio massa | 1 (Manager) | **2** (Manager + NGO) |
| Migrations aplicadas nesta auditoria | — | **0** |
| Documentos técnicos gerados | — | `PLANO_AUDITORIA_2026-07-12.md` (na raiz) |

---

## Vetores corrigidos, por impacto

### 1. LGPD — dois vetores diretos fechados

- **Art. 18 IV (portabilidade)** — o export self-service em `/eu/dados` incluía até 200 chats + 5.000
  mensagens de WhatsApp do tenant inteiro no ZIP. Qualquer usuário comum baixava conversas com todos os
  contatos da organização. Não é vazamento cross-tenant, mas é vazamento **intra-tenant de PII de
  terceiros** — o titular do art. 18 é o solicitante, não a organização. Categorias removidas do export.
- **Art. 8 (consentimento)** — o módulo NGO tinha um tipo de audiência `donors` (legado) que enviava
  campanhas para doadores **sem verificar opt-in**. Só o tipo `donors_optins` filtrava. Ambos passaram a
  exigir consentimento; transacionais para não-consenting seguem por outro canal (`BrevoService::sendEmail`).

### 2. Risco financeiro — cota per-tenant onde não havia

- **Chamadas à API DeepSeek** eram limitadas apenas por rate limiter *per-usuário HTTP*. Um webhook
  malicioso (via qualificação automática) podia disparar centenas de chamadas em background sem cap.
  Novo `AiCallQuotaService` põe teto diário per-tenant (default 500, override via SystemSetting).
- **Módulo de e-mail NGO** também não tinha `EmailQuotaService`. Um user NGO comum podia drenar a
  reputação da conta Brevo. Agora consome + faz refund em caso de falha (mesmo padrão do Manager).

### 3. Trilha de auditoria de mudanças administrativas

Seis ações sensíveis do super admin **não gravavam** `AdminAuditLog`. Todas passaram a gravar:
mudança de API keys em `/admin/settings`, config do bot, telefone de usuário via bot, e as três
operações de membro do time interno (create/update/destroy). O log guarda *quais chaves* mudaram,
**nunca os valores** — secret rotation fica auditável sem que o valor bruto toque a tabela de log.

### 4. XSS armazenado intra-tenant no preview de e-mail

Os três `show.blade.php` (Admin/Manager/NGO) renderizavam o HTML da campanha com `<iframe srcdoc>`
**sem atributo `sandbox`**. JS inline do email executava com origem herdada do painel — um manager
podia criar campanha com `<script>fetch('/api/…', {credentials:'include'})</script>` e comprometer
outros usuários do mesmo tenant que abrissem a preview. Sandbox adicionado sem `allow-scripts`.

### 5. Superfície de erro vazando detalhes

- `DeepSeekService::chat()` retornava o **body cru da API DeepSeek** em `['error' => ...]`, que era
  renderizado em 6 controllers. Agora retorna erro genérico + `error_code` estruturado; body vai só pro
  Log.
- Logs do webhook AbacatePay foram apertados: `email` cru virou `email_hash` (SHA-256) no fallback;
  `createCheckout` erro agora loga metadata em vez do `$response` completo.

### 6. Vetores menores fechados

- `PublicRaffleController::reserve` sem filtro `status='active'` — permitia reservar bilhete em rifa
  pausada/encerrada. Adicionado.
- `subject` e `sender_name` de campanhas sem bloqueio de CRLF — permitia forjar headers SMTP (`Bcc:`,
  `From:`). `not_regex:/[\r\n]/` nos 3 controllers.

---

## O que não era o que parecia — reclassificações

Vale registrar porque a auditoria também consumiu tempo *provando* que alguns achados iniciais não
eram vetores reais:

- **"Prompt injection via mensagem WhatsApp inbound" (agente marcou CRÍTICO)** → **BAIXO.** O output do
  `LeadQualificationService` é enum forte (`frio|morno|quente`) + normalize com max 300 chars. Não há
  canal para exfiltrar dado do sistema. Anotado, não priorizado.
- **"Rotas admin órfãs em `whatsapp/broadcast/*` e `whatsapp/optin/*`" (agente marcou CRÍTICO)** →
  **falso positivo.** Controllers estão no namespace `App\Http\Controllers\Admin\` mas leem
  `auth()->user()->tenant_id` — são controllers **tenant**, não super_admin. Nomenclatura confusa, não
  vulnerabilidade.
- **"HTMLPurifier no `html_content` das campanhas"** — decidido **não fazer.** Purifier quebra CSS
  inline de e-mails; a defesa correta é o `sandbox=""` no iframe do painel (feito) — HTML válido
  no envio fica com o Brevo, que tem sua própria validação.
- **"Mass assignment em POSTs admin"** — verificado, nenhum caso real. Todos os controllers Admin
  validam antes de `create/update` ou usam atribuição campo-a-campo.

---

## Recomendações registradas para sprint futuro

Documentadas no `PLANO_AUDITORIA_2026-07-12.md`, não implementadas por escopo:

| Recomendação | Custo | Valor |
|---|---|---|
| **Webhook Brevo** para bounces/unsubs em tempo real (rota + HMAC + tabela + filtro em `resolveRecipients`) | Médio (1-2 dias) | Alto — hoje o sistema depende de polling de estatísticas com defasagem de horas; usuários que se descadastram recebem a próxima campanha. Gap direto do LGPD art. 18 II. |
| **`landing_page_leads.unsubscribed_at`** + link unsubscribe no template | Baixo (migration + view) | Médio — permite Manager/NGO respeitar opt-out via link no e-mail (hoje só pelo painel Brevo). |
| **Checklist DNS SPF/DKIM/DMARC** no provedor + Brevo | Baixo (fora do código) | Alto — impacta entregabilidade e reputação; deve ser feito por operação, não desenvolvimento. |
| Migrar 61 usos legados de `withoutGlobalScope('tenant')` para `forTenantUnscoped()` | Alto (61 call sites) | Baixo — código atual já é seguro; churn preventivo apenas. |

---

## Itens do documento original tratados como sprint separado

O `analise-segurança.md` misturava segurança com melhoria de produto. Os itens 7 (Bruno mais
inteligente) e 8 (bot interno de clientes) são evolução de NLU/KB — **não pertencem a uma auditoria
de segurança**. Sob o ângulo *de segurança* eles foram cobertos:

- Endpoints `/admin/bot/*` entram na cobertura dinâmica do middleware `EnsureSuperAdmin`
  (`AdminAuditCoverageTest`).
- Mudanças de config do bot passam a gravar `AdminAuditLog`.

A evolução de produto (compreensão de linguagem natural, integração com KB, resolver dúvidas
complexas) fica para sprint próprio — não misturar com trilha de segurança.

---

## Commits nesta auditoria

Todos com testes verdes, mensagens seguem o padrão do repo (sem co-autor Claude):

```
1ae526b sec(p2):   AbacatePay — regressao verde + logs sem PII e sem payload cru
ee2af27 sec(p1.c): super_admin — AuditLog em settings/bot/team + teste de cobertura
faaf4b1 sec(p1.b): campanhas email — quota NGO + iframe sandbox + opt-in obrigatorio + CRLF
3828ddb sec(p1.a): DeepSeek — sanitiza erro upstream + cota diaria per-tenant
91c56f0 sec(p0):   helper forTenantUnscoped + regressao de isolamento em rotas publicas
398bcd0 sec(p0):   export LGPD sem PII de terceiros + reserva apenas em rifa ativa
```

Complementa o commit `fdfbb04` (sessão paralela do dia — auditoria C1 de 201 usos de
`withoutGlobalScope`, foco cross-tenant, zero críticos, `IdorRegressionTest`).

---

## Deploy

- **Nenhuma migration** nesta auditoria. Deploy é `git pull` + `php artisan config:cache` + supervisord
  restart — o padrão do repo. Não exige janela.
- Fase C4.6 (encryption at-rest de `Lead`/`User`) segue independente — nada aqui toca esses models.
- **Sugestão de comunicação interna:** documentar em `HANDOFF.md` que o export self-service em
  `/eu/dados` deixou de incluir conversas de WhatsApp (correção intencional, não bug de UX).
