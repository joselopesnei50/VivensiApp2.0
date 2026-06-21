# Vivensi — Painel Gestor de Projetos · Roadmap de Implementação (Claude Code)

> **ESCOPO INEGOCIÁVEL:** todas as ações deste documento valem **somente para o Painel do Gestor de Projetos**. Não tocar nos painéis do Terceiro Setor (Vivensi NGO), VivensiCT ou Mobiliza, exceto código compartilhado explicitamente identificado e protegido por flag `APP_PRODUCT`.

---

## 1. Objetivo

Transformar o Painel do Gestor de Projetos numa **máquina de gestão de projetos e mobilização social** operada por WhatsApp, e-mail e IA. A tese que une todas as features é um único loop:

```
Perfil Operacional (contexto)  →  Canais (WhatsApp / E-mail) captam
        →  Cadastro/CRM de leads  →  Bruce IA qualifica
        →  Kanban age  →  Métricas fecham o loop  →  (recomeça)
```

O **Perfil Operacional** (o campo de instrução que o usuário preenche: "campanha eleitoral", "gestão de projetos culturais", "projetos empresariais") é a espinha dorsal: ele ajusta rótulos/KPIs do painel, alimenta o *system prompt* do Bruce e seleciona templates de Kanban e de mensagem. Por isso ele é a primeira coisa a existir depois da correção do bug crítico.

---

## 2. Stack confirmada (não inferir, não "atualizar" sem ordem)

- **Laravel 9** (NÃO é 11), **PHP 8.1**
- **Blade + Livewire 3**, **FilamentPHP 3**, **Tailwind CSS**
- **MySQL** (AWS RDS), **Redis** (ElastiCache), **S3 + CloudFront**
- **Filas Laravel + Supervisor**
- **Evolution API** para WhatsApp (instâncias por cliente em VPS Lightsail)
- **OpenPix/PIX** para pagamentos · **Brevo** para e-mail · **Pusher/Soketi** para WebSockets
- IA atual: **DeepSeek** na maioria dos casos; **Gemini** disponível. Roteamento por risco LGPD (ver §3).
- Bug histórico conhecido: `AppServiceProvider` consultando `system_settings` antes das migrations — guarda obrigatória com `Schema::hasTable()`.

Ambiente local: Windows/PowerShell em `C:\xampp\htdocs\vivensi-laravel`. `.env` **não** versionado.

---

## 3. Regras transversais inegociáveis

Toda fase respeita estas regras. O agente `lgpd-eleitoral-guardian` audita o diff antes do merge.

1. **Persistir antes de transmitir.** Toda mensagem que chega por webhook é **gravada no banco com o corpo completo** *antes* de qualquer broadcast WebSocket. O histórico nunca depende do Pusher/Soketi.
2. **Isolamento multi-tenant.** Toda query nova é escopada por `tenant_id`. Nada de operação cross-tenant fora de comandos administrativos auditados (lembrar do bug `purgeExpired()` do VivensiCT).
3. **Opt-in explícito e registrado** em todo cadastro e todo disparo (LGPD Art. 7º/8º). Guardar consentimento com origem, timestamp e IP. Disparo em massa exige opt-in válido.
4. **Dado sensível e contexto eleitoral.** Opinião política é dado sensível (LGPD Art. 5º II) e há regras eleitorais (Lei 9.504/97, TSE). Minimização de dados, sem PII em logs, sem coordenadas/nome real de beneficiário em respostas de API ou logs.
5. **Roteamento de IA por risco.** Caso de uso com dado pessoal sensível → **Claude via AWS Bedrock** ou **Gemini em região GCP brasileira**. Casos de baixo risco → DeepSeek mantido.
6. **Cotas e capacidade no Super Admin.** Limites (e-mail/dia, temas/mês, etc.) ficam configuráveis por tenant no painel do Super Admin, com valor default no código.
7. **Termos versionados.** Aceites (anti-ban etc.) gravam versão do termo + usuário + timestamp + IP, exibidos no perfil do cliente no Super Admin.
8. **Convenções de código:** lógica de negócio em Services (`App\Services\...`), trabalho pesado/externo em Jobs com retry e backoff, migrations idempotentes, e teste automatizado cobrindo o critério de aceite de cada item.

---

## 4. Time de agentes (`.claude/agents/`)

Copie os arquivos da pasta `agents/` para `.claude/agents/` na raiz do repositório. Cada um tem escopo e gatilho próprios:

| Agente | Domínio | Quando aciona |
|---|---|---|
| `laravel-core-engineer` | Backend Laravel 9: migrations, models, services, jobs, filas, tenant isolation | Qualquer mudança de modelo de dados ou regra de negócio |
| `whatsapp-evolution-specialist` | Webhooks Evolution, parsing de payload Baileys, persistência de mensagens, OmniChannel, normalização BR, Cloud API oficial | Tudo de WhatsApp/canal |
| `bruce-ai-orchestrator` | DeepSeek/Gemini, prompts a partir do perfil operacional, qualificação de lead, áudio (STT), formulário conversacional | Tudo de IA |
| `filament-livewire-ui` | Painéis Filament 3, resources, widgets/dashboards, Livewire 3, Kanban, thread em tempo real | Tudo de UI/painel |
| `email-campaign-engineer` | Brevo, templates/MJML, geração de tema por IA, A/B, cota diária, tracking de eventos | Tudo de e-mail |
| `lgpd-eleitoral-guardian` | Revisor transversal: LGPD, lei eleitoral, opt-in, PII em logs, isolamento | Antes de todo merge (read-only) |

**Fluxo de trabalho sugerido:** uma branch por fase → implementar com os agentes de domínio → rodar `lgpd-eleitoral-guardian` no diff → corrigir → merge.

---

## 5. Fases de execução

A ordem respeita dependências. Cada fase é autocontida e pode ser colada isoladamente no Claude Code.

### Fase 0 — Correção do bug crítico: mensagens vazias no OmniChannel
**Mapeia:** item 1 (bug) · **Agente:** `whatsapp-evolution-specialist` + `laravel-core-engineer`

**Sintoma:** com o chat aberto, mensagens chegam em tempo real; no dia seguinte a conversa aparece na lista com badge, mas abre **vazia**. É **perda de dado**, não só UX — prioridade máxima.

**Diagnóstico primeiro (não chutar a correção):**
- [ ] Inspecionar o handler do webhook da Evolution (evento `messages.upsert`) e confirmar se a mensagem é persistida **com o corpo**.
- [ ] Verificar extração de corpo por tipo de mensagem do Baileys: `message.conversation`, `message.extendedTextMessage.text`, `message.imageMessage.caption`, `message.videoMessage.caption`, `message.audioMessage`, `message.documentMessage`, etc.
- [ ] Confirmar se o broadcast (Soketi/Pusher) está acontecendo **no lugar da** persistência (hipótese principal) ou depois dela.
- [ ] Verificar escopo de `tenant_id` e eager-loading na query da thread ao recarregar.
- [ ] Cruzar com o bug do `PUSHER_HOST` vazio (entrega intermitente ≠ thread vazia; não confundir).

**Correção esperada:**
- [ ] Persistir toda mensagem recebida com corpo completo e tipo, escopada por tenant e contato, **antes** do broadcast.
- [ ] Normalizar extração de corpo para todos os tipos de mensagem.
- [ ] Garantir que a thread recarregada do banco renderize idêntica à recebida em tempo real.

**Critério de aceite:**
- [ ] Teste de feature que simula payload de webhook, recarrega a thread do banco e confirma o corpo presente.
- [ ] Reabrir conversa no dia seguinte mostra histórico completo.

**Risco/LGPD:** mensagens podem conter dado pessoal — sem PII em logs; respeitar retenção.

---

### Fase 1 — Perfil Operacional (base de tudo)
**Mapeia:** novo (campo de instrução) + habilita 2.1, 2.3, 4.1, 5 · **Agente:** `laravel-core-engineer` + `filament-livewire-ui`

**Objetivo:** entidade de 1ª classe por tenant que define o contexto de uso e personaliza o sistema.

- [ ] Migration `perfis_operacionais` (ou campo no tenant): `categoria` (enum: campanha_eleitoral, projeto_cultural, projeto_empresarial, mobilizacao_social, outro), `instrucao` (texto livre), `vocabulario` (json opcional de rótulos).
- [ ] Tela no painel para o usuário preencher/editar a instrução.
- [ ] Service `PerfilOperacionalService` que expõe: rótulos/KPIs para o dashboard, contexto para o prompt do Bruce, e template default de Kanban/mensagem.
- [ ] **2.1 — KPI configurável:** em "Project Manager Intelligence – Central de Comando", tornar os cards configuráveis pelo perfil. Para perfis de mobilização, exibir **nº de mensagens WhatsApp recebidas** no lugar de "Entrada / Mês"; manter o KPI financeiro para perfis que o usem. (Depende da Fase 0.)

**Critério de aceite:**
- [ ] Trocar a categoria do perfil muda rótulos/KPIs do painel e o contexto injetado no Bruce, sem deploy.
- [ ] Card de mensagens WhatsApp reflete a contagem real persistida.

**Risco/LGPD:** se categoria = campanha_eleitoral, ativar avisos e travas eleitorais nas fases seguintes.

---

### Fase 2 — Proteções rápidas (baixo esforço, alta proteção)
**Mapeia:** 4.2 + 3.3 · **Agentes:** `whatsapp-evolution-specialist`, `email-campaign-engineer`, `laravel-core-engineer`

**4.2 — Termo de aceite anti-ban antes de criar instância Evolution**
- [ ] Modal/etapa obrigatória antes da criação da instância, com a política anti-ban e responsabilidade do usuário pelo número.
- [ ] Gravar aceite versionado: usuário, versão do termo, timestamp, IP.
- [ ] Exibir o aceite no perfil do cliente no Super Admin.

> Observação: este é um recurso de **registro de aceite/responsabilidade** (proteção jurídica). Não inclui técnicas de evasão de banimento.

**3.3 — Limite de 50 e-mails/dia + capacidade contratável**
- [ ] Campo de cota diária por tenant (default 50), com reset diário (Redis/coluna).
- [ ] *Enforcement no Job de envio* — interromper/segurar disparos acima da cota com mensagem clara ao usuário.
- [ ] Capacidade editável por tenant no Super Admin.

**Critério de aceite:**
- [ ] Criação de instância bloqueada sem aceite; aceite aparece no Super Admin.
- [ ] Disparo trava ao atingir a cota; aumentar a cota no Super Admin libera na hora.

---

### Fase 3 — Transferência de atendimento + Kanban geral
**Mapeia:** item 1 (melhoria) + 2.2 · **Agentes:** `laravel-core-engineer`, `filament-livewire-ui`

**Transferência de atendimento**
- [ ] `assigned_user_id` na conversa; ação "Transferir para…" listando usuários do plano do tenant.
- [ ] Log de auditoria + notificação ao novo responsável.
- [ ] Filtros: "minhas conversas / não atribuídas / todas", respeitando papéis.

**2.2 — Kanban geral do sistema**
- [ ] Modelos `kanban_boards`, `kanban_columns`, `kanban_cards` (escopados por tenant; card opcionalmente ligado a projeto e/ou a uma conversa/mensagem do WhatsApp).
- [ ] Board com drag-and-drop em Livewire 3 (colunas configuráveis).
- [ ] Ação **"Criar card a partir de uma mensagem do WhatsApp"** no OmniChannel.
- [ ] Templates de colunas default conforme o Perfil Operacional.

**Critério de aceite:**
- [ ] Mover card entre colunas persiste e reflete em tempo real.
- [ ] Card criado de uma mensagem mantém link de volta para a conversa.

---

### Fase 4 — Camada de IA (Bruce)
**Mapeia:** 2.3 + 2.4 + 2.5 · **Agente:** `bruce-ai-orchestrator` (+ `whatsapp-evolution-specialist`, `filament-livewire-ui`)

**2.3 — Qualificação de lead e roteamento pro Kanban**
- [ ] Quando o bot estiver ativo no OmniChannel, classificar intenção/qualificar o lead e criar card no Kanban do projeto ou no geral, com a qualificação anexada.
- [ ] Prompt do Bruce recebe o contexto do Perfil Operacional (Fase 1).

**2.4 — Bot interpreta áudio e responde**
- [ ] Baixar o áudio da Evolution → transcrever (STT) → enviar texto ao LLM → responder (TTS opcional).
- [ ] STT recomendado: **Gemini multimodal** (áudio nativo, já usamos Gemini) ou **Whisper via Groq** para PT-BR barato/rápido.
- [ ] Roteamento por risco LGPD: áudio com dado sensível → Gemini região BR ou Whisper self-hosted.

**2.5 — Formulário/questionário conversacional**
- [ ] Bot (ou atendente) envia perguntas sequenciais; máquina de estados captura respostas e grava ligando ao lead (Fase 5).
- [ ] Usar mensagens interativas (botões/listas) da Evolution quando possível; fallback Q&A texto.

**Critério de aceite:**
- [ ] Conversa qualificada vira card automaticamente com a classificação.
- [ ] Áudio recebido é transcrito e respondido corretamente em PT-BR.
- [ ] Respostas do formulário ficam estruturadas e vinculadas ao contato.

---

### Fase 5 — CRM de leads + Hub de Planejamento
**Mapeia:** 5.2 + 5.1 · **Agentes:** `laravel-core-engineer`, `filament-livewire-ui`, `bruce-ai-orchestrator`, `lgpd-eleitoral-guardian`

**5.2 — Cadastro/CRM de leads (backbone da mobilização)**
- [ ] Modelo `leads` com cidade, segmentação (tags), status, e **origem/timestamp do opt-in**.
- [ ] Formulários públicos de cadastro com **opt-in explícito** em cada página.
- [ ] **Validação por WhatsApp (double opt-in)** após contato ou após disparo.
- [ ] Dashboard: total de cadastrados, gráfico por cidade e por segmentação.
- [ ] Campo "evolução" (linha do tempo): notas manuais ou **puxadas da conversa do WhatsApp**; o **Bruce sugere próximas ações** de abordagem a partir das notas.

**5.1 — Melhorar o Hub de Planejamento Estratégico**
> Precisa de leitura do código atual antes de detalhar. Direções: entradas de persona/segmentação, planos por canal (WhatsApp/e-mail/social), saída estruturada, exportação para calendário, amarração ao Perfil Operacional e **push das ações geradas direto pro Kanban geral (Fase 3)**.
- [ ] `filament-livewire-ui` + `laravel-core-engineer` mapeiam a implementação atual e propõem melhorias incrementais.

**Critério de aceite:**
- [ ] Cadastro sem opt-in é rejeitado; consentimento fica registrado e auditável.
- [ ] Gráficos por cidade/segmentação refletem dados reais.
- [ ] Plano gerado consegue criar cards no Kanban.

**Risco/LGPD (alto):** fase mais sensível. Em contexto eleitoral, opinião política é dado sensível — minimização, consentimento e finalidade explícita.

---

### Fase 6 — Trilha paralela de infraestrutura
**Mapeia:** 4.3 + 3.1 + 3.2 · **Agentes:** `whatsapp-evolution-specialist`, `email-campaign-engineer`

**4.3 — WhatsApp Cloud API oficial (Meta), sem celular conectado**
- [ ] Camada de abstração de canal por tenant: **Evolution (não-oficial)** vs **Cloud API (oficial)**.
- [ ] Cloud API: verificação Meta Business, número registrado, **templates aprovados pela Meta**, janela de atendimento de 24h, cobrança por conversa.
- [ ] Iniciativa maior — tratar como épico próprio. Benefício: à prova de ban, abre clientes que não toleram risco.

**3.1 — Tema de e-mail próprio + ajuda do Bruce (2 grátis/mês)**
- [ ] Primeiro: **geração de HTML por IA (DeepSeek)** a partir de prompt (barato, alto impacto).
- [ ] Depois: builder visual (sugestão: MJML ou blocos restritos; evitar GrapesJS/Unlayer no início).
- [ ] Cota "2 grátis/mês" com contador no tenant + liberação de mais no Super Admin.

**3.2 — Envio A/B de campanha**
- [ ] Split da lista, variantes A/B, medir abertura/clique, opcional auto-envio do vencedor.
- [ ] Depende de **tracking de eventos do Brevo via webhook** ligado.

---

## 6. Resumo da ordem recomendada

1. **Fase 0** — bug das mensagens vazias (perda de dado).
2. **Fase 1** — Perfil Operacional (base barata que threada tudo).
3. **Fase 2** — termo anti-ban + cota de e-mail (em paralelo).
4. **Fase 3** — transferência de atendimento + Kanban geral.
5. **Fase 4** — IA: qualificação, áudio, formulário conversacional.
6. **Fase 5** — CRM de leads + Hub de Planejamento.
7. **Fase 6** — Cloud API oficial + builder/A-B de e-mail (trilha paralela).

> Antes de implementar a Fase 0 e a 5.1, cole no Claude Code o handler do webhook da Evolution + o componente Livewire da thread, e o código atual do Hub. São os dois pontos que precisam de leitura do código real para detalhamento cirúrgico.
