# Diagnóstico de Migração — Laravel 9 / PHP 8.0 → Laravel 11 / PHP 8.3

> Gerado em: 2026-06-01 | Apenas leitura e análise — nenhum arquivo foi alterado.

---

## Resumo Executivo

| Item | Valor |
|---|---|
| Esforço estimado | **Alto** |
| Horas humanas totais (excluindo Shift) | **10–16 horas** |
| Bloqueadores críticos | **4** |
| Código PHP limpo (depreciações 8.2/8.3) | ✅ Sim |
| Pode manter `Http/Kernel.php` no L11? | ✅ Sim |

**4 bloqueadores críticos a resolver:**
1. `fruitcake/laravel-cors` — pacote abandonado, referenciado no Kernel
2. `RouteServiceProvider` estende classe removida no L11
3. `DispatchesJobs` e `ValidatesRequests` removidos do Controller no L11
4. Sanctum 4 exige coluna `expires_at` em `personal_access_tokens`

---

## Seção 1 — Dependências

### Tabela completa

| Pacote | Versão atual | Versão L11 | Risco |
|---|---|---|---|
| `php` | `^8.0` | `^8.3` | Médio |
| `laravel/framework` | `^9.0` | `^11.0` | Alto |
| `laravel/sanctum` | `^2.14` | `^4.0` | Médio |
| `laravel/horizon` | `^5.0` | `^5.25+` | Baixo |
| `laravel/tinker` | `^2.7` | `^2.9` | Baixo |
| `spatie/laravel-permission` | `^6.25` | já compatível | Baixo |
| `sentry/sentry-laravel` | `^4.25` | já compatível | Baixo |
| `barryvdh/laravel-dompdf` | `^2.2` | `^3.0` | Médio |
| `doctrine/dbal` | `^3.10` | remover no L11 | Médio |
| `fruitcake/laravel-cors` | `^2.0.5` | **REMOVER** | **BLOQUEADOR** |
| `guzzlehttp/guzzle` | `^7.10` | `^7.x` | Baixo |
| `open-pix/php-sdk` | `^1.1` | não verificado | Médio |
| `pusher/pusher-php-server` | `^7.2` | compatível | Baixo |
| `pestphp/pest` | `^1.23` | `^3.0` | Alto |
| `phpunit/phpunit` | `^9.5` | `^11.0` | Médio |
| `nunomaduro/collision` | `^6.1` | `^8.0` | Médio |
| `spatie/laravel-ignition` | `^1.0` | `^2.0` | Médio |
| `fakerphp/faker` | `^1.9.1` | `^1.23` | Baixo |
| `laravel/sail` | `^1.0.1` | `^1.x` | Baixo |
| `mockery/mockery` | `^1.4.4` | `^1.6` | Baixo |
| `laravel-mix` | `^6` | migrar para Vite | Médio |

### Notas críticas por pacote

#### `fruitcake/laravel-cors` — BLOQUEADOR
Pacote abandonado. Referenciado em `app/Http/Kernel.php:19`:
```php
\Fruitcake\Cors\HandleCors::class
```
O `config/cors.php` já existe no projeto. Correção:
- Remover do `composer.json`
- Substituir a linha 19 do Kernel por `\Illuminate\Http\Middleware\HandleCors::class`

#### `laravel/sanctum` 2 → 4
A tabela `personal_access_tokens` não tem a coluna `expires_at`. O Sanctum 4 a exige. Será necessária uma migration antes do deploy em produção:
```php
$table->timestamp('expires_at')->nullable()->after('last_used_at');
```
A API pública (`HasApiTokens`, `createToken`, `plainTextToken`) não muda.

#### `laravel/horizon` 5 → 5.25+
Simples `composer update laravel/horizon`. Sem breaking changes de API. O `HorizonServiceProvider` e `config/horizon.php` permanecem iguais.

#### `barryvdh/laravel-dompdf` v2 → v3
A v3 mudou como opções são passadas — antes era array no segundo parâmetro, agora usa `->setOptions()`. Todos os controllers que geram PDF precisam ser revisados e adaptados.

#### `doctrine/dbal`
Obrigatório em L9/L10 para `->change()` e `->renameColumn()` nas migrations. No L11 o suporte é nativo — pode ser removido do `composer.json` ao migrar para L11, desde que nenhum pacote de terceiro o exija diretamente.

#### `pestphp/pest` 1 → 3 e `phpunit` 9 → 11
A principal mudança é que `/** @test */` no PHPUnit 11 deve virar `#[Test]`. Arquivos afetados:
- `tests/Feature/RaffleConcurrencyTest.php`
- `tests/Unit/RaffleCleanupCommandTest.php`
- `tests/Feature/TenantScopeTest.php`

O `phpunit.xml` também precisa atualizar o `xsi:noNamespaceSchemaLocation` para a versão 11.

#### `open-pix/php-sdk`
Compatibilidade com PHP 8.3 não verificada formalmente. O projeto o registra como singleton com try/catch, mitigando falhas de boot. Verificar manualmente antes de fazer o bump de PHP.

#### `laravel-mix` → Vite
Não é bloqueador para a migração do framework, mas necessário para novos assets no L11. Custo estimado:
- Substituir `webpack.mix.js` por `vite.config.js`
- Trocar `mix()` por `@vite()` nos arquivos Blade
- Renomear `MIX_*` para `VITE_*` no `.env` e `.env.example`

---

## Seção 2 — PHP 8.0 → 8.3: Varredura de Depreciações

**Resultado: código limpo.** Nenhuma ocorrência encontrada de:

| Verificação | Resultado |
|---|---|
| Propriedades dinâmicas sem declaração | ✅ Não encontrado |
| Interpolação `"${var}"` depreciada no PHP 8.2 | ✅ Não encontrado |
| `utf8_encode()` / `utf8_decode()` | ✅ Não encontrado |
| `each()` nativo do PHP (removido no 8.0) | ✅ Não encontrado |
| `mcrypt_*` (removido no 7.2) | ✅ Não encontrado |
| `mb_convert_encoding()` com aliases antigos | ✅ Não encontrado |
| Intersection types incompatíveis | ✅ Não encontrado |

O projeto já usa PHP 8.0 moderno (`?->`, `match`, constructor promotion, `catch` sem variável). **Nenhuma reescrita de linguagem necessária para ir de 8.0 a 8.3.**

---

## Seção 3 — Breaking Changes L9 → L10 → L11

### É possível manter `app/Http/Kernel.php` no L11?

**Sim.** O `laravel/framework ^11` ainda suporta o skeleton antigo. Os arquivos `app/Http/Kernel.php`, `app/Console/Kernel.php` e `app/Exceptions/Handler.php` continuam funcionando se presentes. O novo skeleton "slim" é padrão para projetos novos, mas **não é obrigatório** para migração de projetos existentes. Isso reduz significativamente o esforço total.

### O que é OBRIGATÓRIO mudar (mesmo mantendo o skeleton antigo)

| Arquivo | Linha | Problema | Correção |
|---|---|---|---|
| `app/Http/Kernel.php` | 61 | `$routeMiddleware` renomeada para `$middlewareAliases` no L11 | Renomear a propriedade |
| `app/Http/Controllers/Controller.php` | 6–7, 12 | `DispatchesJobs` e `ValidatesRequests` removidos no L11 | Remover os dois traits e imports |
| `app/Providers/RouteServiceProvider.php` | 6 | `Illuminate\Foundation\Support\Providers\RouteServiceProvider` removida no L11 | Mudar `extends` para `ServiceProvider` base |
| `app/Http/Middleware/Authenticate.php` | 15 | `redirectTo()` sem `: ?string` — obrigatório no L10 | Adicionar `: ?string` |
| Todos os 19 middlewares | `handle()` | Sem tipo de retorno — L10 exige | Adicionar `: mixed` |
| `AppServiceProvider`, `AuthServiceProvider`, etc. | `boot()`, `register()` | Sem `: void` — L10 exige | Adicionar `: void` |

### O que é apenas RECOMENDADO (não obrigatório) no L11

- Adotar o novo `bootstrap/app.php` slim
- Mover exceções para `->withExceptions()` em vez de `Handler.php`
- Usar `Schedule` inline em `bootstrap/app.php` em vez de `Console/Kernel.php`
- Mover `AuthServiceProvider` e `EventServiceProvider` para dentro de `AppServiceProvider`

### Rate limiters no `RouteServiceProvider`

O `RouteServiceProvider` define 6 rate limiters customizados que precisam ser preservados:

| Rate Limiter | Limite |
|---|---|
| `api` | 60 req/min por user/IP |
| `evo_webhook` | 300 req/min por token |
| `web_write` | 60 req/min por user/IP |
| `web_ai` | 10 req/min por user/IP |
| `web_ai_bulk` | 3 req/min por user/IP |
| `web_export` | 15 req/min por user/IP |

Se o skeleton antigo for mantido, o `RouteServiceProvider` continua sendo carregado via `config/app.php` e os rate limiters continuam funcionando **sem mudança**. A migração para `AppServiceProvider::boot()` só é necessária se o `RouteServiceProvider` for eliminado.

### Bug existente descoberto durante análise

`app/Http/Middleware/CheckSubscription.php:48` usa `Auth::logout()` sem `use Illuminate\Support\Facades\Auth`. Funciona por acidente via alias global, mas pode quebrar em certas configurações. Adicionar o import.

---

## Seção 4 — Suíte de Testes: O Que Quebra Hoje

**3 problemas bloqueadores no CI:**

### Problema 1 — Flag `--ci` inválida no Pest 1
`.github/workflows/laravel.yml:58`:
```bash
./vendor/bin/pest --ci  # --ci não existe no Pest 1
```
**Correção:** Remover a flag `--ci` (só existe no Pest 2+).

### Problema 2 — `laravel/pint` não está no `composer.json`
O CI roda `./vendor/bin/pint --test` mas o pacote não está instalado.
**Correção:** Adicionar ao `require-dev`:
```json
"laravel/pint": "^1.0"
```

### Problema 3 — Migration `MODIFY COLUMN ENUM` sem guard SQLite
`database/migrations/2026_05_28_120000_add_keyword_and_send_once_to_whatsapp_automations.php` — o HANDOFF confirma falha com `SQLSTATE: near "MODIFY": syntax error` no CI.
**Correção:** Garantir que o guard existe:
```php
if (DB::getDriverName() !== 'sqlite') {
    DB::statement("ALTER TABLE ... MODIFY COLUMN ...");
}
```

### PHPUnit `/** @test */` → `#[Test]`
Arquivos afetados (necessário ao migrar para Pest 3 / PHPUnit 11):
- `tests/Feature/RaffleConcurrencyTest.php`
- `tests/Unit/RaffleCleanupCommandTest.php`
- `tests/Feature/TenantScopeTest.php`

---

## Seção 5 — Plano em Degraus

### Degrau 0 — Corrigir a suíte de testes (pré-requisito)

**Esforço: Baixo | 2–3 horas | Laravel Shift: Não**

Tarefas:
1. Remover flag `--ci` de `.github/workflows/laravel.yml:58`
2. Adicionar `"laravel/pint": "^1.0"` ao `require-dev`
3. Verificar e corrigir guard SQLite na migration `2026_05_28_120000_...`
4. Adicionar `use Illuminate\Support\Facades\Auth;` em `CheckSubscription.php`

**Risco:** Nenhum — são correções de CI isoladas.

---

### Degrau 1 — PHP 8.0 → 8.3

**Esforço: Baixo | 1–2 horas | Laravel Shift: Não**

Tarefas:
1. Atualizar `composer.json`: `"php": "^8.3"`
2. Atualizar step do CI para `php-version: '8.3'`
3. Verificar `open-pix/php-sdk` — se não declarar suporte ao 8.3, usar `--ignore-platform-req=php` temporariamente
4. Rodar `composer update` e executar a suíte de testes em PHP 8.3

**Risco:** Baixo. O código não usa nenhuma função depreciada no 8.2/8.3. O único risco é algum pacote de terceiro não declarar suporte formal ao 8.3.

---

### Degrau 2 — Laravel 9 → 10

**Esforço: Médio | 2–3 horas | Laravel Shift: Parcial (~50%)**

Tarefas:
1. Atualizar `composer.json`:
   - `"laravel/framework": "^10.0"`
   - `"nunomaduro/collision": "^7.0"`
   - `"spatie/laravel-ignition": "^2.0"`
2. Adicionar `: void` em todos os `boot()`, `register()`, `commands()`, `schedule()` dos Providers e Kernels
3. Adicionar `: ?string` em `Authenticate.php:15`
4. Adicionar `: mixed` em todos os 19 middlewares customizados no método `handle()`
5. Em `Controller.php`: remover `use DispatchesJobs, ValidatesRequests` e seus imports
6. Remover `fruitcake/laravel-cors` do `composer.json`
7. Em `app/Http/Kernel.php:19`, substituir `\Fruitcake\Cors\HandleCors::class` por `\Illuminate\Http\Middleware\HandleCors::class`
8. No `phpunit.xml`: remover `processUncoveredFiles="true"` → substituir por `includeUncoveredFiles="true"`
9. Rodar `composer update` e `php artisan test`

**Risco principal:** CORS pode parar de funcionar se `config/cors.php` estiver mal configurado — testar cuidadosamente após a troca.

---

### Degrau 3 — Laravel 10 → 11

**Esforço: Alto | 5–8 horas | Laravel Shift: Parcial (~40%)**

Tarefas:
1. Atualizar `composer.json`:
   - `"laravel/framework": "^11.0"`
   - `"laravel/sanctum": "^4.0"`
   - `"nunomaduro/collision": "^8.0"`
   - `"pestphp/pest": "^3.0"`
   - `"pestphp/pest-plugin-laravel": "^3.0"`
   - `"phpunit/phpunit": "^11.0"`
   - Remover `doctrine/dbal`
2. Criar migration adicionando `expires_at` à tabela `personal_access_tokens`:
   ```php
   $table->timestamp('expires_at')->nullable()->after('last_used_at');
   ```
   > ⚠️ Executar esta migration NO VPS ANTES de qualquer outro deploy
3. Em `app/Http/Kernel.php:61`: renomear `$routeMiddleware` para `$middlewareAliases`
4. Em `app/Providers/RouteServiceProvider.php:6`: alterar `extends RouteServiceProvider` para `extends ServiceProvider`
5. Em `app/Http/Controllers/Controller.php`: remover `DispatchesJobs` e `ValidatesRequests` (se não feito no Degrau 2)
6. Atualizar `phpunit.xml` com schema do PHPUnit 11
7. Nos 3 arquivos de teste com `/** @test */`: substituir por `#[Test]` e adicionar `use PHPUnit\Framework\Attributes\Test`
8. Revisar todos os controllers que usam `barryvdh/laravel-dompdf` — adaptar para API da v3 com `->setOptions()`
9. Avaliar migração de `laravel-mix` para Vite (pode ser feito em paralelo)
10. Rodar `php artisan test` e corrigir falhas residuais

**Riscos principais:**
- `RouteServiceProvider` estendendo classe removida → erro fatal se não corrigida antes do deploy
- Sanctum sem coluna `expires_at` no banco de produção → quebra silenciosa de expiração de tokens
- Pest 3 + PHPUnit 11 pode revelar testes frágeis não cobertos pelos testes atuais
- `barryvdh/laravel-dompdf` v3: API diferente para opções de PDF

---

## Resumo de Esforço Total

| Degrau | Esforço bruto | Shift automatiza | Horas humanas |
|---|---|---|---|
| 0 — Testes verdes | 2–3h | Nada | **2–3h** |
| 1 — PHP 8.3 | 1–2h | Nada | **1–2h** |
| 2 — L9 → L10 | 4–6h | ~50% | **2–3h** |
| 3 — L10 → L11 | 8–12h | ~40% | **5–8h** |
| **Total** | — | — | **10–16 horas** |

---

## Arquivos que serão DELETADOS

- `app/Http/Kernel.php` *(opcional — pode ser mantido)*
- `app/Console/Kernel.php` *(opcional — pode ser mantido)*
- `app/Exceptions/Handler.php` *(opcional — pode ser mantido)*
- `app/Providers/RouteServiceProvider.php` *(obrigatório — classe base removida)*
- `app/Providers/BroadcastServiceProvider.php` *(opcional)*
- `app/Providers/EventServiceProvider.php` *(opcional)*

## Arquivos que serão REESCRITOS

- `bootstrap/app.php` *(se adotar skeleton novo)*
- `config/app.php` *(remover array `providers` se adotar skeleton novo)*

## Arquivos com MODIFICAÇÕES CIRÚRGICAS

| Arquivo | O que muda |
|---|---|
| `app/Http/Kernel.php` | Renomear `$routeMiddleware` → `$middlewareAliases` |
| `app/Http/Controllers/Controller.php` | Remover 2 traits e imports |
| `app/Providers/RouteServiceProvider.php` | Trocar classe pai |
| `app/Http/Middleware/Authenticate.php` | Adicionar `: ?string` |
| Todos os 19 middlewares | Adicionar `: mixed` no `handle()` |
| Todos os providers | Adicionar `: void` em `boot()`/`register()` |
| `composer.json` | Bump de versões + remoções |
| `.env.example` | `MIX_*` → `VITE_*` (se migrar para Vite) |

---

*Relatório gerado com base em análise estática do código em 2026-06-01. Nenhum arquivo foi alterado.*
