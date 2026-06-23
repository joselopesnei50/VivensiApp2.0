# Cozinha Solidária — Análise da Fase 0

**Data:** 2026-06-22
**Roadmap:** `cozinha/vivensi-cozinha-solidaria-roadmap.md`
**Escopo:** Fundação do módulo (espinha de dados + motor de regras versionadas + sub-tenancy) como vertical do painel **Terceiro Setor (Vivensi NGO)**.
**Modo:** Análise (sem código). Implementação só após aprovação.

---

## 0. Status dos agentes

| Agente | Em `.claude/agents/` | Em `cozinha/` | Observação |
|---|---|---|---|
| `laravel-core-engineer` | ✅ (versão Gestor) | ✅ (versão Cozinha) | Versões diferem — ver §0.1 |
| `whatsapp-evolution-specialist` | ✅ (versão Gestor) | ✅ | Conviver / renomear |
| `bruce-ai-orchestrator` | ✅ (versão Gestor) | ✅ | Conviver / renomear |
| `filament-livewire-ui` | ✅ (versão Gestor) | ✅ | Conviver / renomear |
| `mds-compliance-guardian` | ❌ **NÃO carregado** | ✅ | **Copiar antes do merge da Fase 0** |
| `prestacao-contas-engineer` | ❌ **NÃO carregado** | ✅ | Necessário só a partir da Fase 4 |

### 0.1 — Decisão pendente sobre colisão de nomes

O README de `cozinha/` (linhas 17–20) sugere renomear com sufixo `-cozinha` ou usar branch separada. Como esse módulo é vertical do painel **NGO** e a Cozinha tem regras compliance bem específicas, **recomendo manter a versão Cozinha sob os nomes com sufixo** (`laravel-core-engineer-cozinha`, etc.) e copiar **integralmente** o `mds-compliance-guardian` e `prestacao-contas-engineer`. Assim os agentes do Gestor continuam vivos sem ser sobrescritos.

Esta análise foi feita aplicando manualmente a persona do `mds-compliance-guardian` (lida de `cozinha/mds-compliance-guardian.md`).

---

## 1. Mapeamento do que já existe no Vivensi

### 1.1 Módulo `ngo/beneficiaries` (a reusar — bom estado)

**Schema atual** — somando todas as migrations (4):
- Base: `database/migrations/2026_01_28_035115_create_beneficiaries_tables.php:17-30` — `tenant_id` (FK cascade), `name`, `cpf`, `nis`, `birth_date`, `phone`, `address`, `status` enum (active/inactive/graduated).
- Geocoords: `2026_03_31_161204_add_geo_coords_to_beneficiaries_and_donors.php:14-20` — `latitude/longitude`.
- Demografia: `2026_05_21_000001_add_social_fields_to_beneficiaries.php:11-27` — `gender`, `race_color`, `education`.
- Endereço estruturado: `2026_05_25_100000_add_structured_address_to_beneficiaries_and_donors.php:12-32`.
- **Criptografia LGPD aplicada**: `2026_06_02_000009_encrypt_beneficiary_cpf_nis_and_ngo_donor_document.php:14-17, 26-28` — `cpf` e `nis` são TEXT cifrados; `cpf_bidx` e `nis_bidx` (HMAC-SHA256) são índices cegos pra busca.

**Tabelas relacionadas**:
- `family_members` (FK cascade em `beneficiary_id`): `name`, `kinship`, `birth_date` (essa cifrada em `FamilyMember` cast 'encrypted').
- `attendances` (FK cascade em `beneficiary_id` + `tenant_id`): `date`, `type`, `description`, `user_id`.

**Model** — `app/Models/Beneficiary.php:1-92`
- Traits: `Auditable` (13), `BelongsToTenant` (14).
- Accessors/mutators auto cifram/decifram `cpf` (47-65) e `nis` (67-79).
- `hidden` (39) bloqueia serialização das PII — `toArray()`/JSON nunca retorna CPF/NIS.
- Relações: `familyMembers()` (83), `attendances()` (87).

**Controller** — `app/Http/Controllers/BeneficiaryController.php:1-1393`
- 1.393 linhas — **lógica inteira inline no controller, sem service**. Gap pra Cozinha: replicar esse padrão é caminho ruim. (Recomendação: extrair quando criarmos serviços novos.)
- CRUD em `routes/partials/ngo.php:97-125`.
- Import CSV com dedup por `cpf_bidx`/`nis_bidx` (821-826).
- Geocoding via Job (`BeneficiaryController.php:962, 1041` dispara `GeocodeAddressJob`).

**Observer** — `app/Observers/BeneficiaryObserver.php:10-85`
- Registrado em `AppServiceProvider:47`.
- Audita criação/edição/deleção em `audit_logs` **sanitizando** `cpf`, `nis`, `birth_date` antes de gravar (62-65) — bom padrão a copiar.
- Falha silenciosa pra não quebrar operação primária (70-80).

**Testes:** nenhum dedicado a `Beneficiary` em `tests/Feature/` (gap pré-existente, não bloqueia Fase 0).

### 1.2 Multi-tenant

**Trait `BelongsToTenant`** — `app/Traits/BelongsToTenant.php:14-48`
- Global scope (27-28): `WHERE tenant_id = auth()->user()->tenant_id` quando há usuário não-super_admin.
- `runningInConsole()` (15-22): bypass para comandos artisan — log de debug + intencional.
- Webhook context (30-36): scope retorna cedo (sem Auth), **handler deve filtrar explicitamente**.
- Auto-fill `tenant_id` em `creating()` se não setado (41-48).

**Bypass `super_admin`** — `User::isSuperAdmin()` em `app/Models/User.php:94-96` retorna `role === 'super_admin'`. 20+ comandos usam `withoutGlobalScopes()` explicitamente. Padrão consistente.

**Sub-tenancy precedente — `ProjectMember`** (PADRÃO A REUSAR)
- `app/Models/ProjectMember.php:12-17` — pivot com **`tenant_id` + `project_id`** + `access_level`.
- Filtragem por projeto é manual (extra `where`) por cima do scope de tenant.
- Coordenador da Cozinha pode seguir esse mesmo padrão (`KitchenMember`/`CozinhaCoordenador` com `cozinha_id`).

**Pattern de escopo em webhook/job** — `WhatsappInstance.php:75-78` define `scopeForTenant($query, int $tenantId)`. Convenção: **nunca** `::find()` direto; sempre filtra por tenant. Aplicar idem aos models novos da Cozinha.

**Tenant model** — `app/Models/Tenant.php:15` tem coluna `type` (`'ngo'|'manager'|'common'`), o que dispensa um `APP_PRODUCT` env. Já há `operationalProfile()` (hasOne) — bom precedente.

### 1.3 Papéis e permissões

**`User.role`** — `app/Models/User.php:26` é string (não enum). Valores: `super_admin`, `ngo`, `manager`, `common`, `employee`.
- Coexiste com Spatie `HasRoles` (10) — fallback duplo. A Cozinha vai depender só do `role` por simplicidade (manter padrão NGO).

**Gates** — `app/Providers/AuthServiceProvider.php:22-75`
- `Gate::before()` (22-24): super_admin passa tudo.
- Gates nomeados (40-54): `manage-donors`, `manage-grants`, etc. — padrão a copiar para `manage-cozinha`, `manage-termo`.

**Middleware**:
- `EnsureSuperAdmin` em `app/Http/Middleware/EnsureSuperAdmin.php:16`.
- Sem middleware "EnsureNgo" — papel é checado no controller (`in_array(role, ['ngo','super_admin'])`).

**Rotas NGO** — `routes/partials/ngo.php:4-204`
- Prefixo `/ngo` com `['auth', 'subscription']` (sem gate de role na rota). Controllers validam.
- Inventário, donors, beneficiaries, hr, grants etc. já estão lá. **Cozinha entra como mais um grupo de rotas dentro desse prefixo** — não precisa de novo painel.

**`User → Tenant`** — `belongsTo(Tenant::class)` em `app/Models/User.php:76-79`. **1 user → 1 tenant**, sem pivot multi-tenant. Coerente.

### 1.4 Outros padrões úteis a reutilizar

- **Encryption blind-index** (`cpf_bidx`, `instance_token_bidx`) — pattern já maduro; aplicar para qualquer doc novo da Cozinha.
- **`AdminAuditLog`** já existe (visto na auditoria de outro sprint) — copiar para auditoria de regras versionadas.
- **`AppServiceProvider::boot()`** registra observers dentro de `try/catch` — pattern de defesa contra DB ausente no boot. Replicar.
- **Migrations idempotentes** (vimos `if (Schema::hasTable(...))` em várias) — padrão obrigatório.

---

## 2. Gap — o que falta para a Fase 0

| Entidade do roadmap | Existe hoje? | Gap |
|---|---|---|
| **Termo de Colaboração** (nº MDS/Sesan, vigência, valor global) | ❌ | Tabela nova. Sem precedente. |
| **Plano de Trabalho** (metas físico-financeiras, cronograma, parcelas) | ❌ | Tabela(s) novas. |
| **Metas físico-financeiras** + **Parcelas** | ❌ | Tabelas novas vinculadas ao Plano. |
| **Cozinha** (sub-unidade, modelo direta/indireta, meta refeições/mês, georref) | ❌ | Tabela nova. Padrão visto em `ProjectMember`. |
| **Vínculo `Beneficiary` ↔ Cozinha** | ❌ | Beneficiary tem só `tenant_id`. Adicionar `cozinha_id` (nullable, retrocompat com NGO comum). |
| **Tabela `regras_compliance`** (regras versionadas por portaria) | ❌ | Tabela nova — coração do compliance. |
| **`RegrasComplianceService`** (resolver regra vigente para data/termo) | ❌ | Serviço novo. |
| **Papel "coordenador de cozinha"** | ❌ | Novo valor de `User.role` + pivot `cozinha_coordenadores` (Cozinha ↔ User). |
| **Sub-tenancy escopo cozinha** | ❌ | Trait/scope novo que adicione filtro `cozinha_id` por cima de `BelongsToTenant`. |
| **Tipagem NGO Cozinha vs NGO comum** | ❌ | Decisão: usar `Tenant.type='ngo'` + flag/coluna `mode='cozinha_solidaria'` no `operationalProfile` ou nova coluna `tenant.product_kind`. Discussão em §3.6. |

---

## 3. Plano da Fase 0 (decisões justificadas — sem código)

### 3.1 — Migrations propostas (ordem)

| # | Migration | Motivação | Idempotência |
|---|---|---|---|
| 1 | `create_termos_colaboracao_table` | Raiz do dossiê. Sem ela nada se reconcilia. | `Schema::hasTable()` guard. |
| 2 | `create_planos_trabalho_table` | 1-N por termo. Cronograma e valor global aprovado. | idem |
| 3 | `create_metas_table` | Metas físico-financeiras. Toda NF/refeição se liga a uma meta. | idem |
| 4 | `create_parcelas_table` | Cronograma de repasses do MDS. | idem |
| 5 | `create_cozinhas_table` | Sub-unidade. Modelo direta/indireta. Meta refeições/mês. Georref. | idem |
| 6 | `create_cozinha_coordenadores_table` | Pivot User × Cozinha (padrão `ProjectMember`). | idem |
| 7 | `add_cozinha_id_to_beneficiaries` (nullable) | Vincula beneficiário à cozinha. **Nullable** = retrocompat com NGO comum. | `hasColumn()` guard. |
| 8 | `create_regras_compliance_table` | Motor de regras versionadas. Coração do compliance. | idem |
| 9 | `seed_regras_compliance_v0` (seeder ou migration) | Cadastro inicial das regras vigentes. **Valores ficam vazios/null até curadoria jurídica confirmar** — migration cria estrutura e tipos, não chuta números. | idem |

### 3.2 — Schema decisões (justificadas)

#### `termos_colaboracao`
- Colunas: `id`, `tenant_id` (FK), `numero` (string único por tenant), `mds_sesan_ref` (string nullable), `vigencia_inicio`, `vigencia_fim`, `valor_global` (decimal 18,2), `modalidade_execucao` enum `direta|indireta` (chave da §1.2 do roadmap), `status` enum `vigente|encerrado|suspenso|em_diligencia`, `documento_url` (S3), `created_by`, timestamps, softDeletes.
- **Justificativa do `modalidade_execucao` aqui (e não em Cozinha):** roadmap §2 deixa claro que na **direta a gestora é uma cozinha de equipamento próprio (colapsam num cadastro só)**. Manter no Termo permite materializar a regra automaticamente (criar 1 Cozinha vinculada ao tenant no caso direta).
- Índices: `(tenant_id, status)`, `(tenant_id, numero)` único.

#### `planos_trabalho`
- 1-N por Termo. Colunas: `id`, `tenant_id`, `termo_id` (FK), `versao` (int, default 1), `status` enum `aprovado|em_revisao|substituido`, `aprovado_em`, `objeto` (texto), `documento_url` (S3 — versão assinada do plano), timestamps.
- **Justificativa de `versao`**: planos podem ser aditivados/reformulados durante a vigência. Reconciliação histórica precisa do plano vigente "à data X".

#### `metas`
- N por Plano. Colunas: `id`, `tenant_id`, `plano_trabalho_id` (FK), `tipo` enum `fisica|financeira|qualificacao`, `descricao`, `unidade` (string — ex.: "refeições/mês", "R$", "pessoas formadas"), `quantidade_prevista` (decimal 18,4), `valor_previsto` (decimal 18,2 nullable), `cronograma_mes` (JSON com `{mes: 'YYYY-MM', meta: ...}` ou tabela auxiliar — ver §3.3).
- **Justificativa de `tipo`**: lastro (refeição), execução (NF) e qualificação (Fase 6) reconciliam tudo contra metas — tipar facilita o vínculo.

#### `parcelas`
- N por Termo. Colunas: `id`, `tenant_id`, `termo_id`, `numero` (int), `valor` (decimal 18,2), `previsto_em` (date), `recebido_em` (date nullable), `status` enum `prevista|recebida|atrasada|bloqueada`, `comprovante_url`.

#### `cozinhas`
- N por Termo (na execução indireta) **ou** 1-1 com Termo (direta — criada automaticamente).
- Colunas: `id`, `tenant_id` (FK), `termo_id` (FK), `nome`, `meta_refeicoes_mes` (int), `endereco_*` (espelhar pattern estruturado de Beneficiary), `latitude/longitude`, `modalidade_execucao` enum `direta|indireta` **copiada do termo** (pra simplificar filtros), `status` enum `ativa|inativa`, timestamps, softDeletes.
- **Justificativa de copiar `modalidade_execucao`**: queries por cozinha não precisam joinar `termo` toda vez.
- Índices: `(tenant_id, status)`, `(termo_id)`.

#### `cozinha_coordenadores`
- Pivot User × Cozinha — segue padrão de `ProjectMember.php:12-17`.
- Colunas: `id`, `tenant_id`, `cozinha_id` (FK), `user_id` (FK), `papel` enum `coordenador|auxiliar`, `ativo` (bool), timestamps.
- Único `(cozinha_id, user_id)`.
- **Justificativa de não usar coluna `cozinha_id` em `users`**: 1 coordenador pode cobrir múltiplas cozinhas (cidades vizinhas em execução indireta). Pivot é a generalização correta.

#### `beneficiaries.cozinha_id` (nova coluna)
- `nullable` (NGO comum continua funcionando sem cozinha).
- FK com `nullOnDelete` (se a cozinha sair, o beneficiário fica órfão mas vivo).
- Índice `(tenant_id, cozinha_id)`.

#### `regras_compliance` (coração)
- Colunas: `id`, `chave` (string slug, ex.: `taxa_administracao_teto`, `vedacao_mao_obra_percentual`, `classificacao_custeio_capital`), `parametros` (JSON), `fonte_legal` (string — ex.: "Portaria MDS 1.131/2025, art. 14"), `vigencia_inicio` (date), `vigencia_fim` (date nullable), `aplica_a_modalidade` enum nullable `direta|indireta|ambas`, `curado_por_user_id` (FK), `curado_em` (timestamp), `created_by_user_id`, timestamps.
- **Sem `tenant_id`** — regras são globais (vêm de portaria, valem pra todos os tenants). Tenant-specific overrides (raros) podem ser uma tabela auxiliar futura.
- **Justificativa de `parametros` JSON**: cada tipo de regra tem schema próprio (teto = `{percentual: 0.15}`, vedação = `{categorias_proibidas: [...]}`, classificação = `{regra_decisao: '...'}`). JSON dá flexibilidade sem explosão de colunas.
- **Justificativa de `curado_por_user_id`**: trilha de auditoria jurídica. Bloqueador do guardião se for null em produção.
- Índices: `(chave, vigencia_inicio)`, `(chave, vigencia_fim)`.

### 3.3 — Cronograma de Metas: JSON inline vs tabela auxiliar

Recomendo **tabela auxiliar `metas_cronograma`** (`meta_id`, `mes`, `quantidade`, `valor`) em vez de JSON. Razões:
- Queries de "realizado vs meta no mês X" ficam triviais (join + GROUP BY).
- Validação de soma das parcelas/metas é mais simples.
- Custo: +1 migration.

### 3.4 — Models propostos (sem código)

Cada model com `BelongsToTenant` + relações apropriadas + `casts` para enums:

- `TermoColaboracao` — `hasMany(PlanoTrabalho)`, `hasMany(Parcela)`, `hasMany(Cozinha)`, `belongsTo(Tenant)`.
- `PlanoTrabalho` — `belongsTo(TermoColaboracao)`, `hasMany(Meta)`.
- `Meta` — `belongsTo(PlanoTrabalho)`, `hasMany(MetaCronograma)`.
- `MetaCronograma` — `belongsTo(Meta)`.
- `Parcela` — `belongsTo(TermoColaboracao)`.
- `Cozinha` — `belongsTo(TermoColaboracao)`, `belongsToMany(User, 'cozinha_coordenadores')`, `hasMany(Beneficiary)`.
- `CozinhaCoordenador` (pivot) — pode usar `belongsToMany` direto ou model dedicado se houver lógica extra. Recomendo model dedicado (auditoria).
- `RegraCompliance` — sem `BelongsToTenant`. Escopo `vigenteEm($date)` — retorna regras com `vigencia_inicio <= date AND (vigencia_fim is null OR vigencia_fim >= date)`.

### 3.5 — `RegrasComplianceService`

Métodos centrais:
- `resolverRegra(string $chave, \DateTimeInterface $data, ?string $modalidade): array` — retorna `parametros` da regra vigente.
- `validar(string $chave, \DateTimeInterface $data, array $contexto): array` — valida um contexto contra a regra; retorna `['ok' => bool, 'violacao' => ?string, 'fonte_legal' => string]`.
- `registrarCuradoria(int $regraId, User $jurista, string $evidencia): void` — marca `curado_por_user_id` + `curado_em`. **Sem isso a regra não vale em prod.**

**Decisão importante:** o service **lê só** da tabela. Nenhum valor de regra hardcoded no código PHP. Bloqueador do guardião se for vista uma constante (`const TAXA_ADM_MAX = 0.15`) em qualquer lugar do código de execução.

### 3.6 — Camada sub-tenancy (papel coordenador de cozinha)

**Decisões**:
1. Adicionar valor `'coordenador_cozinha'` à coluna `User.role` (continua string, não enum).
2. **Não** criar `BelongsToCozinha` global trait — risco alto de quebrar models compartilhados (Beneficiary é usado também por NGO comum).
3. Em vez disso: criar **trait `ScopedByCozinhaForCoordinator`** opcional que aplica scope só quando o user atual é `coordenador_cozinha`. Aplicar nos models específicos da Cozinha (futuros `RegistroRefeicao`, `EstoqueMovimento`).
4. Para `Beneficiary` (compartilhado): no `BeneficiaryController` (lá em `routes/partials/ngo.php:97-125`), adicionar um filtro condicional pelo role — se for coordenador, `->where('cozinha_id', $cozinhasDoUser)`. Isso evita acoplar o domínio.
5. Adicionar Gates: `manage-termo`, `manage-cozinha`, `view-cozinha-propria` (este último permite coordenador).

### 3.7 — Tenant ativa Cozinha como?

Recomendo: **flag em `tenant_operational_profiles`**, não nova coluna em `tenants`. Por quê:
- A tabela `tenant_operational_profiles` já existe (vista em sprint anterior) com `categoria` e `instrucao`. É o lugar canônico de "configuração de perfil".
- Adicionar `enabled_modules` (JSON) com `['cozinha_solidaria' => true]` — sem novo schema, baixo blast radius.
- Decisão alternativa: nova coluna `tenants.product_kind` enum. Mais explícita, mais cara. Adia.

### 3.8 — Boot/observer/cache

- Observer pra `RegraCompliance` que invalida cache do `RegrasComplianceService` em alterações.
- Cache (Redis) de regras com TTL 5 min, key `regras_compliance.{chave}.{date}`. Curto pra refletir alteração jurídica logo.
- Em `AppServiceProvider::boot()`, registrar observers dentro do `try/catch` existente (padrão da casa).

### 3.9 — Critérios de aceite Fase 0 (do roadmap)

| Aceite | Como verificar | Teste a escrever |
|---|---|---|
| Coordenador de cozinha não enxerga outra cozinha nem dados da gestora | Feature test: login como coord, lista `/ngo/cozinhas` retorna só a própria; tentativa de acessar outra cozinha por ID → 403 | `tests/Feature/Cozinha/IsolamentoSubTenantTest.php` |
| Mudar a portaria vigente troca as regras aplicadas sem deploy | Unit test: criar duas `RegraCompliance` mesma `chave` com vigências distintas, mudar `now()`, conferir resolução | `tests/Unit/Services/RegrasComplianceServiceTest.php` |

---

## 4. Revisão `mds-compliance-guardian` (aplicada ao plano acima)

Checklist do guardião (de `cozinha/mds-compliance-guardian.md`):

| Item | Status na proposta | Notas |
|---|---|---|
| Regras lidas de tabela versionada, nunca hardcoded | ✅ | `regras_compliance` + `RegrasComplianceService` (§3.2, §3.5). Bloqueador se aparecer constante de regra no código. |
| Cada regra aponta sua portaria | ✅ | Coluna `fonte_legal` (§3.2). |
| Modelo suporta execução **direta e indireta** | ✅ | Enum em `termos_colaboracao.modalidade_execucao` + cópia em `cozinhas.modalidade_execucao` (§3.2). |
| Tudo reconcilia contra Plano de Trabalho | ✅ | Schema obriga FK em `metas.plano_trabalho_id`. Lastro/financeiro futuro vai amarrar em `metas`. |
| **Integridade do lastro** (refeição imutável após fechamento) | ⏭️ | Fora do escopo da Fase 0. Não bloqueia. |
| **LGPD de público vulnerável** (sem PII em log/API) | 🟡 | Beneficiary já tem encryption + bidx (§1.1). Quando criarmos `cozinhas`, **não** colocar foto/CPF de beneficiário lá. Coordenador é adulto, não cai no recorte. |
| **Sub-tenancy escopo `cozinha_id`** | ✅ | Pivot `cozinha_coordenadores` + scope opcional + Gates (§3.6). Bloqueador se Beneficiary query da Cozinha não filtrar `cozinha_id` quando role for coordenador. |
| Transferegov é export, não submit | ✅ | Fase 0 não toca em Transferegov — só estrutura de dados. |
| Migrations idempotentes e reversíveis | ✅ | `hasTable()`/`hasColumn()` guards (§3.1). |
| Curadoria jurídica humana antes de regra valer | ✅ | Coluna `curado_por_user_id` + service `registrarCuradoria` (§3.5). |

### 4.1 Bloqueadores levantados pelo guardião (manual)

**Nenhum bloqueador estrutural** no plano. Pendências de discussão (RECOMENDAÇÕES):

1. **R1 — Seed inicial de regras (#9):** definir se a primeira versão da `regras_compliance` entra com `vigencia_inicio` + `curado_em` retroativos baseados nas portarias citadas no roadmap (977/2024, 978/2024, 1.131/2025, 1.188/2026) **ou** se entram em branco aguardando curadoria. Recomendo: criar as **chaves** das regras na seed, mas deixar `parametros = null` e `curado_por_user_id = null`. O service deve **falhar com mensagem clara** ("regra não curada — abrir ticket jurídico") até a curadoria chegar. Evita chumbar números errados.

2. **R2 — Sub-tenancy de Beneficiary:** como `Beneficiary` é compartilhado entre NGO comum e Cozinha, o filtro por `cozinha_id` precisa ser **opt-in** no controller (não trait global). Risco de regressão se feito errado — recomendar **teste de regressão pra NGO comum** garantindo que sem `cozinha_id` a listagem continua igual.

3. **R3 — Modalidade direta:** na execução direta, a gestora é uma cozinha de equipamento próprio (roadmap §2). A migration #5 deve ter um **seeder/observer** que, ao salvar um Termo com `modalidade_execucao='direta'`, **cria automaticamente** uma Cozinha vinculada (mesmo nome, mesmo endereço do tenant). Simplifica UX e evita inconsistência.

4. **R4 — Auditoria de mudança de regra:** toda alteração em `regras_compliance` precisa de log (`AdminAuditLog` ou similar). Sem rastro de "quem mudou o teto de taxa de admin e quando" o módulo perde valor pra auditoria CGU.

5. **R5 — Documento dos termos/planos no S3:** colunas `documento_url` em `termos_colaboracao` e `planos_trabalho` devem usar S3 com URLs assinadas (pattern já usado no projeto). Bloquear acesso público.

---

## 5. Próximos passos sugeridos

1. **Aprovar este plano** (você) — particularmente as decisões §3.2, §3.6 e §3.7 onde há trade-offs.
2. Copiar `cozinha/mds-compliance-guardian.md` e `cozinha/prestacao-contas-engineer.md` para `.claude/agents/` (com ou sem renomeação dos demais).
3. Decidir curadoria jurídica (R1 — quem cadastra os valores reais das regras).
4. Confirmar se queremos rodar a Fase 0 numa **branch separada** (`feature/cozinha-fase-0`) para reduzir blast radius.
5. Após aprovação → implementação na ordem das 9 migrations + models + service + sub-tenancy + 2 testes de aceite.

---

## Apêndice — Dependências de Fase

- **Fase 0** desbloqueia tudo. Sem espinha de dados, fase 1 (registro de refeições) não tem onde plugar.
- **Fase 4 (execução financeira)** depende crítica do `RegrasComplianceService`. Reforço: nada de hardcode antes ou depois.
- **Fase 5 (prestação de contas)** depende do `prestacao-contas-engineer` carregado. Lembrar de copiar quando chegarmos lá.
