# Plano de Evolução — Gestão de Projetos Vivensi (v2)

> **Origem**: itens da seção 2.6 do `RELATORIO_FUNCOES_NGO.md` ("pendentes / em desenvolvimento")
> **Data**: 2026-06-03
> **Stack-alvo**: Laravel 9 · MySQL · Redis · DeepSeek

Os três itens abaixo evoluem a tela de gestão de projeto (`/projects/{id}`) sem mudanças estruturais grandes. Foram dimensionados em cima do código real (controllers, models, views), não em hipótese.

---

## 1. Arquivar projeto

### Escopo
Permitir "arquivar" projetos concluídos: saem da lista padrão, ficam acessíveis em página separada com botão "Reativar". **Não é delete** — preserva todo histórico (transações, timeline, logs, beneficiários).

### Mudança de schema
Coluna nova em `projects`:

```php
$table->timestamp('archived_at')->nullable()->index();
$table->unsignedBigInteger('archived_by')->nullable();
```

Migration: `2026_06_03_add_archived_at_to_projects` — idempotente, com `Schema::hasColumn` antes do add.

> Observação: o `Project` model tem `const UPDATED_AT = null;` porque a tabela é legada. Não vamos mexer nessa coluna.

### Arquivos a tocar

| Arquivo | Mudança |
|---|---|
| `database/migrations/2026_06_03_add_archived_at_to_projects.php` | **novo** — adiciona `archived_at`, `archived_by` |
| `app/Models/Project.php` | `$fillable` + casts + scopes `active()` (`whereNull('archived_at')`) e `archived()`. Método `archive(User $u)` / `unarchive()` |
| `app/Http/Controllers/ProjectController.php` | `index()` aplica scope `active()` por padrão; novo método `archived()` lista arquivados; novos `archive($id)` / `unarchive($id)` |
| `routes/partials/projects.php` | `Route::get('/projects/archived', ...)`, `Route::post('/projects/{id}/archive', ...)`, `Route::post('/projects/{id}/unarchive', ...)` |
| `resources/views/projects/show.blade.php:409` | Botão "Arquivar Registro" passa a postar para `/projects/{id}/archive` com confirmação |
| `resources/views/projects/index.blade.php` | Link "Ver arquivados" no header; badge "Arquivado" na lista |
| `resources/views/projects/archived.blade.php` | **novo** — lista compacta + botão "Reativar" |
| `app/Policies/ProjectPolicy.php` (ou check inline) | Permitir `archive` para `manager`/`ngo`/`super_admin` e member com `access_level=ADMIN` |
| `app/Models/AuditLog` | Registrar `project.archived` / `project.unarchived` |

### Decisões
- **Não usar `SoftDeletes`**: o trait do Laravel pressupõe `deleted_at` e altera semântica de queries em vários lugares — risco alto numa tabela com 169 migrations já rodadas. `archived_at` é um campo de domínio, mais explícito.
- **Dashboards e relatórios DRE**: aplicam scope `active()`? Decisão a tomar — recomendo **sim para listagens**, **não para relatórios financeiros** (saldo histórico precisa incluir projetos arquivados).
- **Subscription limit**: validar se há limite de projetos por plano. Projetos arquivados **não devem contar** no limite.

### Estimativa
**~1 dia** (4-6h código + 2h testes/views).

---

## 2. Filtros avançados no Dossiê Financeiro

### Escopo
Adicionar filtros na listagem de transações que aparece **dentro de cada projeto** (aba Dossiê Financeiro) **e** na página global `/transactions` (que hoje só pagina, sem filtros).

### Mudança de schema
Nenhuma. Os campos já existem (`type`, `status`, `approval_status`, `date`, `category_id`).

### Arquivos a tocar

| Arquivo | Mudança |
|---|---|
| `app/Http/Controllers/TransactionController.php` | `index(Request $request)` aceita query params: `type`, `status`, `category_id`, `project_id`, `date_from`, `date_to`, `search`. Aplica `when($request->filled('X'), fn ...)` condicional. Mantém `paginate(20)->withQueryString()` |
| `resources/views/transactions/index.blade.php` | Form GET no topo com 5 campos: tipo (select), status (select), categoria (select), range de data (2 inputs), busca (texto). Mostra chips dos filtros ativos com X para remover |
| `resources/views/projects/show.blade.php` (Dossiê Financeiro) | Mesmos filtros inline na aba. Submit recarrega via Alpine ou AJAX simples |
| `resources/views/projects/partials/dossie-financeiro.blade.php` | **novo** — extrair o bloco atual para partial reutilizável que aceita query |
| `app/Http/Controllers/ProjectController.php::show()` | Passa transações filtradas (não só "últimas 10") quando vier query string; senão mantém comportamento atual |

### Filtros que entram
1. **Tipo**: entrada / saída / todos
2. **Status**: pendente / aprovado / pago / rejeitado / todos
3. **Categoria**: dropdown de categorias do tenant
4. **Período**: `date_from` + `date_to` (datepicker)
5. **Busca**: descrição contém X
6. *(escopo do projeto)*: filtro fixo por `project_id` quando dentro da aba

### Decisões
- **Persistência do filtro**: usar querystring (`withQueryString()`). Não precisa Session.
- **Exportação respeitando filtro**: `TransactionController::export()` precisa aceitar os mesmos query params para que o CSV/PDF reflita o que o usuário está vendo. **Risco médio**: testar para não exportar a base inteira ignorando filtro.
- **Performance**: verificar se já existe índice composto `(tenant_id, type, status, date)`. A migration `add_performance_indexes` de 2026_06_02 sugere que sim — confirmar antes.

### Estimativa
**~6h** (3h backend + filtros, 3h UI/partial).

---

## 3. Bruce AI no contexto do projeto

### Escopo
Adicionar ao Bruce AI a capacidade de receber **contexto de um projeto específico** quando o usuário está em `/projects/{id}`. O chat injeta automaticamente no system prompt: nome do projeto, status, orçamento, saldo, tarefas pendentes, últimos logs, próximo marco.

### Estratégia
**Estender o endpoint existente `/api/bruce/chat`** com 2 parâmetros opcionais `context_type` e `context_id`. Não criar endpoint novo — evita duplicar histórico, cache e throttle, e reaproveita o pattern de `tenantContext()` que já existe no `BruceAiService`.

### Mudança de schema
Nenhuma. Histórico já vive em Redis com TTL 1h.

### Arquivos a tocar

| Arquivo | Mudança |
|---|---|
| `app/Services/BruceAiService.php` | Adicionar parâmetros opcionais `?string $contextType, ?int $contextId` em `chat()` e `buildSystemPrompt()`. Novo método privado `projectContext(int $id, int $tenantId): string` que retorna bloco pronto para concatenar (nome, status, orçamento, saldo, top 5 tarefas pendentes, top 3 logs recentes, próximo marco). Cache 5min por `project_id` (segue o padrão do `tenantContext`) |
| `app/Http/Controllers/Api/BruceAiController.php` | `chat()` aceita `$request->input('context_type')` e `context_id`, valida (`in:project`), confere ownership (`Project::where('tenant_id', $u->tenant_id)->find($id)`), passa para o service |
| Chave do cache de histórico | Passa a ser `bruce.history.{tenant}.{user}.{context_type}.{context_id}` para isolar conversas — perguntas sobre o projeto X não vazam para o projeto Y nem para o chat global |
| `resources/views/projects/show.blade.php` | Adicionar botão "Pergunte ao Bruce sobre este projeto" — abre widget já com `data-context-type="project"` e `data-context-id="{{ $project->id }}"`. Pode reusar o widget global |
| `resources/views/partials/chat_widget.blade.php` | Aceitar atributos `data-context-*` na inicialização; mostrar badge "Falando sobre: {nome do projeto}" no header do widget quando ativo |

### Decisões
- **Histórico isolado por projeto vs único**: isolar é mais "limpo" cognitivamente (usuário não confunde respostas), mas dobra/triplica o footprint no Redis. Recomendo **isolado por contexto**, com mesma TTL (1h).
- **Custo de token**: o context block do projeto vai gastar +200-500 tokens por chamada. Aceitável (DeepSeek é barato), mas monitorar.
- **Permissões**: só member do projeto OU `manager`/`ngo`/`super_admin` pode iniciar Bruce contextual nesse projeto. Senão, retornar 403.
- **Conteúdo do context block**: começar com 5 dados (nome, status, budget vs gasto, tarefas pendentes count, último log). Não derramar muito dado — Bruce já tem acesso ao tenant inteiro pelo prompt global.

### Estimativa
**~2-3 dias** (1 dia service + controller + cache; 1 dia widget + JS + testes; meio-dia integração e ajuste de prompt).

---

## Resumo executivo

| Item | Esforço | Risco | Bloqueia outros? |
|---|---|---|---|
| 1. Arquivar projeto | 1 dia | Baixo | Não |
| 2. Filtros no Dossiê | 6h | Baixo | Não |
| 3. Bruce AI no projeto | 2-3 dias | Médio (toca service crítico em produção) | Não |

**Ordem recomendada**: 1 → 2 → 3. Os dois primeiros são quick wins com baixo risco; o terceiro tem o maior valor mas exige mais cuidado.

**Total**: ~5 dias úteis para os três (com folga para testes e ajustes finos).

---

## Pré-flight em produção (regra de deploy)

Antes de qualquer um destes itens entrar no VPS, seguir a regra estabelecida no projeto: **nunca alterar model sem migration confirmada no VPS primeiro**. Sequência segura:

1. Criar migration localmente, testar (`php artisan migrate` + rollback) no XAMPP
2. Push para o branch
3. SSH no VPS: `git pull && php artisan migrate:status` (confirmar pendente)
4. `php artisan migrate --force` no VPS
5. Só então fazer deploy da view/controller/model que usa o novo campo
6. Rodar `infra/preflight-check.sh` antes do go-live
