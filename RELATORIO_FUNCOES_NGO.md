# Relatório de Funcionalidades — Painel ONG/Terceiro Setor (Vivensi)

> **Escopo**: inventário de todos os módulos do painel ONG e detalhamento das funções disponíveis dentro da gestão de cada projeto.
> **Stack**: Laravel 9 · MySQL · Redis · Evolution API · DeepSeek
> **Data**: 2026-06-03

---

## PARTE 1 — Módulos disponíveis no Painel ONG

Cada item abaixo é um módulo independente acessível pela navegação lateral do painel ONG. Os endpoints estão sob o prefixo `/ngo/...`.

| # | Módulo | Função principal | Recursos-chave |
|---|---|---|---|
| 1 | **Doadores (CRM)** | Cadastro e gestão de doadores | CRUD doador; envio de link do portal; regeneração de token |
| 2 | **Campanhas** | Campanhas de arrecadação | Criação e listagem |
| 3 | **Orçamento** | Planejamento orçamentário anual | CRUD; exportação CSV e PDF |
| 4 | **Equipe** | Gestão de colaboradores e usuários | CRUD; vinculação a projetos |
| 5 | **Smart Analysis** | Painel analítico inteligente | Visão consolidada com IA |
| 6 | **Recibos de Doação** | Emissão de recibos | Geração de link público + regenerar/revogar |
| 7 | **Contratos Digitais** | Contratos com assinatura eletrônica | Geração de link público + regenerar/revogar |
| 8 | **CRM Patrocínios** | Pipeline comercial B2B | Funil por estágio (kanban-like) |
| 9 | **Editais (Grants)** | Captação por editais | Cadastro; análise por IA; geração de proposta IA; upload de documentos |
| 10 | **Portal de Transparência** | Portal público + gestão interna | Conselho/Diretoria; documentos; parcerias; portal `/t/{slug}` |
| 11 | **RH & Voluntários** | Folha + voluntariado | Funcionários; voluntários; banco de horas; certificados PDF; export CSV |
| 12 | **Beneficiários** | Prontuário social | CRUD; importação CSV; prontuário com atendimentos; relatório anual com múltiplos cortes; grupo familiar; PDF |
| 13 | **Almoxarifado** | Controle de estoque | CRUD itens; movimentações; export CSV |
| 14 | **Patrimônio (Ativos)** | Bens duráveis | CRUD; termo de responsabilidade PDF; export CSV |
| 15 | **Conciliação Bancária** | Conciliação OFX/CSV | Upload extrato; conciliação automática |
| 16 | **Relatórios DRE** | Demonstrativo de resultado | DRE com filtros; export CSV e PDF |
| 17 | **Trilha de Auditoria** | Log de quem fez o quê | Listagem; detalhe; export CSV |
| 18 | **E-mail Marketing** | Campanhas por e-mail | Criar; enviar; estatísticas |
| 19 | **Landing Pages** | Builder de páginas próprias | Builder por seções; publicar; duplicar; capturar leads (CSV) |
| 20 | **SIC** | Serviço de Informação ao Cidadão | Recebe pedidos públicos; responde; muda status |
| 21 | **Projetos** | Gestão completa de projetos sociais | *(detalhado na Parte 2)* |

---

## PARTE 2 — Gestão de um Projeto criado pela ONG

Funcionalidades acessíveis ao abrir um projeto específico (`/projects/{id}`).

### 2.1 Cabeçalho do projeto (visão geral)

**Campos exibidos**: nome, descrição/escopo, status (Em Missão / Pausa / Concluído / Cancelado), ID.

**Cards de indicadores em tempo real**:

- **Budget Destinado** (orçamento planejado)
- **Aporte Realizado** (despesas pagas)
- **Saldo em Caixa** (com % disponível)
- **Performance** (taxa de eficiência orçamentária)

**Ações rápidas no topo**:

- Editar dados (managers/admin)
- Exportar Relatório PDF (job assíncrono)
- Acessar Kanban de tarefas

---

### 2.2 Abas/seções dentro do projeto

#### A. Dossiê Financeiro

- Lista das últimas movimentações vinculadas ao projeto
- Colunas: ID, descrição, data, valor (verde = receita, vermelho = despesa)
- **Ação**: "Lançar Movimentação" → cria transação ligada ao projeto
- **Workflow de aprovação**: employees criam despesas pendentes → managers aprovam/rejeitam (e-mail é disparado)

#### B. Stakeholders (Equipe do projeto)

- Lista de membros do projeto com **nível de acesso** (Viewer / Editor / Admin)
- Mostra o responsável geral fixo no topo
- **Ações**:
  - **+ Adicionar membro** (modal com 2 abas):
    - *Vincular ativo*: seleciona usuário existente + define access_level
    - *Novo credenciamento*: cria usuário (employee) + envia link de senha por e-mail
  - **Remover membro** (managers)

#### C. Toolkit Estratégico

Atalhos para ferramentas globais a partir do contexto do projeto:

- Agenda da Missão (calendário)
- Central de Aprovações
- Conciliação Bancária
- Contratos Digitais
- Smart Analysis
- Arquivar projeto *(em desenvolvimento)*

#### D. Pessoas & Contatos (beneficiários do projeto)

- Tabela com nome, telefone (ícone WhatsApp), endereço, cidade, ações
- **Ações**:
  - **+ Nova Pessoa** (cadastro unitário via modal)
  - **Importar CSV** (até 500 linhas; padrão `Nome, Telefone, Endereço, Cidade`)
  - **Criar Lista de Disparo WhatsApp**: extrai os telefones válidos do projeto e redireciona para o broadcast já pré-preenchido

#### E. Linha do Tempo de Impacto (Timeline)

- Dossiê visual em formato de timeline com cards por marco
- **Tipos de registro**: Milestone (verde) · Foto (azul) · Vídeo (âmbar) · Status
- Upload de mídia (arquivos vão para `/storage/tenants/{tenant_id}/projects/timeline/`)
- **Ações**: Registrar impacto · Deletar registro

#### F. Diário de Evolução (Logs)

- Atualizações em prosa livre da equipe sobre o andamento
- Cards por entrada com autor + timestamp
- Limite de 3000 caracteres por entrada
- **Geração de Relatório com IA** (botão habilitado a partir de 3 entradas):
  - Dispara job assíncrono `GenerateProjectLogSummaryJob` (fila `ai`)
  - Chama **DeepSeek** com prompt estruturado (resumo executivo, avanços, atenção, próximos passos)
  - Resposta em markdown, até 500 palavras
  - UI faz polling a cada 5s até concluir
  - Resultado guardado em `ai_summary`, `ai_summary_at`, `ai_summary_status`

#### G. Tarefas — Kanban

- Quadro Kanban próprio do projeto (`/projects/{id}/kanban`)
- Drag-and-drop para mudança de status
- Visualização adicional disponível em lista (`/tasks`) e calendário (`/tasks/calendar`)
- CRUD completo + atualização de status via API

---

### 2.3 Funcionalidades de IA dentro do projeto

| Recurso | Tecnologia | Trigger | Status |
|---|---|---|---|
| Resumo do Diário de Evolução | DeepSeek | Botão "Gerar Relatório IA" (≥3 entradas) | Ativo |
| Análise de Edital (Grants) | DeepSeek/Gemini | Botão na ficha do edital | Ativo |
| Geração de Proposta para Edital | DeepSeek/Gemini | `/ngo/grants/{id}/generate-proposal` | Ativo |
| Smart Analysis | Bruce AI | Módulo separado linkado pelo Toolkit | Ativo |

---

### 2.4 Matriz de Permissões

**Papéis globais**: `super_admin`, `ngo`, `manager`, `employee`
**Nível de acesso no projeto** (member-level): `VIEWER`, `EDITOR`, `ADMIN`

| Função | Viewer | Editor | Manager/ProjectAdmin | Employee (não-membro) |
|---|---|---|---|---|
| Ver detalhes do projeto | sim | sim | sim | não |
| Editar dados | não | não | sim | não |
| Adicionar/remover membro | não | não | sim | não |
| Criar tarefa | não | sim | sim | parcial |
| Registrar log/diário | não | sim | sim | sim (se membro) |
| Disparar Resumo IA | não | sim | sim | sim |
| Lançar transação | não | não | sim | cria pendente |
| Aprovar/rejeitar transação | não | não | sim | não |
| Adicionar pessoa/beneficiário | não | não | sim | não |
| Registrar marco na timeline | não | não | sim | não |

---

### 2.5 Integrações externas ativadas a partir do projeto

| Integração | Onde é acionada | Tecnologia |
|---|---|---|
| **WhatsApp Broadcast** | Botão "Criar Lista de Disparo" em Pessoas | Evolution API + WhatsAppService |
| **E-mail transacional** | Novo membro (link de senha); aprovação de despesa | Brevo / SMTP |
| **PDF Export do projeto** | Botão "Relatório PDF" | DomPDF/Snappy via `GeneratePdfJob` |
| **CSV** | Import/export de pessoas e transações | nativo |
| **IA generativa** | Resumo do diário | DeepSeek |
| **Storage isolado** | Mídias da timeline | `storage/tenants/{tenant_id}/projects/...` |

---

### 2.6 Itens pendentes / em desenvolvimento

- **Arquivar projeto**: botão existe na UI, comportamento ainda não implementado
- **Bruce AI dentro do contexto do projeto**: referenciado mas não ativo na tela atual de projeto (existe Smart Analysis como módulo separado)
- **Filtros avançados no Dossiê Financeiro** (por tipo/status): planejados
