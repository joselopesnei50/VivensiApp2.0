# Matriz Regulatória — Motor de Conformidade Contínua (Vivensi)
> Fase 2 do prompt-analise-motor-conformidade.md | 2026-07-21
> ⚠️ RASCUNHO TÉCNICO — requer revisão jurídica antes de virar produto.

---

## Legenda de Tipos
- **Tipo A — Calculável:** derivado de dados estruturados do sistema
- **Tipo B — Documental:** exige arquivo válido e não vencido
- **Tipo C — Declaratório:** exige confirmação humana com responsabilidade legal

---

## EIXO 1 — CEBAS (Certificação de Entidade Beneficente de Assistência Social)
### Base: LC 187/2021 · Decreto 11.791/2023 · Portaria MDS 952/2023 · Portaria MDS 962/2024

---

### 1.1 Requisitos Gerais (todas as áreas)

---

**ID:** CEBAS-G-001
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, I
**Enunciado:** A entidade deve ser pessoa jurídica de direito privado, sem fins lucrativos, constituída e em funcionamento há pelo menos 12 meses antes do pedido.
**Verificável:** parcial
**Dado necessário:** `tenants.created_at` ou campo `data_fundacao`; `tenants.type` deve ser 'ngo'
**Já temos?** parcial — `created_at` existe, mas não representa data de fundação jurídica; sem campo `data_fundacao` ou `cnpj_abertura_date`
**Periodicidade:** por ciclo de certificação (na renovação)
**Evidência:** Tipo B — CNPJ ativo + Contrato Social/Estatuto com data de constituição
**Risco se falhar:** alto
**Tipo:** B + C

---

**ID:** CEBAS-G-002
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, II e art. 4º
**Enunciado:** O estatuto deve conter cláusulas obrigatórias: vedação de distribuição de patrimônio ou rendas; destinação do patrimônio a entidade congênere em caso de dissolução; não remuneração de dirigentes (ou remuneração permitida conforme regras da lei).
**Verificável:** não (exige análise jurídica do texto do estatuto)
**Dado necessário:** Documento do estatuto vigente (campo tipo + validade no sistema de documentos)
**Já temos?** não — `Attachment` armazena arquivo mas sem campo `tipo_documento` nem análise de cláusulas
**Periodicidade:** por ciclo de certificação; atenção a alterações estatutárias
**Evidência:** Tipo B — Estatuto social registrado em cartório + ata de aprovação
**Risco se falhar:** alto
**Tipo:** B + C

---

**ID:** CEBAS-G-003
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, V [⚠️ verificar artigo exato]
**Enunciado:** A entidade deve estar em dia com suas obrigações previdenciárias (INSS). Exige Certidão Negativa de Débitos Previdenciários (CND/INSS) válida.
**Verificável:** não (certidão emitida externamente pelo gov.br)
**Dado necessário:** `attachments` com `tipo_documento = 'cnd_inss'` e `valid_until` válido
**Já temos?** não — Attachment sem `tipo_documento` e sem `valid_until`
**Periodicidade:** anual (validade da CND é 180 dias) — alerta de vencimento crítico
**Evidência:** Tipo B — CND/INSS emitida no portal gov.br
**Risco se falhar:** alto
**Tipo:** B

---

**ID:** CEBAS-G-004
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, V [⚠️ verificar artigo exato]
**Enunciado:** Certidão de Regularidade do FGTS (CRF) válida, emitida pela Caixa Econômica Federal.
**Verificável:** não (certidão externa)
**Dado necessário:** `attachments` com `tipo_documento = 'crf_fgts'` e `valid_until` válido
**Já temos?** não
**Periodicidade:** validade 30 dias — renovação frequente
**Evidência:** Tipo B — CRF emitida pelo FGTS Digital / Caixa
**Risco se falhar:** alto
**Tipo:** B

---

**ID:** CEBAS-G-005
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, V [⚠️ verificar artigo exato]
**Enunciado:** Certidão Conjunta Negativa de Débitos relativos a Tributos Federais e à Dívida Ativa da União (Receita Federal + PGFN).
**Verificável:** não (certidão externa)
**Dado necessário:** `attachments` com `tipo_documento = 'cnd_federal'` e `valid_until` válido
**Já temos?** não
**Periodicidade:** validade 180 dias — alerta quando restarem 30 dias
**Evidência:** Tipo B — CND Federal emitida no portal gov.br
**Risco se falhar:** alto
**Tipo:** B

---

**ID:** CEBAS-G-006
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, III; Decreto 11.791/2023
**Enunciado:** A entidade deve apresentar demonstrações contábeis anuais elaboradas conforme as normas do CFC (NBC TG), acompanhadas de notas explicativas.
**Verificável:** parcial
**Dado necessário:** `attachments` com `tipo_documento = 'balanco_patrimonial'`, `tipo_documento = 'dre'` + `exercicio_referencia`; DRE calculável via `transactions`
**Já temos?** parcial — DRE pode ser gerada pelo sistema (`resources/views/pdf/ngo/reports/dre_pdf.blade.php`); balanço patrimonial não gerado automaticamente
**Periodicidade:** anual (exercício anterior)
**Evidência:** Tipo B — Demonstrações contábeis assinadas por contador CRC + Tipo C (declaração de responsabilidade)
**Risco se falhar:** alto
**Tipo:** A (DRE calculável) + B (documento assinado)

---

**ID:** CEBAS-G-007
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, III; Decreto 11.791/2023 [⚠️ verificar threshold de receita para obrigatoriedade de auditoria independente]
**Enunciado:** Entidades com receita bruta anual acima do limite definido em regulamento devem apresentar demonstrações contábeis com parecer de auditor independente registrado no CRC.
**Verificável:** parcial
**Dado necessário:** `transactions` (soma income do exercício) para calcular receita bruta; `attachments` com `tipo_documento = 'parecer_auditoria'`
**Já temos?** parcial — receita bruta calculável via `transactions`; documento de parecer não tipificado
**Periodicidade:** anual
**Evidência:** Tipo A (trigger: receita > limite) + Tipo B (parecer do auditor)
**Risco se falhar:** alto
**Tipo:** A + B

---

**ID:** CEBAS-G-008
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, IV [⚠️ verificar artigo]
**Enunciado:** A entidade deve aplicar suas rendas, recursos e eventual resultado operacional integralmente no território brasileiro, na manutenção e desenvolvimento de seus objetivos institucionais.
**Verificável:** não (juízo sobre natureza das despesas)
**Dado necessário:** `transactions.type = 'expense'` discriminadas por finalidade + declaração responsável
**Já temos?** não — sem campo `finalidade_institucional` em Transaction; sem flag `aplicado_no_brasil`
**Periodicidade:** contínua (verificação anual)
**Evidência:** Tipo C — Declaração assinada pelo representante legal
**Risco se falhar:** médio
**Tipo:** C

---

**ID:** CEBAS-G-009
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º [⚠️ verificar artigo sobre remuneração de dirigentes]
**Enunciado:** Dirigentes não podem receber remuneração da própria entidade pelos serviços prestados à entidade (ou, se recebem, nos termos do art. 34 da LC 187/2021 — apenas para entidades de médio e grande porte com aprovação em assembleia).
**Verificável:** não (exige verificação da folha de pagamento vs. cargo de dirigente)
**Dado necessário:** `employees.position` cruzado com `tenants.dirigentes` (campo inexistente); folha de pagamento
**Já temos?** não — sem modelo de dirigentes; Employee não diferencia cargo x mandato eletivo
**Periodicidade:** contínua
**Evidência:** Tipo C — Declaração + ata de assembleia (se remunerado)
**Risco se falhar:** alto
**Tipo:** C + B (ata)

---

**ID:** CEBAS-G-010
**Eixo:** CEBAS / Geral
**Base legal:** LC 187/2021, art. 3º, VI [⚠️ verificar]
**Enunciado:** A entidade deve manter escrituração contábil regular, com registro de todas as receitas e despesas.
**Verificável:** sim
**Dado necessário:** `transactions` — verificar se há lançamentos para todos os meses do período; sem lacunas temporais
**Já temos?** sim — `transactions` com date, type, category, amount; relatório DRE já existe
**Periodicidade:** contínua (mensalmente verificável)
**Evidência:** Tipo A — cobertura de lançamentos contábeis por mês
**Risco se falhar:** médio
**Tipo:** A

---

### 1.2 CEBAS — Assistência Social

---

**ID:** CEBAS-AS-001
**Eixo:** CEBAS / Assistência Social
**Base legal:** LC 187/2021, art. 13; Resolução CNAS 021/2016 [⚠️ verificar resolução vigente]
**Enunciado:** A entidade deve estar inscrita no Conselho Municipal ou Estadual de Assistência Social do município sede e, para o CEBAS federal, no CNAS (Conselho Nacional de Assistência Social).
**Verificável:** não (inscrição verificada externamente no CNEAS)
**Dado necessário:** `tenants.cnas_inscricao_numero`, `tenants.cnas_inscricao_validade`, `tenants.conselho_municipal_inscricao`
**Já temos?** não — nenhum desses campos existe no model Tenant
**Periodicidade:** por ciclo de certificação; inscrição tem validade (normalmente 4 anos [⚠️ verificar])
**Evidência:** Tipo B — Certidão de Inscrição no CNAS / Conselho Municipal
**Risco se falhar:** alto (requisito de habilitação)
**Tipo:** B

---

**ID:** CEBAS-AS-002
**Eixo:** CEBAS / Assistência Social
**Base legal:** LC 187/2021, art. 14; Portaria MDS 952/2023 [⚠️ verificar percentual exato — acredita-se ser 20% da receita bruta de serviços]
**Enunciado:** A entidade deve comprovar que oferece serviços, programas, projetos e benefícios socioassistenciais de forma gratuita, correspondentes a pelo menos 20% da receita bruta proveniente da venda de serviços, deduzidas as vendas canceladas e os descontos incondicionais.
**Verificável:** sim (calculável)
**Dado necessário:** `transactions` (type=income, separar receita de serviços vs. doações/subvenções); `attendances` com flag `gratuito`; `transactions` com campo `isento` ou `gratuidade`
**Já temos?** não — `Attendance` sem campo `gratuito`; `Transaction` sem discriminação de receita de serviço vs. doação; sem cálculo de percentual de gratuidade
**Periodicidade:** contínua (calculado sobre o exercício — verificação mensal para alerta)
**Evidência:** Tipo A — percentual = (valor atendimentos gratuitos / receita bruta de serviços) × 100
**Risco se falhar:** alto (requisito quantitativo central do CEBAS-AS)
**Tipo:** A

---

**ID:** CEBAS-AS-003
**Eixo:** CEBAS / Assistência Social
**Base legal:** Resolução CNAS 109/2009 (Tipificação Nacional de Serviços Socioassistenciais); LC 187/2021, art. 13
**Enunciado:** Os atendimentos prestados devem ser classificáveis como serviços socioassistenciais tipificados: Proteção Social Básica (ex.: PAIF, Convivência e Fortalecimento de Vínculos) ou Especial de Média ou Alta Complexidade (ex.: PAEFI, Acolhimento Institucional).
**Verificável:** parcial
**Dado necessário:** `attendances.type` mapeado para os 9 serviços da Tipificação CNAS 109/2009; campo `tipificacao_suas` em `attendances`
**Já temos?** não — `attendance.type` é string livre, sem vínculo com a tipificação oficial
**Periodicidade:** contínua
**Evidência:** Tipo A (contagem por tipificação) + Tipo C (declaração de que os serviços seguem a tipificação)
**Risco se falhar:** alto
**Tipo:** A + C

---

**ID:** CEBAS-AS-004
**Eixo:** CEBAS / Assistência Social
**Base legal:** Portaria MDS 843/2010; Instrução Operacional MDS/SNAS 07/2009 [⚠️ verificar normativa vigente do RMA]
**Enunciado:** A entidade vinculada ao SUAS deve registrar mensalmente os atendimentos no sistema REDE SUAS (RMA — Registro Mensal de Atendimentos). O RMA é comprovação de execução dos serviços socioassistenciais.
**Verificável:** parcial
**Dado necessário:** `attendances` agrupados por mês/tipo; exportação no formato RMA; campo `rma_enviado_em` + `rma_protocolo`
**Já temos?** parcial — dados de atendimento existem; sem geração de RMA nem campo de protocolo de envio
**Periodicidade:** mensal (envio até o dia 10 do mês seguinte [⚠️ verificar prazo])
**Evidência:** Tipo A (contagem de atendimentos por mês/tipificação) + Tipo B (comprovante de envio ao REDE SUAS)
**Risco se falhar:** alto (comprovação de execução)
**Tipo:** A + B

---

**ID:** CEBAS-AS-005
**Eixo:** CEBAS / Assistência Social
**Base legal:** NOB-RH/SUAS (Resolução CNAS 269/2006 e atualizações) [⚠️ verificar se NOB-RH foi revisada]
**Enunciado:** A equipe técnica de referência deve seguir os parâmetros da NOB-RH/SUAS: psicólogo e assistente social para serviços de PSB; equipe multiprofissional mínima para serviços de PSE.
**Verificável:** parcial
**Dado necessário:** `employees` com `position` mapeado para categorias profissionais (psicólogo, assistente social, pedagogo); `employees.work_hours_weekly`; vínculo com projeto/serviço
**Já temos?** parcial — `employees.position` existe mas é string livre; sem mapeamento com exigências NOB-RH
**Periodicidade:** contínua
**Evidência:** Tipo A (funcionários com cargo adequado por serviço) + Tipo B (carteiras profissionais / CRM / CRP registrados)
**Risco se falhar:** médio
**Tipo:** A + B

---

**ID:** CEBAS-AS-006
**Eixo:** CEBAS / Assistência Social
**Base legal:** LC 187/2021, art. 20; Portaria MDS 962/2024 [⚠️ verificar artigo e portaria vigente]
**Enunciado:** A entidade deve apresentar relatório de atividades do período de certificação, descrevendo os serviços prestados, número de pessoas atendidas, recursos utilizados e resultados alcançados.
**Verificável:** parcial
**Dado necessário:** `attendances` (total atendimentos, período, tipo); `beneficiaries` (total pessoas atendidas únicas); `transactions` (total recursos); `projects` (resultados por meta)
**Já temos?** parcial — dados estão no sistema; relatório formatado para CEBAS não existe
**Periodicidade:** por ciclo de certificação (3 ou 5 anos)
**Evidência:** Tipo A (dados calculados) + Tipo B (relatório assinado e protocolado no MDS)
**Risco se falhar:** alto
**Tipo:** A + B + C

---

**ID:** CEBAS-AS-007
**Eixo:** CEBAS / Assistência Social
**Base legal:** LC 187/2021, art. 3º, I; CNPJ ativo na área de assistência social
**Enunciado:** O código CNAE principal da entidade no CNPJ deve ser de atividade de assistência social (grupo 88 — CNAE 2.3).
**Verificável:** não (dado externo da Receita Federal)
**Dado necessário:** `tenants.cnae_principal` (não existe); consulta externa ao CNPJ
**Já temos?** não
**Periodicidade:** por ciclo de certificação
**Evidência:** Tipo B — Comprovante de CNPJ com CNAE 88.xx
**Risco se falhar:** alto (requisito de habilitação)
**Tipo:** B

---

### 1.3 CEBAS — Saúde

---

**ID:** CEBAS-S-001
**Eixo:** CEBAS / Saúde
**Base legal:** LC 187/2021, arts. 28-42; Portaria MS/GM [⚠️ verificar portaria vigente de percentuais SUS]
**Enunciado:** A entidade deve comprovar que oferece serviços de saúde ao SUS, em percentual mínimo de procedimentos gratuitos definido por portaria do Ministério da Saúde (varia por tipo de serviço: internação, consulta, etc.).
**Verificável:** não (dados de produção SUS são externos — DATASUS/CNES)
**Dado necessário:** Produção SUS (BPA/APAC), registro no CNES; campo `sus_percentual_cumprido`
**Já temos?** não — Vivensi não tem módulo de saúde hoje
**Periodicidade:** mensal (competência SUS) + anual (certificação)
**Evidência:** Tipo B — Relatório de produção SUS validado pelo gestor
**Risco se falhar:** alto
**Tipo:** B
**Nota:** Baixa prioridade de implementação inicial — módulo de saúde inexistente no sistema.

---

**ID:** CEBAS-S-002
**Eixo:** CEBAS / Saúde
**Base legal:** LC 187/2021, art. 33 [⚠️ verificar]
**Enunciado:** A entidade de saúde deve estar inscrita no CNES (Cadastro Nacional de Estabelecimentos de Saúde) e com habilitação vigente.
**Verificável:** não (dado externo do CNES/MS)
**Dado necessário:** `tenants.cnes_numero`, `tenants.cnes_habilitacao_validade`
**Já temos?** não
**Periodicidade:** por ciclo de certificação
**Evidência:** Tipo B — Consulta no CNES + portaria de habilitação
**Risco se falhar:** alto
**Tipo:** B
**Nota:** Baixa prioridade inicial.

---

### 1.4 CEBAS — Educação

---

**ID:** CEBAS-ED-001
**Eixo:** CEBAS / Educação
**Base legal:** LC 187/2021, arts. 43-57; Portaria MEC [⚠️ verificar portaria vigente de bolsas]
**Enunciado:** A entidade de educação deve oferecer bolsas de estudo em percentual mínimo de alunos, calculado sobre a receita bruta de mensalidades do exercício anterior.
**Verificável:** parcial
**Dado necessário:** Módulo de matrículas com status bolsista; `transactions` de mensalidades; campo `tipo_bolsa`, `percentual_desconto`
**Já temos?** não — Vivensi não tem módulo de educação formal hoje (tem LMS mas não faturamento de mensalidades)
**Periodicidade:** anual
**Evidência:** Tipo A (percentual calculável se dados existirem) + Tipo B (atos de concessão de bolsas)
**Risco se falhar:** alto
**Tipo:** A + B
**Nota:** Baixa prioridade inicial.

---

## EIXO 2 — MROSC (Lei 13.019/2014)
### Parceria entre Administração Pública e Organizações da Sociedade Civil

---

**ID:** MROSC-P-001
**Eixo:** MROSC / Habilitação
**Base legal:** Lei 13.019/2014, art. 33 e 34
**Enunciado:** A OSC deve comprovar regularidade jurídica, fiscal e trabalhista: CNPJ ativo, certidões negativas, inscrição no CNIS ou equivalente, inexistência de sanções.
**Verificável:** não (certidões externas)
**Dado necessário:** mesmo conjunto de certidões do CEBAS-G-003/004/005 + `attachments` tipificados
**Já temos?** não — Attachment sem tipo e validade
**Periodicidade:** por parceria (antes da assinatura + durante a execução)
**Evidência:** Tipo B — CND INSS, CRF FGTS, CND Federal, Certidão de Regularidade Estadual/Municipal
**Risco se falhar:** alto
**Tipo:** B

---

**ID:** MROSC-P-002
**Eixo:** MROSC / Parceria
**Base legal:** Lei 13.019/2014, art. 22
**Enunciado:** O instrumento de parceria (Termo de Fomento ou Colaboração) deve conter Plano de Trabalho aprovado, com: identificação do objeto, metas e indicadores, cronograma de execução, previsão de receitas e despesas, forma de execução.
**Verificável:** parcial
**Dado necessário:** `ngo_grants` (title, agency, value, start_date, deadline) + `projects` vinculado + `project_stages` (metas) + `transactions` (orçamento); Plano de Trabalho em PDF versionado
**Já temos?** parcial — NgoGrant + Project + ProjectStage cobrem a estrutura; sem PDF de Plano de Trabalho gerado; sem campo `numero_instrumento`, `modalidade` (fomento/colaboração), `orgao_concedente_codigo`
**Periodicidade:** por parceria
**Evidência:** Tipo A (dados estruturados do projeto) + Tipo B (Plano de Trabalho assinado)
**Risco se falhar:** alto
**Tipo:** A + B

---

**ID:** MROSC-P-003
**Eixo:** MROSC / Execução
**Base legal:** Lei 13.019/2014, art. 48
**Enunciado:** Os recursos da parceria devem ser mantidos em conta corrente específica para o objeto, com movimentação rastreável.
**Verificável:** não (dado bancário externo)
**Dado necessário:** `ngo_grants.conta_corrente_especifica`; `transactions` com flag `origem_mrosc` + `ngo_grant_id`
**Já temos?** parcial — `transactions.ngo_grant_id` não verificado; sem campo `conta_corrente_especifica` em NgoGrant
**Periodicidade:** contínua (durante a execução)
**Evidência:** Tipo B — Extrato bancário da conta específica + Tipo C — Declaração de inexistência de outras movimentações
**Risco se falhar:** alto
**Tipo:** B + C

---

**ID:** MROSC-P-004
**Eixo:** MROSC / Transparência
**Base legal:** Lei 13.019/2014, art. 11
**Enunciado:** O instrumento de parceria, seu valor, nome da OSC, objeto e resultados devem ser publicados na internet pelo órgão público concedente. A OSC também deve manter a informação acessível em seu portal de transparência.
**Verificável:** sim
**Dado necessário:** `ngo_grants` publicados no `TransparencyPortal`; campo `publicado_transparencia_em` em NgoGrant
**Já temos?** parcial — `TransparencyPortal` existe; não há vínculo automático com `NgoGrant`
**Periodicidade:** contínua (a partir da assinatura)
**Evidência:** Tipo A — Verificar se grant está visível no portal público da entidade
**Risco se falhar:** médio
**Tipo:** A

---

**ID:** MROSC-P-005
**Eixo:** MROSC / Prestação de Contas
**Base legal:** Lei 13.019/2014, arts. 63-72; Decreto 8.726/2016
**Enunciado:** A OSC deve apresentar prestação de contas parcial (se previsto) e final: relatório de execução do objeto (comprovando o cumprimento de metas) e demonstração de receitas e despesas com documentos comprobatórios.
**Verificável:** parcial
**Dado necessário:** `projects` (metas vs. realizadas via ProjectGoal/ProjectStage); `transactions` vinculadas ao grant; `attachments` de notas fiscais por despesa
**Já temos?** parcial — estrutura existe; sem geração de relatório de prestação de contas formatado para MROSC
**Periodicidade:** por parceria (parcial: conforme cronograma; final: até 90 dias após encerramento [⚠️ verificar prazo])
**Evidência:** Tipo A (execução financeira calculável) + Tipo B (NFs e recibos anexados) + Tipo B (relatório assinado protocolado)
**Risco se falhar:** alto
**Tipo:** A + B + C

---

**ID:** MROSC-P-006
**Eixo:** MROSC / Prestação de Contas
**Base legal:** Lei 13.019/2014, art. 66
**Enunciado:** Toda despesa executada com recursos da parceria deve ter comprovante fiscal (nota fiscal, recibo) vinculado ao objeto do plano de trabalho.
**Verificável:** sim
**Dado necessário:** `transactions` com `ngo_grant_id` + `attachments` vinculados (notas fiscais); percentual de transações com comprovante
**Já temos?** parcial — `Transaction` tem `attachment_path` (um único comprovante) mas sem vínculo tipificado ao grant como "despesa elegível MROSC"
**Periodicidade:** contínua
**Evidência:** Tipo A — percentual de despesas com comprovante vinculado
**Risco se falhar:** alto
**Tipo:** A + B

---

**ID:** MROSC-P-007
**Eixo:** MROSC / Execução
**Base legal:** Lei 13.019/2014, arts. 46-49
**Enunciado:** A OSC deve cumprir as metas e indicadores pactuados no Plano de Trabalho. Desvio acima de 25% [⚠️ verificar percentual] exige justificativa e autorização do concedente.
**Verificável:** sim
**Dado necessário:** `project_stages` (planned_value vs. valor executado); `project_goals` (meta vs. realizado); percentual de desvio por meta
**Já temos?** parcial — `ProjectStage.planned_value` existe; falta `executed_value` e cálculo automático de desvio
**Periodicidade:** contínua
**Evidência:** Tipo A — desvio de meta calculado automaticamente
**Risco se falhar:** médio
**Tipo:** A

---

## EIXO 3 — SUAS (Sistema Único de Assistência Social)
### Base: LOAS (Lei 8.742/1993) · PNAS/2004 · NOB-SUAS (Resolução CNAS 33/2012) · NOB-RH/SUAS (Resolução CNAS 269/2006)

---

**ID:** SUAS-R-001
**Eixo:** SUAS / Registro
**Base legal:** LOAS, art. 9º; NOB-SUAS, art. 6º [⚠️ verificar artigos]
**Enunciado:** A entidade ou organização de assistência social deve estar inscrita no Conselho Municipal de Assistência Social (CMAS) do município onde opera para poder integrar o SUAS e acessar cofinanciamento público.
**Verificável:** não (dado externo do conselho municipal)
**Dado necessário:** `tenants.cmas_inscricao_numero`, `tenants.cmas_inscricao_validade`, `tenants.cmas_municipio`
**Já temos?** não
**Periodicidade:** por ciclo (renovação normalmente a cada 4 anos [⚠️ verificar])
**Evidência:** Tipo B — Certidão de Inscrição no CMAS
**Risco se falhar:** alto
**Tipo:** B

---

**ID:** SUAS-R-002
**Eixo:** SUAS / Registro
**Base legal:** NOB-SUAS, art. 13 [⚠️ verificar artigo do CNEAS]
**Enunciado:** A entidade deve estar registrada no CNEAS (Cadastro Nacional de Entidades de Assistência Social), mantido pelo MDS, com dados atualizados sobre os serviços oferecidos.
**Verificável:** não (dado externo do MDS)
**Dado necessário:** `tenants.cneas_codigo`, `tenants.cneas_atualizado_em`
**Já temos?** não
**Periodicidade:** cadastro permanente; atualização quando há mudança de serviços
**Evidência:** Tipo B — Comprovante de registro no CNEAS
**Risco se falhar:** alto
**Tipo:** B

---

**ID:** SUAS-OP-001
**Eixo:** SUAS / Operação Mensal
**Base legal:** Portaria MDS 843/2010 [⚠️ verificar normativa vigente]; Instrução Operacional SNAS/MDS
**Enunciado:** A entidade com vínculo SUAS deve preencher e enviar o RMA (Registro Mensal de Atendimentos) pelo sistema Rede SUAS Web até o dia 10 do mês subsequente, detalhando atendimentos por tipificação, perfil dos usuários e capacidade instalada.
**Verificável:** sim
**Dado necessário:** `attendances` agrupados por mês, `tipificacao_suas`, `beneficiaries` (perfil: idade, sexo, família), `project_classes` (capacidade); campo `rma_enviado_em` + `rma_protocolo_rede_suas`
**Já temos?** parcial — dados de atendimento existem; sem tipificação SUAS nos atendimentos; sem campos de envio RMA; sem geração do arquivo no formato Rede SUAS
**Periodicidade:** mensal (até dia 10)
**Evidência:** Tipo A (geração do relatório a partir do sistema) + Tipo B (protocolo de envio Rede SUAS)
**Risco se falhar:** alto (pode resultar em descredenciamento do SUAS)
**Tipo:** A + B

---

**ID:** SUAS-OP-002
**Eixo:** SUAS / Operação
**Base legal:** Resolução CNAS 109/2009
**Enunciado:** Os serviços oferecidos devem corresponder a pelo menos uma das tipificações nacionais (PSB: PAIF, SCFV, PETI; PSE Média: PAEFI, Abordagem Social; PSE Alta: Acolhimento). A capacidade de atendimento deve ser declarada e respeitada.
**Verificável:** parcial
**Dado necessário:** `projects.tipificacao_suas` (não existe); `project_classes.max_students` (capacidade — existe); tabela de tipificações CNAS 109/2009 no sistema
**Já temos?** parcial — capacidade existe em `project_classes.max_students`; sem tipificação
**Periodicidade:** contínua
**Evidência:** Tipo A (capacidade × atendimentos realizados) + Tipo C (declaração de adequação à tipificação)
**Risco se falhar:** alto
**Tipo:** A + C

---

**ID:** SUAS-OP-003
**Eixo:** SUAS / Recursos Humanos
**Base legal:** NOB-RH/SUAS (Resolução CNAS 269/2006); PNAS/2004
**Enunciado:** A equipe de referência deve ser composta por trabalhadores com nível superior das áreas de Serviço Social, Psicologia, Pedagogia, conforme o tipo de serviço e a capacidade de atendimento. Exige-se vínculo formal com a entidade.
**Verificável:** parcial
**Dado necessário:** `employees.position` (mapeado para categorias profissionais) + `employees.contract_type` (vínculo formal) + `employees.work_hours_weekly` + `employees.project_id` (vinculado ao serviço)
**Já temos?** parcial — campos existem mas sem mapeamento para exigências NOB-RH
**Periodicidade:** contínua
**Evidência:** Tipo A (equipe adequada por serviço) + Tipo B (contratos de trabalho / registros profissionais)
**Risco se falhar:** médio
**Tipo:** A + B

---

**ID:** SUAS-OP-004
**Eixo:** SUAS / Cofinanciamento
**Base legal:** NOB-SUAS, art. 34 [⚠️ verificar]; Resolução CNAS 33/2012
**Enunciado:** Os recursos de cofinanciamento SUAS (federal, estadual, municipal) devem ser aplicados exclusivamente nos serviços tipificados, com prestação de contas ao fundo de assistência social correspondente.
**Verificável:** parcial
**Dado necessário:** `transactions` com `fonte_recurso = 'cofinanciamento_suas'` (campo inexistente) + `ngo_grant_id` vinculado; relatório de execução por fundo
**Já temos?** não — sem discriminação de fonte de recurso em Transaction
**Periodicidade:** mensal / conforme prazo do gestor
**Evidência:** Tipo A (execução financeira por fonte de recurso) + Tipo B (extratos bancários)
**Risco se falhar:** alto
**Tipo:** A + B

---

**ID:** SUAS-OP-005
**Eixo:** SUAS / Transparência e Controle Social
**Base legal:** LOAS, art. 16; NOB-SUAS
**Enunciado:** A entidade deve participar das instâncias de controle social (conferências, reuniões de conselho) e disponibilizar informações sobre seus serviços para o CMAS e para os usuários.
**Verificável:** não (participação em reuniões externas)
**Dado necessário:** `attachments` com `tipo_documento = 'ata_conselho'`, `tipo_documento = 'presença_conferencia'`
**Já temos?** não
**Periodicidade:** conforme calendário do conselho (geralmente bimestral)
**Evidência:** Tipo B — Atas de reunião do conselho com presença da entidade
**Risco se falhar:** baixo
**Tipo:** B + C

---

## RESUMO DA MATRIZ

### Por tipo:
| Tipo | Quantidade | Descrição |
|---|---|---|
| **Tipo A — Calculável** | 9 requisitos (puros A) | Dados já no sistema ou com ajuste de campo |
| **Tipo B — Documental** | 8 requisitos (puros B) | Certidões e documentos externos |
| **Tipo A + B** | 7 requisitos | Dado calculado + documento comprobatório |
| **Tipo C / B + C** | 5 requisitos | Declaração humana + documentação |
| **TOTAL** | **29 requisitos** | Sendo 16 com componente calculável (A) |

### Por eixo:
| Eixo | Requisitos | Calculáveis (A) | Documentais (B) | Declaratórios (C) |
|---|---|---|---|---|
| CEBAS Geral | 8 | 2 | 6 | 2 |
| CEBAS Assistência Social | 7 | 4 | 5 | 2 |
| CEBAS Saúde | 2 | 0 | 2 | 0 |
| CEBAS Educação | 1 | 1 | 1 | 0 |
| MROSC | 7 | 4 | 6 | 2 |
| SUAS | 5 | 3 | 4 | 2 |
| **TOTAL** | **30** | **14** | **24** | **8** |

### Requisitos com dados já existentes no Vivensi (total ou parcialmente):
| ID | Já temos? | O que falta |
|---|---|---|
| CEBAS-G-010 | ✅ sim | Nada — lançamentos contábeis existem |
| CEBAS-G-006 | ✅ parcial | DRE existe; balanço não |
| CEBAS-G-007 | ✅ parcial | Receita calculável; documento de parecer não tipificado |
| CEBAS-AS-006 | ✅ parcial | Dados existem; relatório formatado não |
| MROSC-P-002 | ✅ parcial | NgoGrant + Project + Stage cobrem estrutura |
| MROSC-P-005 | ✅ parcial | Dados existem; relatório formatado não |
| MROSC-P-006 | ✅ parcial | Transaction tem attachment_path; falta tipificação |
| MROSC-P-007 | ✅ parcial | ProjectStage tem planned_value; falta executed_value |
| SUAS-OP-002 | ✅ parcial | max_students existe; falta tipificação |
| SUAS-OP-003 | ✅ parcial | employees existem; falta mapeamento NOB-RH |

### Requisitos que exigem dados 100% novos (maior esforço):
- CEBAS-AS-002: Percentual de gratuidade (campo `gratuito` em Attendance + discriminação de receita)
- CEBAS-AS-001/007, SUAS-R-001/002: Inscrições externas (CNAS, CMAS, CNEAS) — cadastro manual
- CEBAS-G-003/004/005, MROSC-P-001: Certidões negativas — upload tipificado com validade
- CEBAS-AS-004, SUAS-OP-001: RMA — geração de relatório no formato Rede SUAS
- Todos os campos de `tipo_documento` e `valid_until` em Attachment

---

> ⚠️ **AVISO LEGAL:** Este documento é uma tradução técnica de requisitos regulatórios para fins de desenho de sistema. Percentuais, prazos e artigos específicos devem ser verificados com advogado especializado em direito do terceiro setor antes de qualquer comunicação ao cliente ou uso em produto.
