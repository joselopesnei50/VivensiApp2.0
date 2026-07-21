# Análise de Lacunas — Motor de Conformidade Contínua (Vivensi)
> Fase 3 | 2026-07-21 | Cruzamento Fase 1 (inventário) × Fase 2 (matriz regulatória)
> Escopo v1: CEBAS/Assistência Social + SUAS + MROSC

---

## Categoria 1 — Calcula HOJE, sem nenhuma alteração

| Requisito | O que já calcula | Limitação |
|---|---|---|
| CEBAS-G-010 | Cobertura de lançamentos contábeis por mês (via `transactions`) | Precisa de UI para exibir |
| CEBAS-G-006 (parcial) | DRE gerada via `dre_pdf.blade.php` | Balanço patrimonial não existe |
| CEBAS-G-007 (trigger) | Receita bruta anual (soma `transactions.amount` onde type=income) | Documento de parecer não tipificado |
| MROSC-P-004 (parcial) | `NgoGrant` existe e `TransparencyPortal` existe | Vínculo automático não implementado |
| SUAS-OP-002 (parcial) | `project_classes.max_students` (capacidade instalada) | Falta tipificação SUAS |
| SUAS-OP-003 (parcial) | `employees.position`, `work_hours_weekly`, `contract_type` | Sem mapeamento NOB-RH |

**Conclusão:** 6 requisitos parcialmente atendíveis hoje — nenhum 100% sem UI de conformidade.

---

## Categoria 2 — Atende com ajuste pequeno (1 campo ou 1 relacionamento)

| Gap | Requisito | Ajuste necessário | Esforço | Impacto |
|---|---|---|---|---|
| **G-01** | CEBAS-G-003/004/005, MROSC-P-001 | Adicionar `tipo_documento` (enum) + `valid_until` em `Attachment` | **P** | Alto |
| **G-02** | CEBAS-AS-002 (gratuidade 20%) | Adicionar `gratuito` (boolean) em `Attendance` | **P** | Alto |
| **G-03** | CEBAS-AS-003, SUAS-OP-001/002 | Adicionar `tipificacao_suas` (enum CNAS 109/2009) em `Attendance` | **P** | Alto |
| **G-04** | CEBAS-AS-001, SUAS-R-001/002 | Adicionar campos em `Tenant`: `cnas_numero`, `cnas_validade`, `cmas_numero`, `cmas_validade`, `cneas_codigo`, `cnae_principal`, `data_fundacao`, `area_atuacao_cebas` | **P** | Alto |
| **G-05** | MROSC-P-002 | Adicionar `modalidade` (fomento/colaboração), `numero_instrumento`, `orgao_concedente_codigo` em `NgoGrant` | **P** | Médio |
| **G-06** | MROSC-P-006 | Adicionar `elegivel_mrosc` (boolean) + garantir `ngo_grant_id` em `Transaction` | **P** | Alto |
| **G-07** | MROSC-P-007 | Adicionar `executed_value` em `ProjectStage` | **P** | Médio |
| **G-08** | CEBAS-AS-005, SUAS-OP-003 | Adicionar `categoria_profissional` (enum: assistente_social/psicologo/pedagogo/outros) em `Employee` | **P** | Médio |
| **G-09** | CEBAS-G-001 | CNPJ API (gov.br/conecta): auto-preencher `data_fundacao`, CNAE, razão social, situação | **M** | Médio |

---

## Categoria 3 — Exige novo domínio de dados

| Gap | Descrição | Esforço | Impacto |
|---|---|---|---|
| **G-10** | **Catálogo de requisitos legais** (`RequisitoLegal`): tabela global, versionada por vigência. Permite que uma avaliação feita em 2026 reflita a regra de 2026 mesmo que a lei mude depois. | **M** | Alto |
| **G-11** | **Ciclo de certificação** (`CicloConformidade`): janela CEBAS de 3 ou 5 anos por tenant, com enquadramento, datas e status. | **M** | Alto |
| **G-12** | **Avaliação por requisito** (`AvaliacaoRequisito`): resultado (verde/amarelo/vermelho/não-aplicável) por tenant × ciclo × requisito, com timestamp e quem avaliou. | **M** | Alto |
| **G-13** | **Evidência polimórfica** (`Evidencia`): vínculo entre uma avaliação e sua prova (registro do sistema, documento, declaração). | **M** | Alto |
| **G-14** | **Snapshot de conformidade** (`SnapshotConformidade`): foto diária/semanal do índice geral por tenant, para gráfico de evolução. | **P** | Médio |
| **G-15** | **Gerador de relatório RMA**: command/job que agrupa `attendances` por mês e tipificação e exporta em formato compatível com Rede SUAS Web. | **M** | Alto |
| **G-16** | **Gerador de dossiê CEBAS/MROSC**: PDF compilado com todas as evidências do ciclo, formatado para protocolo no ministério. | **G** | Alto |
| **G-17** | **Dashboard de conformidade**: painel Filament com semáforo por eixo, contagem regressiva do ciclo, lista de pendências ordenada por risco. | **M** | Alto |
| **G-18** | **Alertas de vencimento de documentos**: job agendado que notifica por WhatsApp/email quando certidão vence em X dias. | **P** | Alto |

---

## Categoria 4 — Não automatizável (sempre entrada manual do usuário)

| Requisito | Por quê não automatiza | Solução no sistema |
|---|---|---|
| CEBAS-G-002 | Análise jurídica das cláusulas do estatuto | Upload do estatuto + checklist de confirmação humana |
| CEBAS-G-008 | Declaração de aplicação de recursos no Brasil | Formulário de declaração com assinatura digital do responsável |
| CEBAS-G-009 | Verificação de não remuneração de dirigentes | Checklist + upload de ata de eleição da diretoria |
| CEBAS-AS-001 | Inscrição no CNAS é externa (sem API) | Campo manual com upload da certidão |
| CEBAS-AS-007 | CNAE ativo na Receita Federal (sem API autenticada para entidades) | CNPJ API (pública) retorna CNAE — pode automatizar parcialmente |
| SUAS-R-001/002 | Inscrição CMAS/CNEAS é externa | Campo manual com upload da certidão |
| SUAS-OP-004/005 | Participação em conselhos é presencial | Upload de atas + confirmação manual |
| MROSC-P-003 | Conta corrente específica é informação bancária | Campo declaratório com número da conta |
| Certidões negativas (G-003/004/005) | Emitidas externamente no gov.br | Upload tipificado + alerta de vencimento automático |

---

## Corte recomendado — Máximo valor com mínimo esforço

### Por que começar por CEBAS/AS + documento de certidão?

- **Maior risco existencial para o cliente:** CEBAS perdido = perda de isenção previdenciária e possível devolução de valores. Nenhum outro módulo tem esse impacto financeiro.
- **Requisito mais simples de calcular que gera mais valor:** percentual de gratuidade (G-02) é 1 campo em `Attendance` e 1 cálculo. Sem ele, o cliente não sabe se vai perder o CEBAS.
- **Certidões (G-01) desbloqueiam quase tudo:** CEBAS, MROSC e SUAS exigem as mesmas certidões. Um único ajuste em `Attachment` serve para os 3 eixos.

### Fatias recomendadas (Fase 5 vai detalhar)

| Fatia | Conteúdo principal | Gaps cobertos | Esforço total | Valor entregue |
|---|---|---|---|---|
| **F1 — Fundação** | Campos em Tenant + Attachment (tipo+validade) + `gratuito` em Attendance + `tipificacao_suas` + CNPJ API | G-01, G-02, G-03, G-04, G-09 | 2 semanas | Cliente já vê % de gratuidade e certidões vencendo |
| **F2 — Motor** | RequisitoLegal + CicloConformidade + AvaliacaoRequisito + Evidencia + Dashboard semáforo | G-10, G-11, G-12, G-13, G-17 | 3 semanas | Painel "você está X% conforme" funcional |
| **F3 — Documentos e Alertas** | Versionamento de documentos + alertas de vencimento + checklist declaratórios | G-14, G-18 + Cat.4 | 1 semana | Nunca mais perde certidão vencida |
| **F4 — MROSC e RMA** | Campos em NgoGrant + Transaction + gerador RMA + dossiê PDF | G-05, G-06, G-07, G-15, G-16 | 3 semanas | Prestação de contas de convênio + relatório para Rede SUAS |
| **F5 — IA e Expansão** | BruceIA explicando pendências + CEBAS Saúde/Educação | futuro | 2+ semanas | Diferencial premium |

**Total v1 (F1 a F4): ~9 semanas de desenvolvimento.**
