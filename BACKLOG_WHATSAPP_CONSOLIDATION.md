# Backlog — Consolidação dos pipelines WhatsApp

> Status: **pendente**. Decisão de produto necessária antes de iniciar.
> Última atualização: 2026-06-11
> Contexto: Tarefa 2.1 da auditoria de segurança (`PROMPT_CORRECAO_VIVENSI.md`).

## Por que este documento existe

A auditoria pediu "consolidar pipelines de WhatsApp". A leitura do código mostrou que **não são dois pipelines da mesma coisa** — são dois módulos com modelos, schema e fluxos diferentes que conviveram historicamente:

| | Pipeline legado | Pipeline protegido |
|---|---|---|
| Módulo | "Opt-in Campanhas" (`/whatsapp/optin/campanhas`) | "Broadcast" |
| Campanha | `App\Models\Campanha` (PT) | `App\Models\BroadcastCampaign` (EN) |
| Contato | `App\Models\ContatoWhatsapp` (`opt_in`/`opt_out` boolean) | `App\Models\WhatsappChat` (timestamps + blacklist + histórico) |
| Service | `App\Services\CampanhaService` | (lógica embarcada no job) |
| Job | `App\Jobs\EnviarMensagemCampanhaJob` | `App\Jobs\ProcessBroadcastCampaignJob` |
| Entry HTTP | `Admin\CampanhaController@disparar` | (rotas de broadcast) |
| Entry scheduler | `ProcessScheduledBroadcasts.php:80` | `ProcessScheduledBroadcasts.php:41` |
| Anti-ban | ✅ **Adicionado em 2026-06-11** (Caminho B) | ✅ Nativo desde a criação |

## O que já foi feito (Caminho B — protege o legado sem migrar)

Commit `<HASH_DESTE_COMMIT>` — refatoração cirúrgica do `EnviarMensagemCampanhaJob` aplicando `AntiBanManager`:

- Janela horária + limite diário/horário verificados antes de cada envio
- Simulação de digitação humana (presence "composing") antes do envio
- Detecção de sinal de ban → instância restrita 24h
- Circuit breaker em 5 erros consecutivos → status `pausada`
- Pré-flight contra URLs encurtadas → cancela antes de iniciar
- `sleep` cego substituído por delay orgânico mín. 5s + pausa de 3-5 min a cada 30 mensagens
- Novo status `pausada` no enum de `campanhas` (migration `2026_06_11_120000_add_pausada_to_campanhas_status_enum`)

**Resultado:** o dano ativo da auditoria está mitigado. Campanhas legadas agora rodam sob a mesma política antiban do pipeline protegido. **A duplicação de modelo permanece**.

## O que falta (Caminho A — consolidação real)

### Decisões de produto necessárias antes de codar

1. **Cutover ou sunset?** Migrar todas as `Campanha` existentes para `BroadcastCampaign` em um cutover único, ou marcar o módulo legado como "leitura apenas" e parar de aceitar novas campanhas nele?
2. **O que vira da `ContatoWhatsapp` que não tem `WhatsappChat` correspondente?** Criar `WhatsappChat` órfão (sem histórico) ou descartar?
3. **Como comunicar a mudança aos clientes que usam o módulo "Opt-in Campanhas" hoje?** Janela de aviso? Migração assistida? Vídeo curto?
4. **A UI nova (`broadcast`) atende à mesma necessidade da UI legada?** Conferir paridade: agendamento, retomada manual, duplicar como rascunho, intervalo entre envios.
5. **Há clientes em contratos antigos que dependem do schema legado em integrações externas?** (Webhooks, exports, relatórios)

### Trabalho técnico (estimativa: 4–6 semanas)

**Fase 1 — Mapeamento**
- [ ] Diff dos campos: `Campanha` → `BroadcastCampaign`
  - `titulo` → ? (não existe no broadcast — adicionar ou descartar?)
  - `mensagem` → `message`
  - `intervalo_segundos` → `cadence`
  - `agendada_para` → `scheduled_at`
  - `total_contatos` → `actual_recipients`
  - `total_enviados` → `total_sent`
  - `total_falhas` → `total_failed`
  - Status: `rascunho`/`agendada`/`processando`/`pausada`/`concluida`/`cancelada` → `scheduled`/`queued`/`processing`/`paused`/`completed`/`failed`
- [ ] Diff dos campos: `ContatoWhatsapp` → `WhatsappChat`
  - `telefone` → `wa_id`
  - `nome` → `name` (ou `display_name`)
  - `opt_in` (bool) → `opt_in_at` (timestamp)
  - `opt_out` (bool) → `opt_out_at` (timestamp)

**Fase 2 — Backfill**
- [ ] Migration `migrate_legacy_campanhas`: para cada `Campanha`, cria `BroadcastCampaign` equivalente, mantém `legacy_campanha_id` para rastreio
- [ ] Migration `migrate_legacy_contatos`: para cada `ContatoWhatsapp` sem `WhatsappChat`, cria um — com `name`, `wa_id`, `opt_in_at = created_at` se `opt_in=true`, `opt_out_at = updated_at` se `opt_out=true`
- [ ] Validação: contagem antes vs depois, sample manual de 10 registros

**Fase 3 — UI**
- [ ] Apontar `Admin\CampanhaController@disparar` para `ProcessBroadcastCampaignJob` via `BroadcastCampaign` correspondente
- [ ] Adaptar a view `admin/whatsapp/optin/campanhas` para ler de `BroadcastCampaign`, ou redirecionar para a UI de broadcast
- [ ] Adicionar botão "Reativar" para campanhas em `paused` (atualmente requer Tinker)
- [ ] Manter rotas `/whatsapp/optin/*` funcionando durante a transição (redirect, não quebrar)

**Fase 4 — Sunset**
- [ ] Remover `ProcessScheduledBroadcasts.php` linha 50-83 (o segundo loop, do pipeline legado)
- [ ] Marcar `Campanha`, `ContatoWhatsapp`, `CampanhaService`, `EnviarMensagemCampanhaJob` como `@deprecated`
- [ ] Após 1 release sem usos, deletar
- [ ] Drop das tabelas `campanhas` e `contatos_whatsapp` em migration separada (com backup explícito em `storage/legacy-backups/`)

## Riscos da consolidação

- **Perda de histórico**: usuários acostumados a ver "Campanhas Opt-in" listadas separadamente podem se confundir
- **Bug de mapeamento**: cada campo mapeado errado é um cliente perdendo dado
- **Tempo morto**: durante cutover, disparo pode ficar suspenso por minutos/horas
- **Rollback caro**: depois de drop das tabelas, voltar atrás é restore de backup

## Quando reabrir este backlog

Sinais de que vale priorizar:
- Mais de 1 incidente em que cliente reportou confusão entre "Campanhas" e "Broadcasts"
- Adição de feature nova precisa ser duplicada nos dois pipelines
- Time gastando >2h/mês mantendo as duas implementações em sincronia
- Auditoria de segurança apontar a duplicação como vetor recorrente

Enquanto nenhum desses sinais aparecer, **manter o status quo** com o Caminho B já entregue é a leitura mais honesta.
