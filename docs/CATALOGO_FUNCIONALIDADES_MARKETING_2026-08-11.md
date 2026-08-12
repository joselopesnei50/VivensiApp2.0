# Catálogo de Funcionalidades — Vivensi (2026-08-11)

Este documento mapeia **todas as funcionalidades** por painel, extraído de:
1. Menu real: `resources/views/layouts/app.blade.php` (linhas 743–1080 painel NGO)
2. Rotas: `routes/partials/ngo.php`, `shared.php`, `whatsapp.php`, `projects.php`, `social.php`, `personal.php`
3. Config Bruno: `config/bot-vendedor.php` (bloco `product.panels`, catálogo curado pra vendedor)

**Para quê:** briefing de criativos + material de marketing. Cada item tem:
- **Nome** (como aparece na UI)
- **Rota** real
- **O que faz** (funcionalidade objetiva)
- **Dor que resolve** (linguagem de cliente, não técnica)
- **Diferencial** (só quando há — argumento defensável de venda)

**Regra:** se algo estiver marcado `[verificar]`, o item aparece no menu mas o controller/rota exata precisa de dupla checagem antes de virar peça pública.

---

## PAINEL 1 — TERCEIRO SETOR (ONGs, OSCs, Associações, Institutos, Fundações)

**Nicho principal.** É onde o Vivensi brilha e o Bruno pita primeiro. Vocabulário: doador, beneficiário, edital, captação, prestação de contas, voluntário, CEBAS, MROSC, SUAS.

### Projetos & Captação

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Projetos Ativos** | `/projects` | CRUD de projetos com orçamento, equipe, cronograma, etapas (ProjectStage), indicadores. Dashboard de risco por projeto. | Coordenar múltiplos programas sem perder orçamento nem prazo. | Camada `ProjectStage` (kanban de fases + aprovação + digest) — a maioria dos ERPs não tem isso. |
| **Turmas / Chamada / Lista de Presença** | `/class-sessions` (dentro do projeto) | Registro de presença com QR code público (token único), dashboard de risco de evasão (3 faltas seguidas OR ≥30%), opt-in LGPD obrigatório, ip_hash HMAC (nunca IP cru). | Comprovar em edital que a atividade aconteceu e quem participou. | ip_hash HMAC + opt-in LGPD nativo — pronto pra auditoria. |
| **Doadores** | `/ngo/donors` | CRM completo — histórico de doações, campanhas vinculadas, portal individual por token, geração de recibos. Encriptação at-rest de PII (email/phone). | Entender quem doa, quanto e por quê; oferecer relatório sem perder contato. | Portal do doador via token público (sem senha) + PII cifrada AES-256. |
| **Recibos de Doação** | `/ngo/receipts` | Emissão digital com link compartilhável e rastreamento de acesso. | Comprovar doação na Receita sem papel. | |
| **Editais & Convênios** | `/ngo/grants` | Cadastro, análise via IA (Bruce identifica chances/prazos/lacunas), geração automática de proposta, upload de docs, histórico. | Não perder edital por desorganização; reduzir escrita de proposta de 20h pra 2h. | IA que **escreve a proposta** com base no edital + dados da ONG (não só rastreia). |
| **CRM de Patrocínios** | `/ngo/sponsorships` | Pipeline Prospecção→Qualificação→Negociação→Fechado com histórico de interações e valor pré-negociado. | Estruturar venda de patrocínio; não deixar oportunidade esfriar. | |
| **Radar de Editais** | `/ngo/radar` | Rastreia editais públicos (Querido Diário) por palavra-chave + CNPJ + IBGE, avalia aderência automática ao perfil da ONG, notifica quando novo edital bater. | Descobrir oportunidades sem navegar 50 portais; receita passiva. | Único no mercado que casa perfil da ONG × edital automaticamente com IA. |

### WhatsApp

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Chat & Atendimento Omnichannel** | `/whatsapp/chat` | Chat central — Evolution API + Meta Cloud na mesma tela. Atribuição a agentes, transferência, qualificação IA, roteamento auto round-robin. | Não deixar mensagem sem resposta; não misturar pessoal com profissional. | **2 backends WhatsApp no mesmo painel** (Evolution + Cloud) + auto-assign per-tenant configurável. |
| **Etiquetas** | `/whatsapp/labels` | Etiquetas customizadas por tenant (ex: "Doador VIP", "Lead qualificado"). | Organizar centenas de chats. | |
| **Disparo em Massa** | `/whatsapp/broadcast` | Texto+imagem+PDF+áudio pra lista/grupo/etiquetas, com agendamento, cadência anti-ban, quota mensal, dashboard de entrega. | Comunicar 2 mil beneficiários sem ban de instância. | **Anti-ban 5 fases** (fingerprint, diversidade, taxa resposta 7d, ultra-safe 21d, dashboard) + termo v2026-07 obrigatório. |
| **Opt-in & Campanhas** | `/whatsapp/optin` | Registra consentimento LGPD explícito, tokens de duplo opt-in, respeita opt-out automático. | Estar em conformidade LGPD art. 7. | |
| **Conectar WhatsApp Cloud** | `/whatsapp/cloud/connect` | Integra WhatsApp Oficial Meta — Embedded Signup (Tech Provider) ou conexão manual. | Escalar sem risco de ban; rodar 24/7 sem celular. | Vivensi é **Tech Provider Meta verificado** (NC5HUBDIGITAL-EMP). |
| **Chatbot & Config (Bruce AI)** | `/whatsapp/settings` | Treina Bruce AI em texto livre (personalidade, tom, regras, links). Aplica em todas as respostas automáticas. | Respostas automáticas em segundos em linguagem natural. | IA treinada pelo próprio cliente **sem addon**. |
| **Templates Meta** | `/whatsapp/cloud/templates` | CRUD per-tenant + sync com Meta, parâmetros dinâmicos. | Ter mensagens pré-aprovadas prontas. | |
| **Formulários conversacionais** | `/whatsapp/forms` | Formulário Q&A dentro do WhatsApp que capta dados (beneficiário/doador/voluntário). | Qualificar lead sem fuga de canal. | |
| **Automações por regra** | `/whatsapp/automations` | "Se trigger, então ação" (ex: usuário escreve VOLUNTÁRIO → envia link inscrição). | 80% das mensagens automáticas. | |
| **Meu Consumo** | `/whatsapp/consumo` | Dashboard de custo real por conversa (só se Cloud Meta ativo). | Validar ROI; evitar surpresa na fatura Meta. | |

### Marketing & Comunicação

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **E-mail Marketing** | `/ngo/email-campaigns` | Builder de campanha, segmentação, rastreamento de abertura/clique via Brevo API. | Comunicar; saber quem leu. | Envio via **Brevo API** (não SMTP), 200 emails 5min sem timeout 504. |
| **Construtor de Landing Pages** | `/ngo/landing-pages` | Builder visual (sem código), campos customizáveis (`custom_fields[]`), publicação instantânea, domínio próprio. | Lançar LP em 10 min vs 1 semana pedindo dev. | Campos personalizados dinâmicos (feature 2026-08-06). |
| **Inteligência Territorial** | `/intelligence/territorial` | Mapa interativo com indicadores IBGE (população, renda, educação) por cidade. | Escolher cidade-alvo com dados; fundamentar proposta. | IBGE + geo nativo. |
| **Social AI Hub** | `/social-ai` | Gera 3 variações de post (imagem via DALL-E + texto via DeepSeek), agenda no calendário. | Postar 5x/semana sem designer. | Texto já com **gancho de 2s** (framework growth 2026-08-11). |
| **Agenda de Posts** | `/social/posts` | Calendário visual + agendador Facebook/Instagram. | Planejar mês em 1h. | |
| **Publicar em Redes** | `/social/posts/create` | Formulário direto de publicação (texto + mídia + agendamento). Timezone BRT correto. | Postar rápido sem abrir cada rede. | |
| **Hub de Marketing IA (Estratégia)** | `/marketing` | Aplica **matriz RFM** (Recência+Frequência+Valor), recomenda segmentos, propõe funil de educação vs anúncio frio, calcula CAC. | Estratégia científica em vez de adivinhação. | RFM + funil educação + gancho 2s = **frameworks científicos, não IA genérica**. |
| **Prospecção IA** | `/prospecting` | Busca via Serper (Google Maps + Web), scoring pela Bruce AI, pitch pronto por lead. Quantidade configurável (20–100). | Encher pipeline sem disparo frio que queima instância. | Scoring automático + pitch personalizado. |
| **Rifas Online** | `/raffles` | Plataforma completa — criação, venda com Pix, sorteio, comunicação com participantes. | Diversificar receita; arrecadar R$ 20k em 1 mês. | Rifa dentro do ERP (comum: rifa em plataforma separada). |
| **Contas Meta** | `/social/accounts` | Conecta Facebook/Instagram via OAuth. | Centralizar gerência de 3+ redes. | |
| **Analytics Redes** | `/social/analytics` | Relatório de engajamento (curtidas, comentários, alcance) por post e período. | Repetir o que funciona, cortar o que não vende. | Coleta métricas Meta com auto-desligamento de conta órfã. |

### Financeiro

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Fluxo de Caixa** | `/transactions` | Entrada/saída por projeto/categoria, saldo em tempo real, anexo de comprovante. | Saber quanto tem disponível; evitar estouro. | |
| **Nova Transação** | `/transactions/create` | Atalho rápido pra lançamento. | Registrar gasto no mesmo dia. | |
| **Orçamento Anual** | `/ngo/budget` | Planejamento por mês/projeto, alocação, acompanhamento (% gasto vs previsto), alertas. | Não ultrapassar orçamento. | |
| **Conciliação Bancária** | `/ngo/reconciliation` | Upload de extrato CSV, match automático com lançamentos, identifica divergências. | Fechar mês sem discrepância (3h → 15min). | |
| **Importar Planilha** | `/finance/import` | Wizard 2-passos, dedup por description+date+amount, aprovação por role. | Migrar do Excel sem perder histórico. | Aprovação obrigatória por role antes de gravar. |

### Pessoas, RH e Voluntários

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Equipe da ONG** | `/ngo/team` | Cadastro de colaboradores — CPF, data admissão, salário, assinatura digital de contrato. | Nómina organizada; gerar eSocial sem contabilista mandando 10 emails. | |
| **RH & Voluntários** | `/ngo/hr` | Cadastro de voluntários (dados, horas doadas), emissão automática de certificado em PDF, folha simplificada de horas. | Comprovar em edital "X horas de voluntariado engajadas". | Certificado PDF em 1 clique. |
| **Beneficiários** | `/ngo/beneficiaries` | CRM social 360 — dados pessoais, família, deficiências, situação socioeconômica, histórico de participação. Import em massa. | Perfil 360 sem repetir cadastro. | Auditoria 2026-07-31 fechada (decryptPii nos exports, gate delete-beneficiaries, orgName real). |
| **Indicadores Sociais** | `/ngo/beneficiaries/insights` | Dashboard KPIs — idade média, gênero, famílias, taxa retenção, renda. Exporta em gráfico. | Comprovar impacto em números, não achismo. | |
| **Relatório Anual de Impacto** | `/ngo/beneficiaries/reports/annual` | PDF+CSV estruturado — evolução de beneficiários, destaques de histórias, dados estatísticos. | Marketing + compliance combinados. | |

### Patrimônio & Estoque

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Almoxarifado e Estoque** | `/ngo/inventory` | Itens com quantidade, valor, movimentação (entrada/saída), FIFO/LIFO, export inventário. Anexos polimórficos (PDF/JPG/PNG max 10MB) + import CSV. | Não perder material; saber o que tem. | Import CSV + anexos por item. |
| **Patrimônio** | `/ngo/assets` | Bens permanentes, data aquisição, valor, depreciação mensal. Termo de guarda pra CEBAS. Import CSV. | Comprovar estrutura no CEBAS; auditar bens desaparecidos. | |

### Contratos e Jurídico

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Contratos Digitais** | `/ngo/contracts` | Upload/cria contrato, colhe assinatura eletrônica, rastreia status, arquivo permanente com blind index HMAC. | Contratos não se perdem em e-mail; assinado em 1 dia. | Blind index HMAC (busca por token sem expor plaintext). |

### Relatórios e Auditoria

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **DRE** | `/ngo/reports/dre` | Demonstração de Resultados de Exercício — receita×despesa por mês/ano, saldo, formato contábil. | Prestar contas auditável. | |
| **Central de Auditoria** | `/ngo/audit` | Trilha de alterações (quem, o quê, quando, IP), export CSV pra auditor. | Demonstrar conformidade; achar erro rápido. | Trilha completa em `AuditLog` + `WhatsappAuditLog`. |
| **Portal da Transparência** | `/ngo/transparencia` | Página pública (URL própria) com conselho, demonstrações, parcerias — configurável. | Cumprir lei; conquistar confiança de doador. | URL pública direto (sem plugin ou site à parte). |

### Motor de Conformidade Contínua

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Dashboard Conformidade** | `/ngo/conformidade` | Visão 360 — % cumprimento CEBAS/SUAS/MROSC. Alerta se requisito vencer. | Saber se está em risco antes da auditoria chegar. | **Único ERP brasileiro com módulo dedicado a CEBAS/MROSC/SUAS.** |
| **Eixos** (CEBAS/SUAS/MROSC) | `/ngo/conformidade/eixo/{eixo}` | Checklist por norma — marca cumprimento ou upload de evidência (Tipo B). | Passar em certificação de filantropia. | |
| **Planos de Ação** | `/ngo/conformidade/planos` | Cria plano (quem, quando, deadline) pra requisitos não conformes. Alerta de vencimento por email + notificação. | Estruturar correção rastreável. | `AlertaDocumentoVencendoJob` diário. |
| **Ciclos** | `/ngo/conformidade/ciclos` | Recalcula status por ciclo (anual/semestral). | Documentar evolução ao longo do tempo. | |
| **Configurar** | `/ngo/conformidade/configurar` | Customiza eixos avaliados, CNPJ, certificações ativas. | Modelar pro perfil exato. | |
| **Relatório PDF** | (dentro dos eixos) | Gera 3 PDFs (Relatório de Atividades, Conformidade, Plano de Ação) via `RelatorioPdfService`. | Documento pronto pra auditor. | |

### Inteligência Artificial

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Smart Analysis AI** | `/smart-analysis` | Bruce AI estuda financeiro + projetos + doadores e gera insights estratégicos ("Captação caiu 20%, diversifique fontes"). | Sair de reunião com número; ter dado acionável. | |
| **Sala de Estratégia** | `/strategy-room` | **5 agentes de IA** (dados/mercado, financeiro, operações/programas, mobilização, estrategista-chefe) debatem problema real e entregam 1 ação prioritária com plano que vira cartão no Kanban. Dispara sozinha ao detectar risco. | Decisão estratégica em 2 min vs 3h de reunião. | **Sistema Solar aplicado** (4 pilares Growth: aquisição/engajamento/monetização/retenção + estrategista). Vocabulário adapta pra ONG (doadores/editais). |
| **Bruce AI (assistente global)** | (em todas as telas) | Assistente conversacional integrado — pergunta sobre qualquer dado do sistema, gera análise, sugere ação. | Não precisar navegar 5 telas pra achar informação. | |

### Academy

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Vivensi Academy** | `/academy` | LMS — cursos sobre gestão ONG, LGPD, edital, financeiro. Progresso + certificado. | Equipe capacitada; reduz erros; aumenta conformidade. | |

---

## PAINEL 2 — PEQUENO NEGÓCIO (MEI, Autônomo, PJ Simples)

**Enfoque:** organizar o negócio pequeno sem burocracia de ERP grande. Módulos exclusivos: termômetro do teto MEI, DAS mensal, dossiê fiscal NFS-e.

### CRM & Clientes

| Item | Rota | O que faz | Dor |
|---|---|---|---|
| **Meus Clientes** | `/personal/clients` | CRM — nome, telefone, histórico de vendas/serviços, último contato. | Não esquecer cliente; ter histórico organizado. |
| **Novo Cliente** | `/personal/clients/create` | Atalho pra cadastro rápido. | Registrar na hora. |

### WhatsApp

Idêntico ao painel Terceiro Setor (mesmas 10 funcionalidades) — vocabulário adapta pra "cliente" em vez de "doador/beneficiário".

### Marketing & Comunicação

Mesmos módulos do painel Terceiro Setor (LP, Social AI Hub, Hub Marketing IA, Prospecção IA, Analytics), com pitch voltado pra "cliente" e "vendas".

### Gestão Financeira (exclusivo MEI)

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Fluxo de Caixa** | `/transactions` | Entrada/saída, saldo. | Saber quanto ganha/gasta. | |
| **Recibos & NFS-e** | `/personal/receipts` | Emite recibo digital + anexa NFS-e emitida no portal nfse.gov.br (Vivensi guarda o dossiê fiscal auditável). | Estar legal com Receita; cliente recebe nota. | **Dossiê fiscal NFS-e** com download por link revogável. |
| **Emitir Recibo** | `/personal/receipts/create` | Atalho rápido. | Emitir em 30 seg. | |
| **Marcar DAS como Pago** | `/personal/das/pago` | Botão no widget do dashboard MEI pra marcar DAS mensal como pago. | Não esquecer DAS (evita multa). | Lembrete DAS mensal (dia 20). |
| **Conciliação Bancária** | `/personal/reconciliation` | Upload de extrato, match com lançamentos. | Fechar mês auditável. | |
| **Importar Planilha** | `/finance/import` | CSV com histórico. | Onboarding. | |
| **Planejamento Anual** | `/personal/budget` | Projeta receita/despesa; **alerta se aproxima do teto MEI** (R$ 81k). | Não perder regime MEI; saber quando abrir ME. | Termômetro do Teto MEI (avisa aos 70% e 90%) — **único ERP com isso nativo**. |
| **Dicas IA** | `/personal/budget/ai-tips` | Bruce AI sugere ajustes ("você tá perto do teto, considere abrir ME"). | Insight sem consultor caro. | |

### Inteligência Artificial

Idêntico ao painel Terceiro Setor (Sala de Estratégia + Smart Analysis), mas com vocabulário MEI (clientes/teto/notas fiscais).

### Academy

`/academy` — cursos MEI, NFS-e, fisco, vender online.

---

## PAINEL 3 — GESTOR/PME (Gestor de Projetos, Pequena Empresa até ~50 pessoas)

**Enfoque:** gestão de projetos + operações da empresa.

### Projetos e Operações

| Item | Rota | O que faz | Dor | Diferencial |
|---|---|---|---|---|
| **Projetos com Orçamento** | `/projects` | Igual ao painel NGO — cria projeto, aloca equipe, define etapas, deadline, dashboard progresso. | Coordenar 5+ projetos paralelos. | |
| **Chamada / Lista de Presença** | `/class-sessions` | Registro em reunião/treinamento com QR code + opt-in LGPD. | Comprovar participação; engajamento. | |
| **Equipe e RH** | `/ngo/team`, `/ngo/hr` | Colaboradores + voluntários (contexto empresa). | Nómina + certificado. | |
| **Agenda Corporativa** | `/manager/schedule` | Calendário compartilhado de reuniões, prazos. | Evitar conflito de horário. | |
| **Central de Aprovações** | `/manager/approvals` | Fila de despesas/documentos esperando aprovação. | Aprovar em 1 clique; não deixar gasto parado. | |
| **Perfil Operacional** | `/manager/perfil-operacional` | Define processo padrão da empresa (fluxo aprovação, responsáveis, prazos). | Governança; todos sabem quem aprova o quê. | |
| **Kanban Geral** | `/manager/kanban` | Quadro Kanban com colunas customizáveis. | Visualizar pipeline; achar bottleneck. | Widget "Perfil Operacional" unificado com KPIs no dashboard manager (row única col-md-3 quando 4+2, senão col-md-4 quando 3+3). |

### Contratos e Financeiro

Contratos Digitais + Conciliação Bancária — mesmos módulos do painel NGO/MEI.

### WhatsApp + Marketing + IA + Academy

Mesmos módulos dos outros painéis, com vocabulário voltado pra "projeto" e "aprovação".

---

## FUNCIONALIDADES TRANSVERSAIS (Todos os Painéis)

| Item | Rota | O que faz | Dor |
|---|---|---|---|
| **Dashboard** | `/dashboard` | Home personalizada — resumo projetos/transações/chats/tarefas. Widget onboarding. | Entrar e já saber prioridade do dia. |
| **Tarefas / Kanban** | `/tasks`, `/tasks/calendar` | Lista + Kanban + calendário. | Não esquecer deadline. |
| **Notificações** | `/notifications` | Sininho — novo chat, tarefa vencida, aprovação pendente. | Não perder alerta. |
| **Perfil** | `/profile` | Dados pessoais, senha, 2FA. | Segurança. |
| **Meus Dados (LGPD)** | `/eu/dados` | Portal do Titular art. 15/18 — export ZIP via token 48h, delete com grace 30d + cancel, purge diário. | Direito do titular; multa evitada. |
| **Suporte** | `/support` | Chat com time Vivensi — criar ticket, conversar. | Help quando travado. |
| **Identidade Visual** | `/settings/branding` | White-label — logo, cores, domínio customizado (Gestor/NGO). | Parecer empresa própria. |
| **Documentação API** | `/api-docs` | Docs OpenAPI — endpoints, exemplos. | Dev integra sem ligar. |
| **API Tokens** | `/settings/api-tokens` | Gerar/revogar API token, ratelimit. | Integrar CRM/ERP externo. |
| **Webhooks** | `/settings/webhooks` | Configura webhook (nova transação → POST). | Sistema externo real-time. |
| **2FA** | `/2fa` | TOTP obrigatório pra super_admin. | Segurança da conta. |

---

## PAINEL SUPER ADMIN (Interno Vivensi — omitir do marketing público)

**Nota:** este painel é uso interno da própria Vivensi (gestão de tenants, planos, faturamento, conformidade de infra). Não deve aparecer em criativo de venda. Se precisar mencionar em prospecto B2B: "gerenciamento centralizado de organizações + planos + billing consolidado".

---

## DIFERENCIAIS MACRO — argumentos transversais pros criativos

1. **WhatsApp Oficial Meta (Cloud API) + Evolution API nativa no mesmo sistema.** O cliente escolhe: número via celular (Evolution, sem custo Vivensi extra) ou hospedado na nuvem Meta (com custo Meta por conversa). Não é "um ou outro" — é ambos integrados. Único no mercado brasileiro.

2. **Vivensi é Tech Provider Meta verificado (NC5HUBDIGITAL-EMP).** Embedded Signup próprio, sem depender de BSP terceiro.

3. **Bruce AI nativa e treinável pelo cliente.** Sem addon, sem fee extra. Cada organização configura personalidade/tom/regras da IA no painel — ela aplica em todas as respostas automáticas. Não é IA "genérica de terceiro".

4. **Sala de Estratégia: conselho de 5 IAs em debate.** Não é chatbot. É estrutura de conselho executivo virtual (financeiro/operações/mobilização/dados/estrategista) que estuda dados REAIS da organização e entrega UMA ação com plano que vira cartão no Kanban. Dispara sozinha se detecta risco (queda de doadores, teto MEI 90%).

5. **Hub de Marketing IA com frameworks científicos** (não IA que gera texto solto). Aplica matriz **RFM** (Recência+Frequência+Valor), **funil de educação** em vez de anúncio frio, **gancho de 2s** nos criativos. Marketing data-driven, não intuição.

6. **Prospecção IA com scoring + pitch pronto.** Busca leads via Serper (Google Maps + Web) até 100 por busca, Bruce AI faz scoring 0-100, entrega pitch personalizado. Evita disparo frio que queima instância WhatsApp.

7. **Conformidade Contínua (CEBAS/SUAS/MROSC) nativa.** Único ERP brasileiro com módulo dedicado — checklist, plano de ação, alerta de vencimento, PDF pra auditor. ONG sabe se tá em risco antes da fiscalização.

8. **Radar de Editais.** Busca automática em portais públicos (Querido Diário) por perfil da ONG, notifica quando bater. Captação passiva.

9. **LGPD-first do zero** (não patch depois). Trilha de auditoria de todo acesso, opt-in/opt-out automático, criptografia at-rest AES-256 (PII), blind index HMAC pra busca sem expor plaintext, Portal do Titular (art. 15/18) com self-service (export/delete), pol. de retenção configurável. Reduz risco de multa R$50k+.

10. **Especialização por vertical.** Painel Terceiro Setor fala "edital/doador/beneficiário", MEI fala "cliente/teto/NFS-e", Gestor fala "projeto/aprovação/equipe". Não é CRM genérico maquiado.

11. **Preço em reais**, sem volatilidade dólar. Orçamento estável.

12. **Integração WhatsApp ↔ Kanban ↔ CRM ↔ Financeiro.** Chat vira cartão no Kanban, vira tarefa, vira transação aprovável. Tudo conectado, não 5 abas em paralelo.

13. **Bruno (bot vendedor próprio).** O Vivensi usa a própria plataforma pra atender leads — Bruno vende pelo WhatsApp com function calling (agenda demo inline), qualifica lead automático, cria KanbanCard quando quente. Prova de conceito viva.

14. **Anti-ban WhatsApp em 5 fases** — fingerprint de conteúdo, diversidade de audiência, taxa de resposta 7d, ultra-safe 21d, dashboard visual. Termo v2026-07 obrigatório. Broadcast em massa sem queimar número.

---

## COMO USAR ESTE DOCUMENTO PRA CRIATIVOS

**Regra do gancho 2s aplicada a Vivensi:**
- Primeira frase = a dor mais aguda da persona (não "somos o melhor ERP")
- Segundo frame = o mecanismo específico (não "temos IA")
- Terceiro = prova (número, case anônimo, screenshot)
- CTA = demo 20 min (nunca "trial" — Vivensi não tem trial)

**Segmentação sugerida (matriz RFM aplicada em prospecção Vivensi):**
- Cluster A (ONG grande, ativa, capta >R$500k/ano): pitch de conformidade + Sala de Estratégia + Radar de Editais
- Cluster B (ONG média, WhatsApp desorganizado): pitch de chat omnichannel + Bruce AI + Broadcast
- Cluster C (MEI/autônomo, sozinho): pitch de teto MEI + recibos + DAS

**Formatos por canal:**
- Instagram Reels (< 30s): 1 dor + 1 módulo + demo. Gancho no 1º segundo.
- LinkedIn (post): case anônimo com número + módulo + link demo
- WhatsApp broadcast pra base: pergunta que qualifica → resposta gera hot lead pro Bruno
- Landing page: pipeline Sala de Estratégia (mostrar as 5 IAs em ação como imagem forte)

**Copy proibido:**
- "trial", "teste grátis", "grátis por X dias" — Vivensi não tem trial. Use "demonstração ao vivo de 20 min, sem custo".
- Prometer feature não catalogada aqui. Se precisar mencionar algo novo, checar `verificar_feature` no admin/bruno primeiro.
- Comparar diretamente com concorrente ("somos melhor que X"). Preferir "o modelo que a X usou" como analogia — nunca implicar cliente compartilhado.
- Cases inventados. Bloco `cases` do Bruno KB está VAZIO proposital — só usar analogias (Minimal, Infomoney, Lugano, Dropbox) e sempre no formato "o modelo que X usou".

---

**Fontes deste catálogo (validadas em 2026-08-11):**
- `resources/views/layouts/app.blade.php` — menu completo por role
- `routes/partials/{ngo,shared,whatsapp,social,personal,projects,public}.php` — mapa rota→controller
- `config/bot-vendedor.php` — catálogo curado do Bruno (fonte da verdade sincronizada com menu)
- Memories: `project_vivensi_*.md` — histórico de features implantadas

**Próxima revisão:** ao lançar novo módulo ou após 30 dias.
