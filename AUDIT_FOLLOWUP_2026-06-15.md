# Auditoria — Follow-up de encerramento

> Resumo de execução da auditoria de segurança `PROMPT_CORRECAO_VIVENSI.md`.
> Janela: 2026-06-10 a 2026-06-15 (6 dias corridos, ~5 sessões de trabalho).
> Branch: `main`. Repo: `joselopesnei50/VivensiApp2.0`.

---

## Sumário executivo

**8 de 10 tarefas fechadas em produção.** As 2 restantes (1.4 e 2.2) foram **conscientemente adiadas** após análise crítica — não por falta de tempo, mas por trade-off de ROI (descrito abaixo).

Zero incidentes em produção durante a janela. Zero rollbacks. Zero regressões reportadas.

---

## O que foi corrigido (por tarefa)

### ✅ 2.1 — Pipelines de campanha WhatsApp consolidados (Caminho B)

**Risco original:** dano ativo contínuo. Pipeline legado `EnviarMensagemCampanhaJob` rodava sem `AntiBanManager` (só `sleep` cego), expondo números de clientes a ban inevitável a cada disparo de campanha.

**Solução aplicada:** integração cirúrgica do `AntiBanManager` no job legado — janela horária + rate limit + simulação de digitação + circuit breaker + detecção de sinal de ban + delay orgânico. Migration `2026_06_11_120000` adicionou status `pausada` ao enum de `campanhas` para suportar retomada manual.

**Backlog formal:** `BACKLOG_WHATSAPP_CONSOLIDATION.md` documenta o **Caminho A** (consolidação real da duplicação `Campanha`/`BroadcastCampaign`) com pré-requisitos de decisão de produto e estimativa de 4-6 semanas.

**Commits:** `b7ebb72`, `33b1a9a`.

---

### ✅ 1.1 — XSS armazenado em páginas institucionais

**Risco original:** `PageController@update` salvava `$request->content` sem sanitização. `admin/pages/edit.blade.php:39` renderizava `{!! !!}` cru no editor Quill — XSS executável em contexto admin.

**Solução aplicada:** sanitização defensiva em escrita E leitura via `sanitize_user_html()` (helper já existente, com allowlist + remoção de `on*` + neutralização de `javascript:`/`data:`/`vbscript:` + remoção de `style` inline). Comando `pages:sanitize-legacy` para regravar payloads já armazenados (rodado em produção, 0 sanitizadas — banco estava limpo).

**Commit:** `227e4b4`.

---

### ✅ 3.1 — Opt-out com falso positivo

**Risco original:** `AntiBanManager::isOptOutMessage()` usava `str_contains` com keywords curtas. "Parabéns" disparava opt-out por conter "para", "saia" por conter "sai", "comparar" por conter "parar". Cliente que respondia elogio era bloqueado.

**Solução aplicada:** separação em duas listas — `OPTOUT_EXACT` (palavras curtas, match exato da mensagem inteira) e `OPTOUT_CONTAINS` (frases longas com word-boundary regex `\b`). Normalização de acentos antes da comparação. Método marcado como `static` (função pura). 21 testes Pest cobrindo critérios da auditoria + edge cases.

**Nota honesta:** o método `isOptOutMessage()` **não está cabeado** em nenhum handler de mensagem entrante hoje. A correção é preventiva. Cabeamento no Evolution webhook fica como follow-up natural.

**Commit:** `e4b4cfb`.

---

### ✅ 1.3 — Filtro de tenant em jobs e commands (Forma A)

**Risco original:** trait `BelongsToTenant` ignora intencionalmente o global scope quando `runningInConsole()`. Jobs/commands podem fazer queries amplas sem filtro `tenant_id`.

**Solução aplicada (Forma A acordada com o usuário):** auditoria documentada de **51 arquivos** (27 jobs + 24 commands) classificados em 6 categorias. Veredito: **zero problemas reais de isolamento**. Três arquivos receberam comentários justificando seus `withoutGlobalScopes()` que estavam sem documentação.

**Backlog formal:** `AUDIT_JOBS_TENANT_FILTER.md` com follow-ups 1.3.A a 1.3.D (defesa em profundidade via `tenant_id` no construtor dos jobs por-PK, comentários nos bypasses restantes, remoção de boilerplate vazio, endurecimento do trait).

**Commit:** `a642ef1`.

---

### ✅ 3.4 — Doc compliance Baileys vs Cloud API

**Risco original:** toda a estratégia antiban é evasão de ToS Meta para Baileys/Evolution. Sem doc, posição da Vivensi sobre o risco regulatório era implícita.

**Solução aplicada:** `docs/WHATSAPP_COMPLIANCE.md` define posicionamento explícito — Cloud API como padrão para clientes novos, Baileys mantido com termo de uso para legados, AntiBanManager tratado internamente como redução de probabilidade (não eliminação). Rascunho de cláusula contratual para clientes em Baileys (marcado como pendente revisão jurídica). Cross-references com `ROADMAP_APROVACAO_META.md`.

**Commit:** `02e79ee`.

---

### ✅ 3.3 — Double-counting de delays no envio Evolution

**Risco original:** `EvolutionApiService::sendMessage()` tinha "armadilha do default 1200ms" — caller passava `delay=0` esperando "sem delay" e ganhava 1.2s mágicos. `ProcessBroadcastCampaignJob:266` somava `rand(2,5)` de API delay depois de já ter simulado digitação humana (6-14s).

**Solução aplicada:** default mágico de 1200ms removido. `ProcessBroadcastCampaignJob` passa `delay=0` em envios individuais (mantém `rand(2,5)` só em grupos sem simulação). Docblock novo no `sendMessage` documenta tempo médio esperado por mensagem (~11-29s na pipeline anti-ban). Em campanha de 100 destinatários: ~2 minutos a menos de delay redundante.

**Commit:** `4eda967`.

---

### ✅ 1.5 — Blind index dos tokens WhatsApp desacoplado de APP_KEY

**Risco original:** `WhatsappInstance::setInstanceTokenAttribute` e `EvolutionWebhookController` usavam `config('app.key')` direto no HMAC. Rotacionar `APP_KEY` quebrava lookup de instâncias nos webhooks.

**Solução aplicada (versão sem fallback eterno):** nova env `WHATSAPP_BIDX_KEY` em `config/whatsapp.php`. Helper `whatsapp_bidx_key()` prefere a env dedicada, cai para `APP_KEY` se vazia (compat para dev e prod não migrada). Comando `whatsapp:rebuild-bidx --apply` regrava todos os bidx das instâncias existentes (idempotente). Migração executada em produção em 2026-06-12 com sucesso — 14 instâncias migradas, webhooks continuam resolvendo.

**Commit:** `ed89170`. Migração aplicada em prod.

---

### ✅ 3.2 — Warming e limites do AntiBanManager configuráveis por instância

**Risco original:** `WARMING_PROFILE`, `MAX_PER_HOUR`, `DEFAULT_BAN_RESTRICTION_HOURS` hardcoded como constants. Nenhum cliente conseguia ajustar perfil de warming para chip novo sem fork de código.

**Solução aplicada:** valores migrados para `config('whatsapp.antiban')` com defaults idênticos ao anterior (sem mudança de comportamento sem opt-in). Override por instância via `whatsapp_instances.settings` JSON (sem migration de schema). Dois perfis pré-definidos: `default` (mesmo de sempre, 20→370/dia em 14 dias) e `conservative` (15→150/dia em 14 dias, recomendado para chip novo). Doc `docs/WHATSAPP_TUNING.md` documenta ativação por Tinker.

**Commit:** `90c709a`.

---

## O que ficou como follow-up

### ⏸️ 1.4 — CSP nonce em script-src

**Por que adiada:** Fase 1 (adicionar nonce ao middleware) sem Fase 2 (migrar 234 views com inline scripts) tem ganho de segurança ZERO — navegadores ignoram `'unsafe-inline'` quando há nonce, mas o atacante ainda pode injetar via outros vetores enquanto `'unsafe-inline'` permanecer. Trabalho de ~1-2 semanas dedicadas.

**Mitigação parcial:** Tarefa 1.1 (XSS sanitizado) fechou o vetor principal de XSS armazenado. CSP nonce defenderia contra vetores futuros, mas o vetor crítico hoje já está coberto.

**Quando atacar:** sprint dedicado, quando houver janela de 2 semanas e cobertura de teste de integração suficiente para garantir que nenhuma view perde script.

---

### ⏸️ 2.2 — Consolidação de tokens de design

**Por que adiada:** 234 `style=` inline + 5 CSS soltos + 96 views Bootstrap + 65 views Tailwind são sintoma de **falta de extração de componentes Blade**, não de "falta de tokens CSS". Documentar tokens em `design-system.css` não reduz inline-style spread. Caminho honesto é refatorar 5-10 componentes mais repetidos (hero, kpi-card, section-card, sidebar-item, btn-premium), o que é trabalho de outro sprint.

**Quando atacar:** quando houver decisão de produto sobre direção visual (já discutida em sessão de 2026-06-10 — Memorial Vivo descartado, direção atual aprovada como referência). A consolidação técnica pode então ser feita extraindo componentes que materializam essa direção.

---

## Follow-ups operacionais identificados durante a auditoria

Não pertencem ao escopo das 10 tarefas mas surgiram no caminho e merecem registro:

1. **`horizon.service` não existe como systemd unit** (descoberto em 2026-06-12)
   - Sintoma: "mensagens só chegam quando sistema está aberto"
   - Causa: workers da fila `whatsapp` não rodam 24/7 sem Horizon/supervisor
   - Fix: instalar Horizon como systemd unit ou supervisor com `autostart=true`

2. **Permission denied no log file** (`storage/logs/laravel-*.log`)
   - Ubuntu user não consegue gravar (owner = www-data)
   - Fix: `sudo chown -R www-data:www-data storage/logs && sudo chmod -R 775 storage/logs`

3. **Polkit prompt em `sudo systemctl`**
   - Detecção tardia na migração 1.5 (Etapa 2)
   - Workaround usado: `sudo -s` antes do reload, depois `exit`
   - Fix permanente: configurar NOPASSWD em `/etc/sudoers.d/` para `systemctl reload php8.1-fpm`

4. **Instância `id=25` (tenant 15) com `status=close`** (detectado em 2026-06-15)
   - Pode explicar falhas de envio se algum cliente reclamar
   - Não é bug do sistema — é estado de conexão WhatsApp

---

## Gaps que a auditoria original não cobre (já apontados na análise crítica inicial)

Mantidos como **backlog de auditoria futura**:

- **Criptografia at-rest dos models ONG** (na memória do projeto: "Grupo Seguro aprovado, Grupo Delicado aguarda decisão")
- **Assinatura HMAC dos webhooks Meta** (`x-hub-signature-256`)
- **Rate limit em rotas públicas críticas** (login, forgot-password, doação pública)
- **Cache stampede no portal de transparência** (`Cache::remember` sem lock)
- **Validação de uploads** (MIME, tamanho, scan)
- **`composer audit` / `npm audit` no CI**
- **Logs vazando PII** (debug_backtrace no `BelongsToTenant`)
- **Índices faltando + DoS por query lenta** em tabelas grandes
- **2FA** — gaps de implementação não revisados

---

## Auditoria entregue para além do escopo das 10 tarefas

Trabalhos colaterais entregues nesta janela que aproveitaram a momentum:

- **Bloco "Onde estamos" no portal de transparência** (mapa territorial agregado por `Beneficiary.address_city` + `address_state`, LGPD-safe) — refatoração de `transparency/portal.blade.php` aba `#impacto`
- **Observer automatizando invalidação de cache do portal** quando `Beneficiary` ou `FamilyMember` são criados/atualizados/removidos (evita stale 1h após cadastro)
- **Fix de timezone** no "Atualizado em" do portal (UTC → America/Sao_Paulo)
- **Aviso de cobertura parcial** quando `address_city` é preenchido em menos de 100% dos beneficiários

---

## Estado de produção em 15/06/2026 23:00

- ✅ Todos os 8 deploys aplicados sem rollback
- ✅ `WHATSAPP_BIDX_KEY` dedicado ativo (chave `kftz1...`)
- ✅ 14 instâncias WhatsApp com bidx regravado
- ✅ Observer de Beneficiary/FamilyMember ativo
- ✅ AntiBanManager protege pipeline legado + Broadcast
- ✅ AntiBan tuning configurável (defaults preservados, override disponível)
- ✅ Docs vivos no repo: `BACKLOG_WHATSAPP_CONSOLIDATION.md`, `AUDIT_JOBS_TENANT_FILTER.md`, `docs/WHATSAPP_COMPLIANCE.md`, `docs/WHATSAPP_TUNING.md`, `AUDIT_FOLLOWUP_2026-06-15.md`

## Encerramento

Auditoria fechada. Próximas sessões podem usar `AUDIT_FOLLOWUP_2026-06-15.md` (este arquivo) como ponto de partida para reabrir 1.4 ou 2.2 quando houver janela, ou para atacar os gaps listados em "Gaps que a auditoria original não cobre".
