# Roadmap — Motor de Conformidade Contínua (Vivensi)
> Fase 5 | 2026-07-21 | Fatias verticais entregáveis com valor real ao final de cada uma

---

## Visão geral

| Fatia | Nome | Dias | Dependência | Risco |
|---|---|---|---|---|
| F1 | Fundação de dados | 10 | nenhuma | Baixo |
| F2 | Motor + Dashboard | 15 | F1 | Médio |
| F3 | Documentos e Alertas | 5 | F1 + F2 | Baixo |
| F4 | MROSC + RMA | 15 | F1 + F2 + F3 | Médio |
| F5 | IA e Expansão | 10+ | F1–F4 | Médio |
| **TOTAL v1** | **F1–F4** | **45 dias** | | |

---

## Fatia 1 — Fundação de dados
**Duração:** 10 dias de desenvolvimento
**Valor entregue:** O cliente já vê o percentual de gratuidade, as certidões com prazo de vencimento, e o sistema já tem todos os dados necessários para o motor calcular.

### Escopo
1. **6 migrations** — campos novos em tabelas existentes:
   - `attendances`: `gratuito` (boolean) + `tipificacao_suas` (enum)
   - `attachments`: `tipo_documento` (enum) + `valid_until` (date) + `versao` (int) + `substituido_por_id`
   - `tenants`: `cnas_numero`, `cnas_validade`, `cmas_numero`, `cmas_validade`, `cneas_codigo`, `cnae_principal`, `data_fundacao`, `area_atuacao_cebas`, `receita_bruta_anual_ref`
   - `employees`: `categoria_profissional` (enum)
   - `transactions`: `elegivel_mrosc` (boolean) + `fonte_recurso` (enum)
   - `ngo_grants`: `modalidade` + `numero_instrumento` + `orgao_concedente_codigo`
   - `project_stages`: `executed_value` (decimal)

2. **2 seeders** — catálogo global:
   - `RequisitoLegalSeeder` — 29 requisitos (CEBAS/AS + SUAS + MROSC)
   - `RegraAvaliacaoSeeder` — regras de cálculo para cada requisito

3. **1 service** — `CnpjApiService`:
   - Consulta API pública de CNPJ (gov.br/conecta)
   - Auto-preenche `tenants`: razão social, CNAE, data abertura, situação, endereço
   - Chamado no formulário de configurações do tenant ao digitar o CNPJ

4. **UI — ajustes em telas existentes:**
   - Formulário de `Attendance`: adicionar checkbox "Atendimento gratuito" + select "Tipificação SUAS"
   - Upload de `Attachment` (qualquer lugar): adicionar select "Tipo do documento" + campo "Válido até"
   - Configurações do Tenant: adicionar seção "Dados de Conformidade" (CNAS, CMAS, CNEAS, CNPJ auto-fill)
   - Formulário de `Employee`: adicionar select "Categoria profissional"
   - Formulário de `Transaction`: adicionar select "Fonte de recurso" + checkbox "Elegível MROSC"
   - Formulário de `NgoGrant`: adicionar campos de modalidade e instrumento

### Arquivos afetados
```
database/migrations/            (6 novos arquivos)
database/seeders/RequisitoLegalSeeder.php      (novo)
database/seeders/RegraAvaliacaoSeeder.php      (novo)
app/Models/Attendance.php       (+ fillable, cast)
app/Models/Attachment.php       (+ fillable, cast, enum)
app/Models/Tenant.php           (+ fillable, cast)
app/Models/Employee.php         (+ fillable, cast)
app/Models/Transaction.php      (+ fillable, cast)
app/Models/NgoGrant.php         (+ fillable, cast)
app/Models/ProjectStage.php     (+ fillable)
app/Services/CnpjApiService.php (novo)
app/Models/RequisitoLegal.php   (novo)
app/Models/RegraAvaliacao.php   (novo)
resources/views/ngo/beneficiaries/ (attendance form)
resources/views/ngo/hr/            (employee form)
resources/views/components/attachment-upload.blade.php (se existir, ajuste)
tests/Feature/Conformidade/FundacaoDadosTest.php (novo)
```

### Testes mínimos
- Campo `gratuito` persiste e é recuperável
- Campo `tipo_documento` + `valid_until` persistem
- `CnpjApiService` retorna dados esperados (mock da API)
- Seeder cria 29 requisitos sem erro

### Dependências externas
- API pública de CNPJ (gov.br) — sem autenticação, pode cair; implementar fallback gracioso (campo manual)

### Risco
**Baixo** — todas as mudanças são aditivas. Nenhuma coluna existente é alterada. Deploy seguro com migration forward-only.

---

## Fatia 2 — Motor + Dashboard
**Duração:** 15 dias de desenvolvimento
**Valor entregue:** Painel de conformidade funcional com semáforo verde/amarelo/vermelho por eixo, índice percentual, e lista de pendências ordenada por risco.

### Escopo
1. **4 migrations** — novo domínio:
   - `ciclos_conformidade`
   - `avaliacoes_requisito`
   - `evidencias`
   - `snapshots_conformidade`

2. **4 models** com BelongsToTenant:
   - `CicloConformidade`, `AvaliacaoRequisito`, `Evidencia`, `SnapshotConformidade`

3. **`ComplianceCalculationService`** — motor de cálculo:
   - `calcularTipoA(Tenant, RequisitoLegal): float` — executa a fórmula da `RegraAvaliacao`
   - `avaliarRequisito(Tenant, RequisitoLegal, CicloConformidade): AvaliacaoRequisito`
   - `calcularIndice(Tenant, CicloConformidade): array` — agrega resultados por eixo
   - Cálculos Tipo A implementados na F2:
     - Percentual de gratuidade (CEBAS-AS-002): `attendances` com `gratuito=true` / total receita serviços
     - Cobertura contábil mensal (CEBAS-G-010): meses com lançamentos / meses do ciclo
     - Cumprimento de metas (MROSC-P-007): `project_stages.executed_value` / `planned_value`
     - Comprovantes de despesa MROSC (MROSC-P-006): transações com attachment / total elegível
     - Atendimentos tipificados SUAS (SUAS-OP-002): atendimentos com tipificacao / total

4. **`RecalcularConformidadeJob`**:
   - Queue: `default`
   - Recalcula Tipo A para o tenant específico
   - Invalida cache do dashboard (Redis)

5. **Event Listeners:**
   - `AttendanceObserver` → dispara `RecalcularConformidadeJob` (throttled: no máximo 1 job na fila por tenant)
   - `TransactionObserver` → idem
   - `AttachmentObserver` → idem

6. **Dashboard Filament** (`/ngo/conformidade`):
   - Widget semáforo (3 eixos)
   - Widget contagem regressiva do ciclo
   - Lista de pendências (ordenada: vermelho > amarelo, risco alto > médio > baixo)
   - Widget índice geral com gauge visual
   - Página de detalhamento por eixo

7. **`SnapshotConformidadeJob`** (agendado domingo 03:00):
   - Itera tenants ativos
   - Despacha job individual por tenant na fila

### Arquivos afetados
```
database/migrations/            (4 novos)
app/Models/CicloConformidade.php         (novo)
app/Models/AvaliacaoRequisito.php        (novo, Auditable)
app/Models/Evidencia.php                 (novo)
app/Models/SnapshotConformidade.php      (novo)
app/Services/ComplianceCalculationService.php  (novo)
app/Jobs/RecalcularConformidadeJob.php   (novo)
app/Jobs/SnapshotConformidadeJob.php     (novo)
app/Observers/AttendanceObserver.php     (novo)
app/Observers/TransactionObserver.php    (novo)
app/Observers/AttachmentObserver.php     (novo)
app/Console/Kernel.php                   (+ schedule SnapshotJob)
app/Providers/EventServiceProvider.php   (+ observers)
resources/views/ngo/conformidade/        (novo diretório — dashboard, detalhamento)
tests/Feature/Conformidade/MotorCalculoTest.php  (novo)
tests/Feature/Conformidade/DashboardTest.php     (novo)
```

### Testes mínimos
- Percentual de gratuidade calculado corretamente com dados reais
- Requisito vermelho quando < threshold, verde quando >= threshold
- Dashboard renderiza sem erro para tenant sem dados
- Observer dispara job após criar Attendance

### Risco
**Médio** — lógica de cálculo nova, precisa de testes unitários por fórmula. Complexidade controlada porque cada fórmula é isolada em método.

---

## Fatia 3 — Documentos e Alertas
**Duração:** 5 dias de desenvolvimento
**Valor entregue:** O cliente nunca mais perde uma certidão vencida. Declarações (Tipo C) com assinatura e auditoria.

### Escopo
1. **Central de documentos** — nova view listando todos os `Attachment` com `tipo_documento != null`:
   - Filtros por tipo, status de validade (válido/vencendo/vencido)
   - Badge de alerta para documentos vencendo em < 30 dias
   - Upload inline de nova versão (incrementa `versao`, seta `substituido_por_id` no anterior)

2. **`AlertaDocumentoVencendoJob`** (agendado diariamente 08:00):
   - Varre `attachments` com `valid_until` nos próximos 60 dias
   - Respeita silêncio de 7 dias (flag `alerta_enviado_em` no attachment)
   - Envia WhatsApp ao admin + email com link direto para upload

3. **Checklist declaratório (Tipo C)**:
   - Para cada requisito Tipo C, exibe formulário de declaração
   - Campo de texto confirmando a declaração + checkbox de ciência
   - Persiste `AvaliacaoRequisito` com `avaliado_por`, `avaliado_em`, texto da declaração em `observacoes`
   - Imutável: nova declaração cria novo registro, não edita o anterior

4. **Avaliação manual (Tipo B)**:
   - Para certidões carregadas, o sistema verifica automaticamente `valid_until > today`
   - Se documento com `tipo_documento` adequado e válido existe → avaliação verde automática

### Arquivos afetados
```
database/migrations/            (+ alerta_enviado_em em attachments)
app/Jobs/AlertaDocumentoVencendoJob.php  (novo)
app/Console/Kernel.php                   (+ schedule AlertaJob)
resources/views/ngo/conformidade/documentos.blade.php  (novo)
resources/views/ngo/conformidade/declaracao.blade.php  (novo)
tests/Feature/Conformidade/AlertaDocumentoTest.php     (novo)
```

### Risco
**Baixo** — lógica simples de datas. Risco principal: falso positivo de alerta. Mitigado pelo silêncio de 7 dias.

---

## Fatia 4 — MROSC + RMA
**Duração:** 15 dias de desenvolvimento
**Valor entregue:** A entidade gera o relatório de atendimentos no formato esperado pelo Rede SUAS Web e gera o PDF de prestação de contas para convênios MROSC, sem precisar montar manualmente em planilha.

### Escopo
1. **Gerador de RMA** (Registro Mensal de Atendimentos):
   - Command: `php artisan conformidade:gerar-rma {tenant_id} {mes} {ano}`
   - Agrupa `attendances` por `tipificacao_suas`, gênero, faixa etária (de `beneficiaries.birth_date`)
   - Exporta em formato CSV estruturado conforme layout Rede SUAS Web
   - Disponível para download no dashboard (botão "Gerar RMA de [mês]")
   - Persiste protocolo de download em `AvaliacaoRequisito` (evidência de que foi gerado)

2. **PDF de Prestação de Contas MROSC**:
   - Template `resources/views/pdf/conformidade/prestacao_contas_mrosc.blade.php`
   - Dados: `NgoGrant` + `Project` + `ProjectStage` (metas × realizado) + `Transaction` (receitas/despesas elegíveis) + `Attachment` (NFs)
   - Seções: identificação do instrumento, plano de trabalho × execução, demonstrativo financeiro, declaração do responsável

3. **PDF de Dossiê CEBAS**:
   - Template `resources/views/pdf/conformidade/dossie_cebas.blade.php`
   - Compilado de todas as avaliações verdes do ciclo com evidências
   - Capa + índice + seções por requisito + anexos referenciados

4. **Publicação automática de convênios no portal de transparência**:
   - Observer em `NgoGrant`: ao salvar com `modalidade` preenchida, verifica se `TransparencyPortal` existe e publica automaticamente dados básicos do convênio
   - Avaliação de MROSC-P-004 recalculada automaticamente

5. **Cálculo de desvio de metas** (MROSC-P-007):
   - `ProjectStage.executed_value` vs `planned_value`
   - Alerta quando desvio > 25%: notifica admin via WhatsApp

### Arquivos afetados
```
app/Console/Commands/GerarRmaCommand.php   (novo)
app/Jobs/GerarDossieJob.php                (novo)
app/Observers/NgoGrantObserver.php         (novo)
app/Console/Kernel.php                     (+ register command)
resources/views/pdf/conformidade/          (novo diretório)
  prestacao_contas_mrosc.blade.php
  dossie_cebas.blade.php
  rma_relatorio.blade.php
resources/views/ngo/conformidade/          (+ botões de geração)
tests/Feature/Conformidade/RmaGeneratorTest.php   (novo)
tests/Feature/Conformidade/DossieTest.php          (novo)
```

### Risco
**Médio** — layout do RMA pode mudar por portaria MDS. Mitigar: separar a lógica de montagem do CSV em classe própria (`RmaBuilder`) fácil de atualizar. PDF de dossiê é complexo mas usa dompdf já instalado.

---

## Fatia 5 — IA e Expansão (pós-v1)
**Duração:** 10+ dias
**Valor entregue:** BruceIA explica pendências em linguagem simples e sugere ações. Expansão para CEBAS/Saúde e CEBAS/Educação via seeder (sem código novo).

### Escopo
1. **BruceIA — contexto de conformidade**:
   - Novo contexto `compliance` em `BruceAiService`
   - Prompt do sistema inclui: eixo, índice atual, top 3 pendências, ciclo restante
   - Exemplos de perguntas respondíveis: "Por que estou em amarelo no CEBAS?", "O que preciso fazer para renovar em 2028?"
   - BruceIA nunca altera `AvaliacaoRequisito` — apenas explica e orienta
   - Redigir minutas de relatório de atividades a partir dos dados do sistema

2. **Expansão do catálogo** (seeder incremental):
   - Adicionar requisitos CEBAS/Saúde e CEBAS/Educação ao `RequisitoLegalSeeder`
   - Zero mudança de código — a arquitetura já suporta

3. **Integração Rede SUAS (futuro)**:
   - Monitorar se REDE SUAS disponibiliza API — atualmente não disponível
   - Estrutura de RMA já pronta para integrar quando/se disponível

### Arquivos afetados
```
app/Services/BruceAiService.php   (+ contexto compliance)
database/seeders/RequisitoLegalSeeder.php (+ requisitos saude/educacao)
```

---

## Cronograma sugerido

```
Semana 1-2:   Fatia 1 — Fundação de dados
              [Deploy incremental no VPS ao final]

Semana 3-5:   Fatia 2 — Motor + Dashboard
              [Deploy ao final — painel básico já funcional]

Semana 6:     Fatia 3 — Documentos e Alertas
              [Deploy ao final]

Semana 7-9:   Fatia 4 — MROSC + RMA
              [Deploy ao final — v1 completa]

Semana 10+:   Fatia 5 — IA e Expansão
              [Pós-lançamento]
```

---

## O que precisa acontecer FORA do código (em paralelo)

| Ação | Responsável | Quando |
|---|---|---|
| Confirmar percentual de gratuidade CEBAS/AS com advogado | Vivensi | Antes de F2 ir ao ar |
| Confirmar prazos de validade das inscrições (CNAS/CMAS) | Vivensi | Antes de F3 |
| Revisar catálogo de 29 requisitos (02-matriz-regulatoria.md) | Advogado parceiro | Paralelo à F1 |
| Definir copy de aviso legal no dashboard ("o sistema informa, não garante") | Produto | Antes de F2 |
| Testar layout do RMA com usuário real no Rede SUAS Web | QA / cliente beta | Durante F4 |
