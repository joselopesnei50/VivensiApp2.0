# AUDIT_REPORT — Vivensi SaaS

**Data da auditoria:** 2026-04-26  
**Auditor:** Claude Code (Engenheiro Sênior)  
**Versão analisada:** Branch principal, ambiente localhost/XAMPP  

---

## Resumo Executivo

O Vivensi está arquiteturalmente bem estruturado para um SaaS de terceiro setor: multi-tenant isolado, filas Redis, broadcasting com autorização por tenant e pagamentos com dupla validação. O módulo de rifas implementa `lockForUpdate()` corretamente, prevenindo race conditions. Contudo, foram identificados **3 achados críticos** que comprometem a integridade financeira e a segurança antes de entrada em produção com usuários pagantes: o comando de expiração de reservas de bilhetes nunca é executado automaticamente, o endpoint público de reserva não tem rate limiting e todos os security headers de produção estão desativados por um comentário no Kernel. Os demais achados são mitigáveis antes ou logo após o go-live.

---

## Tabela de Achados por Severidade

| ID | Arquivo | Descrição | Severidade |
|----|---------|-----------|------------|
| A01 | `app/Console/Kernel.php` | `raffles:cleanup-reservations` ausente do agendador — bilhetes reservados jamais expiram | **CRÍTICO** |
| A02 | `app/Http/Kernel.php` | `SecurityHeaders` middleware comentado — sem X-Frame, HSTS, CSP em produção | **CRÍTICO** |
| A03 | `routes/web.php` | `POST /rifa/{slug}/reserve` sem rate limiting — endpoint público pode ser inundado | **CRÍTICO** |
| A04 | `app/Http/Controllers/PublicRaffleController.php` | `uploadReceipt()` sem validação de propriedade — qualquer pessoa com ID do bilhete pode fazer upload (IDOR) | **ALTO** |
| A05 | `config/session.php` | `SESSION_SECURE_COOKIE` padrão `false` — cookie de sessão pode ser enviado por HTTP | **ALTO** |
| A06 | `config/logging.php` | Stack de log usa `single` em vez de `daily` — arquivo único cresce indefinidamente | **ALTO** |
| A07 | `app/Console/Commands/BackupDatabase.php` | Backup salvo apenas localmente em `storage/backups` — sem envio para S3 off-site | **ALTO** |
| A08 | `database/migrations/...create_raffle_tickets_table.php` | Ausência de índice composto `(raffle_id, status)` — queries lentas sob carga | **MÉDIO** |
| A09 | `app/Http/Middleware/SecurityHeaders.php` | HSTS `max-age=300` (5 min) — insuficiente para produção, deve ser ≥ 1 ano | **MÉDIO** |
| A10 | `.env.example` | `DB_USERNAME=root`, `MAIL_HOST=mailhog`, `ASAAS_ENVIRONMENT=sandbox` como padrões perigosos | **MÉDIO** |
| A11 | `composer.json` | `"minimum-stability": "dev"` com `guzzle: "*"`, `doctrine/dbal: "*"` — versões indeterminadas | **MÉDIO** |
| A12 | `routes/web.php` | `/admin/health` requer autenticação — monitoramento externo (UptimeRobot etc.) não funciona | **MÉDIO** |
| A13 | `composer.json` | `fruitcake/laravel-cors` depreciado + `open-pix/php-sdk` não utilizado | **BAIXO** |
| A14 | `composer.json` | Laravel 9 EOL desde fevereiro 2024 — sem patches de segurança futuros | **BAIXO** |

---

## Top 5 Riscos — Corrigir ANTES de Qualquer Nova Feature

1. **A01 — Bilhetes reservados nunca expiram:** usuário reserva bilhetes, não paga e eles ficam bloqueados para sempre. Em rifas com poucos bilhetes isso paralisa as vendas.
2. **A03 — Reserva pública sem rate limiting:** bot pode reservar todos os bilhetes de uma rifa em segundos, bloqueando compradores legítimos.
3. **A02 — Security headers desativados:** clickjacking, XSS e MIME sniffing ficam sem camada de proteção no navegador.
4. **A04 — IDOR no upload de comprovante:** qualquer pessoa que adivinhe ou enumere um ID de bilhete pode substituir o comprovante de pagamento.
5. **A07 — Backup apenas local:** falha de disco ou instância comprometida destrói o backup junto com o banco.

---

## Lista Completa de Achados

### A01 — CRÍTICO: Limpeza de reservas de bilhetes não está agendada

- **Arquivo:** `app/Console/Kernel.php`
- **Descrição:** O comando `raffles:cleanup-reservations` existe em `app/Console/Commands/CleanupRaffleReservations.php` e libera bilhetes com `reserved_at` > 30 minutos. Porém, nunca foi adicionado ao `schedule()` do Kernel. Bilhetes reservados e não pagos ficam com status `pending` indefinidamente.
- **Impacto:** Bloqueio permanente de bilhetes, perda de receita para a ONG.
- **Correção:** Adicionar `$schedule->command('raffles:cleanup-reservations')->everyFifteenMinutes()->withoutOverlapping();` ao Kernel.

---

### A02 — CRÍTICO: SecurityHeaders middleware desativado

- **Arquivo:** `app/Http/Kernel.php`, linha com `// \App\Http\Middleware\SecurityHeaders::class,`
- **Descrição:** O middleware existe, está implementado corretamente, mas foi comentado "temporariamente até estabilização do servidor" e nunca foi reativado.
- **Impacto:** Sem `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `HSTS` e `CSP` em nenhuma resposta.
- **Correção:** Descomentar a linha.

---

### A03 — CRÍTICO: Reserva pública sem rate limiting

- **Arquivo:** `routes/web.php`, linha `Route::post('/rifa/{slug}/reserve', ...)`
- **Descrição:** A rota pública de reserva de bilhetes não tem nenhum middleware de throttle. Qualquer bot pode fazer POST ilimitado, reservando todos os bilhetes antes de compradores legítimos.
- **Impacto:** Negação de serviço financeiro para a ONG; bloqueio de rifas inteiras.
- **Correção:** Adicionar `->middleware('throttle:20,1')` à rota.

---

### A04 — ALTO: IDOR no upload de comprovante

- **Arquivo:** `app/Http/Controllers/PublicRaffleController.php`, método `uploadReceipt()`
- **Descrição:** A rota `POST /rifa/ticket/{ticket}/comprovante` aceita qualquer `$ticket` por ID. Não verifica se o bilhete pertence à rifa correta, se o IP/email bate com o reservante, ou qualquer autenticação. Um atacante pode fazer upload de comprovante falso em bilhetes de outras pessoas.
- **Impacto:** Fraude: comprovante falso pode enganar o operador da ONG e levar à confirmação indevida de pagamento.
- **Correção:** Validar que `$ticket->buyer_email` bate com um campo enviado na requisição, ou usar token assinado na URL.

---

### A05 — ALTO: Cookie de sessão inseguro por padrão

- **Arquivo:** `config/session.php`
- **Descrição:** `'secure' => env('SESSION_SECURE_COOKIE', false)` — o padrão é `false`. Se a variável não for explicitamente setada no `.env` de produção, o cookie de sessão é transmitido também por HTTP.
- **Correção:** Mudar padrão para `true` no config.

---

### A06 — ALTO: Log stack aponta para canal `single`

- **Arquivo:** `config/logging.php`
- **Descrição:** O canal `stack` tem `'channels' => ['single']`. O canal `single` grava em um único arquivo que cresce indefinidamente (o scheduler zera o arquivo a cada 50 MB — apagando o histórico em vez de rotacionar). Em produção, `daily` com retenção de 14 dias é o padrão correto.
- **Correção:** Mudar `'channels' => ['daily']` na stack.

---

### A07 — ALTO: Backup apenas local, sem envio para S3

- **Arquivo:** `app/Console/Commands/BackupDatabase.php`
- **Descrição:** O backup gera `.sql.gz` em `storage/backups/` com retenção de 7 dias. Não há envio para S3. Se a instância for comprometida, corrompida ou o disco falhar, o backup é perdido junto.
- **Correção:** Após `gzip`, fazer upload para S3 com `Storage::disk('s3')->putFileAs('backups/', $file, basename($file))` e depois deletar o arquivo local.

---

### A08 — MÉDIO: Índice composto ausente em `raffle_tickets`

- **Arquivo:** `database/migrations/2026_03_31_114200_create_raffle_tickets_table.php`
- **Descrição:** A migration só tem `unique(['raffle_id', 'number'])`. Não há índice em `status`. Queries como `WHERE raffle_id = ? AND status = 'available'` (usada em toda reserva) fazem full scan da tabela de bilhetes da rifa.
- **Correção:** Adicionar migration com `$table->index(['raffle_id', 'status'])`.

---

### A09 — MÉDIO: HSTS max-age muito curto

- **Arquivo:** `app/Http/Middleware/SecurityHeaders.php`
- **Descrição:** `Strict-Transport-Security: max-age=300` (5 minutos). Browsers descartam a diretiva após 5 min, sem proteção real contra downgrade.
- **Correção:** Mudar para `max-age=31536000; includeSubDomains`.

---

### A10 — MÉDIO: Valores perigosos no `.env.example`

- **Arquivo:** `.env.example`
- **Descrição:** `DB_USERNAME=root` (sem princípio do menor privilégio), `MAIL_HOST=mailhog` (mail trap de dev), `ASAAS_ENVIRONMENT=sandbox` (pode ir a produção em sandbox acidentalmente).
- **Correção:** Alterar para valores que exijam configuração explícita.

---

### A11 — MÉDIO: Versões wildcard em dependências críticas

- **Arquivo:** `composer.json`
- **Descrição:** `"guzzlehttp/guzzle": "*"`, `"doctrine/dbal": "*"`, `"guzzlehttp/psr7": "*"` — sem pin de versão. Um `composer update` pode quebrar o sistema.
- **Correção:** Fixar versões: `"guzzlehttp/guzzle": "^7.8"`, `"doctrine/dbal": "^3.8"`, `"guzzlehttp/psr7": "^2.6"`.

---

### A12 — MÉDIO: Healthcheck requer autenticação

- **Arquivo:** `routes/web.php` linha ~496
- **Descrição:** `GET /admin/health` está dentro do grupo `auth` + `super_admin`. Ferramentas de monitoramento externo (UptimeRobot, AWS Health Check, Pingdom) não conseguem acessar.
- **Correção:** Criar `GET /ping` ou `GET /health` público que retorne `{"status":"ok","ts":"..."}` sem autenticação.

---

### A13 — BAIXO: Dependências depreciadas/não utilizadas

- `fruitcake/laravel-cors` — depreciado; Laravel 9 já possui CORS nativo via `config/cors.php`.
- `open-pix/php-sdk` — SDK OpenPix não utilizado (sistema usa AbacatePay). Superfície de ataque desnecessária.

---

### A14 — BAIXO: Laravel 9 EOL

- Laravel 9 atingiu fim de vida em fevereiro de 2024. Não recebe mais patches de segurança. Planejar upgrade para Laravel 11.

---

*Relatório gerado automaticamente por auditoria de código em 2026-04-26.*
