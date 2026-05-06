# Cybersecurity Scan Report — Vivensi SaaS

**Data:** 2026-05-02  
**Stack:** PHP 8.0 · Laravel 9 · MySQL · Laravel Horizon (Redis) · Pusher/Soketi (WebSocket) · Evolution API (WhatsApp) · Brevo · Asaas · AbacatePay · OpenPix · Meta Cloud API · Gemini AI · DeepSeek · Sentry

---

## Sumário Executivo

| Métrica | Valor |
|---------|-------|
| Total de checks avaliados | 90 |
| ✅ Passaram | 54 |
| ❌ Falharam | 36 |
| 🔴 Críticos | 5 |
| 🟠 Altos | 9 |
| 🟡 Médios | 14 |
| 🔵 Baixos | 8 |

---

## 🔴 Achados Críticos

---

### [CRÍTICO] APP_DEBUG=true no .env local — risco de stack trace em produção

**Categoria:** SECRETS / INFRA  
**Arquivo:** `.env:4`  
**Encontrado:**
```
APP_DEBUG=true
APP_ENV=local
```
**Impacto:** Se este `.env` for acidentalmente enviado para produção (ou se o servidor de homologação receber requisições externas), qualquer erro PHP exibirá stack trace completo, variáveis de ambiente, estrutura de diretórios e queries SQL para o usuário final. Facilita enormemente ataques de reconhecimento.  
**Fix:** Garantir que `.env` de produção tenha `APP_DEBUG=false` e `APP_ENV=production`. Adicionar verificação no `deploy.sh` que rejeite deploy se `APP_DEBUG=true`.

---

### [CRÍTICO] Sentry DSN real commitado no .env local

**Categoria:** SECRETS  
**Arquivo:** `.env:61`  
**Encontrado:**
```
SENTRY_LARAVEL_DSN=https://d1b76c...@o4510840139087872.ingest.de.sentry.io/4510840152916048
```
**Impacto:** O DSN do Sentry é tratado como segredo pois permite enviar eventos falsos para o seu projeto de monitoramento, poluindo alertas, causando cotas falsas e possibilitando enumeração de eventos de erro da aplicação.  
**Fix:** Remover o DSN real do `.env` local (use valor fictício). Rotacionar o DSN no painel do Sentry. No `.env.example` já está correto (vazio). Adicionar ao `.gitignore` qualquer referência explícita.

---

### [CRÍTICO] EVOLUTION_GLOBAL_KEY real exposta no .env local

**Categoria:** SECRETS  
**Arquivo:** `.env:65-66`  
**Encontrado:**
```
EVOLUTION_API_URL=https://evo.vivensi.app.br
EVOLUTION_GLOBAL_KEY=e838f5d5b86ea0fe27492c283c27498ffe0b250085896f1eaa8093baf0a3309e
```
**Impacto:** A Global API Key da Evolution API concede controle total sobre todas as instâncias WhatsApp do sistema — criar, destruir, ler mensagens, enviar para qualquer número. Com essa chave, um atacante pode comprometer todos os clientes SaaS simultaneamente.  
**Fix:** Rotacionar imediatamente a `EVOLUTION_GLOBAL_KEY` no painel da Evolution API. Nunca commitar o `.env` real. Usar gerenciador de segredos (AWS Secrets Manager, HashiCorp Vault ou Doppler) em produção.

---

### [CRÍTICO] DB_USERNAME=root / DB_PASSWORD="" no .env

**Categoria:** SECRETS / IAM  
**Arquivo:** `.env:15-16`  
**Encontrado:**
```
DB_USERNAME=root
DB_PASSWORD=
```
**Impacto:** Banco de dados acessível com usuário root sem senha. Se o servidor MySQL estiver com bind 0.0.0.0 (erro comum em XAMPP), qualquer host da rede consegue acesso total ao banco. Mesmo em localhost, PHP pode ser explorado para executar queries arbitrárias com privilégio máximo.  
**Fix:** Criar usuário MySQL dedicado com privilégios apenas nas tabelas necessárias. Definir senha forte. Em produção, restringir o bind do MySQL para `127.0.0.1` apenas.

---

### [CRÍTICO] TLS desabilitado em ambiente não-produção na EvolutionApiService

**Categoria:** INFRA / CÓDIGO  
**Arquivo:** `app/Services/EvolutionApiService.php:311-317`  
**Encontrado:**
```php
$shouldVerify = app()->environment('production')
    && !str_contains($this->baseUrl, 'localhost')
    && !str_contains($this->baseUrl, '127.0.0.1');

return $shouldVerify
    ? Http::withOptions(['verify' => true])
    : Http::withoutVerifying();  // ← TLS desabilitado em dev/staging
```
**Impacto:** Em qualquer ambiente que não seja `production` (dev, staging, homologação), a verificação SSL é desabilitada para TODOS os requests para a Evolution API. Isso abre vulnerabilidade a ataques MITM: um atacante na mesma rede pode interceptar e modificar mensagens WhatsApp de todos os clientes.  
**Fix:** Nunca desabilitar verificação TLS. Em dev, configurar a Evolution API com certificado autoassinado e adicionar via `verify => '/path/to/ca.crt'`. Remover o bloco condicional — sempre verificar.

---

## 🟠 Achados Altos

---

### [ALTO] SESSION_SECURE_COOKIE=false — cookies de sessão enviados em HTTP

**Categoria:** INFRA  
**Arquivo:** `config/session.php:173`  
**Encontrado:**
```php
'secure' => env('SESSION_SECURE_COOKIE', false),
```
**Impacto:** O cookie de sessão não tem flag `Secure`, podendo ser transmitido em conexões HTTP não criptografadas. Em redes Wi-Fi públicas, um atacante pode capturar o cookie e sequestrar sessões de usuários.  
**Fix:** Mudar o default para `true` ou garantir que o `.env` de produção tenha `SESSION_SECURE_COOKIE=true`. Adicionar verificação no `deploy.sh`.

---

### [ALTO] HSTS com max-age=300 (apenas 5 minutos) — proteção ineficaz

**Categoria:** INFRA  
**Arquivo:** `app/Http/Middleware/SecurityHeaders.php:29`  
**Encontrado:**
```php
$response->header('Strict-Transport-Security', 'max-age=300');
```
**Impacto:** O HSTS com apenas 5 minutos não oferece proteção real. Browsers não vão memorizar a política, e usuários que acessaram via HTTP continuarão vulneráveis a downgrade attacks e SSLstrip.  
**Fix:** Aumentar para `max-age=31536000; includeSubDomains` após confirmar SSL estável. O TODO no código já reconhece isso — é hora de executar.

---

### [ALTO] CSP muito permissiva — sem restrição de script-src

**Categoria:** INFRA / CÓDIGO  
**Arquivo:** `app/Http/Middleware/SecurityHeaders.php:34`  
**Encontrado:**
```php
$response->header('Content-Security-Policy', "frame-ancestors 'self'; upgrade-insecure-requests;");
```
**Impacto:** A CSP atual não define `script-src`, `style-src`, `img-src`, etc. Isso significa que qualquer XSS bem-sucedido pode carregar e executar scripts arbitrários de qualquer origem.  
**Fix:** Implementar CSP completa com pelo menos: `default-src 'self'; script-src 'self' 'nonce-{nonce}'; style-src 'self' fonts.googleapis.com; img-src 'self' data:; connect-src 'self' *.sentry.io`.

---

### [ALTO] shell_exec() com dados fixos do SO em AdminController — sem sanitização de retorno

**Categoria:** CÓDIGO  
**Arquivo:** `app/Http/Controllers/AdminController.php:141,149,151`  
**Encontrado:**
```php
$free = shell_exec('free -m');
$disk = shell_exec("df -h | grep '/$' | awk '{print $4}'");
$uptime = shell_exec('uptime -p');
```
**Impacto:** Embora os argumentos sejam fixos (sem input do usuário), o retorno do `shell_exec()` é injetado diretamente na view sem escape. Se algum output do SO contiver `<script>` por corrupção de dados ou configuração maliciosa, ocorre XSS. Também é um padrão perigoso que pode ser replicado incorretamente com input do usuário.  
**Fix:** Substituir por APIs PHP nativas (`disk_free_space()`, `sys_getloadavg()`, `/proc/meminfo`) sem necessidade de shell. Eliminar `shell_exec()` completamente.

---

### [ALTO] Webhook da WhatsApp Meta (webhook global) sem validação de assinatura HMAC

**Categoria:** CÓDIGO / SECRETS  
**Arquivo:** `app/Http/Controllers/WhatsappController.php` (método `webhook`)  
**Impacto:** O endpoint `POST /api/whatsapp/webhook` processa eventos do WhatsApp Meta Cloud API sem verificar a assinatura `X-Hub-Signature-256`. Qualquer pessoa que descubra a URL pode enviar eventos falsos (mensagens falsas, atualizações de status falsas) que serão processados como legítimos.  
**Fix:** Implementar verificação HMAC-SHA256 com `META_APP_SECRET`: `hash_hmac('sha256', $rawBody, $appSecret)` e comparar com `X-Hub-Signature-256`.

---

### [ALTO] PagSeguro webhook sem verificação de assinatura

**Categoria:** CÓDIGO  
**Arquivo:** `app/Http/Controllers/Api/PagSeguroWebhookController.php`  
**Impacto:** O webhook do PagSeguro não verifica autenticidade do request. Um atacante pode forjar notificações de pagamento aprovado, ativando assinaturas sem pagar.  
**Fix:** Implementar verificação de assinatura conforme documentação do PagSeguro, similar ao que já existe no Asaas (`hash_equals`).

---

### [ALTO] BelongsToTenant scope não bloqueia unauthenticated requests

**Categoria:** CÓDIGO / IAM  
**Arquivo:** `app/Traits/BelongsToTenant.php:25-32`  
**Encontrado:**
```php
} else {
    // Contexto web sem autenticação (Webhooks)
    // $builder->whereRaw('0 = 1'); // Removido para permitir Webhooks
}
```
**Impacto:** Quando não há usuário autenticado, o global scope de tenant não aplica NENHUM filtro. Se qualquer endpoint público inadvertidamente consultar um Model com BelongsToTenant sem `withoutGlobalScopes()`, todos os registros de todos os tenants serão retornados.  
**Fix:** Restaurar `$builder->whereRaw('0 = 1')` como padrão para unauthenticated. Os controllers de webhook que precisam contornar isso devem usar explicitamente `withoutGlobalScopes()` (o que já fazem corretamente).

---

### [ALTO] Backup armazenado apenas localmente (storage/backups) — sem offsite

**Categoria:** BACKUP  
**Arquivo:** `app/Console/Commands/BackupDatabase.php:18`  
**Encontrado:**
```php
$dir = storage_path('backups');
```
**Impacto:** Backups no mesmo servidor que a aplicação são destruídos junto com ela em caso de falha catastrófica de disco, ransomware ou comprometimento do servidor. Retenção de 7 dias sem cópia offsite não atende RPO mínimo para SaaS.  
**Fix:** Após gerar o backup local, fazer upload para S3 (ou outro objeto storage remoto). Usar `spatie/laravel-backup` que já faz isso nativamente. Habilitar S3 Object Lock (WORM) para proteção contra ransomware.

---

### [ALTO] Backup sem criptografia em repouso

**Categoria:** BACKUP  
**Arquivo:** `app/Console/Commands/BackupDatabase.php:42-51`  
**Impacto:** Os dumps `.sql.gz` contêm dados sensíveis de todos os tenants (transações, doadores, documentos) sem criptografia. Se o servidor for comprometido ou o arquivo copiado, todos os dados ficam expostos.  
**Fix:** Adicionar criptografia GPG ao backup: `mysqldump ... | gzip | gpg --encrypt --recipient backup@vivensi.com.br > arquivo.sql.gz.gpg`. Ou usar `spatie/laravel-backup` com `password` configurado.

---

## 🟡 Achados Médios

---

### [MÉDIO] PUSHER_APP_SECRET exposta com valor real no .env local

**Categoria:** SECRETS  
**Arquivo:** `.env:48`  
**Encontrado:** `PUSHER_APP_SECRET=vivensi-secret`  
**Impacto:** Permite forjar eventos WebSocket como qualquer tenant.  
**Fix:** Usar valor aleatório forte (`openssl rand -hex 32`). Rotacionar se esse valor já foi comprometido.

---

### [MÉDIO] .env.example contém placeholder literal "seu_token_secreto_aqui"

**Categoria:** SECRETS  
**Arquivo:** `.env.example:90`  
**Encontrado:** `DEPLOY_WEBHOOK_SECRET=seu_token_secreto_aqui`  
**Impacto:** Desenvolvedores descuidados podem copiar o example e usar o placeholder literal em produção.  
**Fix:** Substituir por instruções claras: `DEPLOY_WEBHOOK_SECRET=# Gerar com: openssl rand -hex 32`.

---

### [MÉDIO] SESSION_LIFETIME=120 minutos — sessão não expira em inatividade real

**Categoria:** IAM  
**Arquivo:** `.env:23`, `config/session.php:34`  
**Impacto:** Sessões de 2 horas sem invalidação por inatividade. Se um usuário deixar o computador desbloqueado, qualquer pessoa pode acessar a conta.  
**Fix:** Reduzir para 60 minutos para usuários normais. Implementar detecção de inatividade no front-end (JavaScript timeout com aviso).

---

### [MÉDIO] exec() sem Process Isolation no BackupDatabase

**Categoria:** CÓDIGO  
**Arquivo:** `app/Console/Commands/BackupDatabase.php:54`  
**Encontrado:** `exec($cmd, $output, $exitCode);`  
**Impacto:** Embora `escapeshellarg()` seja usado corretamente, `exec()` herda as variáveis de ambiente do processo PHP (incluindo todas as env vars). Preferível usar `Process` do Symfony para maior isolamento.  
**Fix:** Substituir por `Symfony\Component\Process\Process` com array de argumentos (imune a shell injection por design).

---

### [MÉDIO] Rate limiting ausente no webhook PagSeguro e OpenPix

**Categoria:** CÓDIGO  
**Arquivo:** `routes/api.php:42`, `routes/web.php:652`  
**Encontrado:**
```php
Route::post('/webhooks/pagseguro', ...);  // Sem throttle
Route::post('/openpix/webhook', ...);    // Sem throttle
```
**Impacto:** Esses endpoints podem ser usados em ataques de DoS ou flood de eventos falsos, causando alto consumo de CPU/banco por processamento de jobs.  
**Fix:** Adicionar `->middleware('throttle:200,1')` consistentemente em todos os webhooks.

---

### [MÉDIO] Logs sem request_id — impossível correlacionar eventos de um mesmo request

**Categoria:** LOGS  
**Impacto:** Com múltiplos workers/requests concorrentes, logs de erros e informações de diferentes requests ficam misturados. Diagnóstico de incidentes torna-se extremamente difícil.  
**Fix:** Adicionar middleware que gera `X-Request-ID` e injeta no contexto de log via `Log::withContext(['request_id' => $uuid])`.

---

### [MÉDIO] Sem SBOM (Software Bill of Materials) gerado

**Categoria:** DEPENDÊNCIAS  
**Impacto:** Impossível auditar automaticamente CVEs em dependências transitivas sem um SBOM.  
**Fix:** Adicionar `composer sbom` ou usar ferramenta como `cyclonedx/cyclonedx-php-composer` no CI/CD.

---

### [MÉDIO] laravel/framework ^9.0 — versão desatualizada (Laravel 11 é o atual LTS)

**Categoria:** DEPENDÊNCIAS  
**Arquivo:** `composer.json:14`  
**Impacto:** Laravel 9 atingiu fim de suporte em agosto de 2024. Não recebe mais patches de segurança. Vulnerabilidades descobertas desde então não serão corrigidas.  
**Fix:** Planejar migração para Laravel 11 (LTS). Mínimo: atualizar para última tag do Laravel 9.x enquanto se planeja a migração.

---

### [MÉDIO] laravel/sanctum ^2.14 — versão antiga

**Categoria:** DEPENDÊNCIAS  
**Arquivo:** `composer.json:16`  
**Impacto:** Sanctum 2.x tem várias melhorias de segurança nas versões 3.x e 4.x. Token abilities e SPA authentication foram revisados.  
**Fix:** Atualizar para Sanctum 4.x compatível com Laravel 11 na migração planejada.

---

### [MÉDIO] fruitcake/laravel-cors ^2.0 — pacote abandonado

**Categoria:** DEPENDÊNCIAS  
**Arquivo:** `composer.json:11`  
**Impacto:** O pacote `fruitcake/laravel-cors` foi descontinuado. O Laravel 9+ inclui CORS nativo. Pacotes abandonados não recebem patches de segurança.  
**Fix:** Remover `fruitcake/laravel-cors` e usar a implementação nativa do Laravel (já configurada em `config/cors.php`).

---

### [MÉDIO] doctrine/dbal:"*" e guzzlehttp/guzzle:"*" — wildcard sem versão fixada

**Categoria:** DEPENDÊNCIAS  
**Arquivo:** `composer.json:10,12`  
**Impacto:** Wildcards `"*"` podem instalar qualquer versão futura, incluindo versões com breaking changes ou vulnerabilidades. O `composer.lock` mitiga isso em deploy, mas `composer update` pode introduzir regressões.  
**Fix:** Fixar versões com constraints semânticas: `"doctrine/dbal": "^3.0"`, `"guzzlehttp/guzzle": "^7.0"`.

---

### [MÉDIO] Política de privacidade e termos não verificados programaticamente

**Categoria:** LGPD  
**Impacto:** As rotas `/privacidade` e `/termos` existem, mas não há verificação automatizada de que estão atualizadas ou que o conteúdo é exibido antes do cadastro.  
**Fix:** Adicionar campo `privacy_accepted_at` no registro de usuários. Forçar aceite explícito com checkbox no formulário de cadastro com timestamp.

---

### [MÉDIO] Direito ao esquecimento (LGPD Art. 18) sem implementação verificável

**Categoria:** LGPD  
**Impacto:** Não há endpoint ou processo documentado para deleção real de dados de usuário (cascade delete de todos os dados do tenant). `destroyTenant()` deleta users e tenant, mas não dados associados nas demais tabelas.  
**Fix:** Implementar soft delete completo + comando artisan `tenant:delete {id}` que remove cascata todos os dados relacionados, com audit trail.

---

### [MÉDIO] Sem alertas configurados para eventos suspeitos nos logs

**Categoria:** LOGS  
**Impacto:** Logins falhos repetidos, tentativas de acesso admin, webhooks com token inválido são logados mas não disparam alertas automáticos. Um ataque de força bruta pode passar despercebido.  
**Fix:** Configurar canais de log no Sentry (já integrado) com alertas para `Log::warning` e `Log::error` específicos. Criar regras de alerta no Sentry para `Evolution Webhook: token inválido` e `Unauthorized` em webhooks.

---

## 🔵 Achados Baixos

---

### [BAIXO] CORS_ALLOWED_ORIGINS não definido no .env local

**Categoria:** INFRA  
**Arquivo:** `.env` (ausente), `config/cors.php:22`  
**Encontrado:** `array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')))` retorna array vazio.  
**Impacto:** CORS provavelmente bloqueando todos os requests cross-origin legítimos, ou configuração inconsistente entre ambientes.  
**Fix:** Definir `CORS_ALLOWED_ORIGINS=http://localhost` no `.env` local.

---

### [BAIXO] Documentos de análise e diagnóstico sensíveis na raiz do projeto

**Categoria:** INFRA  
**Arquivos:** `AUDIT_REPORT.md`, `mensageria.md`, `nc5hub-briefing.md`, `relatorio_analise_vivensi.md`, `MEMORANDO_TECNICO_MIGRACAO.md`, etc.  
**Impacto:** Esses arquivos contêm arquitetura interna, vulnerabilidades conhecidas, credenciais de referência e estratégias de negócio. Se o servidor web servir a raiz do projeto (não apenas `/public`), ficam expostos.  
**Fix:** Mover para `/docs/` fora do webroot. Adicionar ao `.gitignore` arquivos sensíveis de análise. Garantir que o webroot do Nginx/Apache aponte para `/public`.

---

### [BAIXO] Ausência de MFA para acesso administrativo

**Categoria:** IAM  
**Impacto:** O painel super admin (`/admin`) não exige MFA. Um ataque de credential stuffing ou força bruta bem-sucedido dá acesso total ao SaaS.  
**Fix:** Implementar TOTP (Laravel Fortify com 2FA) para usuários com role `super_admin`.

---

### [BAIXO] Log de acesso administrativo sem rastreamento de IP

**Categoria:** LOGS / IAM  
**Impacto:** Logins no painel admin não logam IP de origem, dificultando detecção de acesso não autorizado.  
**Fix:** Adicionar `Log::info('Admin login', ['user' => $user->email, 'ip' => $request->ip()])` no `LoginController`.

---

### [BAIXO] Retenção de logs inadequada — sem configuração explícita de 90 dias

**Categoria:** LOGS  
**Impacto:** Logs do Laravel usam `LOG_CHANNEL=stack` sem rotation configurada. Em produção, logs podem crescer indefinidamente ou ser sobrescritos.  
**Fix:** Configurar `LOG_CHANNEL=daily` com `LOG_DAILY_DAYS=90` no `.env` de produção.

---

### [BAIXO] Sem RTO/RPO documentados formalmente

**Categoria:** BACKUP  
**Impacto:** Sem definição formal de Recovery Time Objective e Recovery Point Objective, não há como medir se o backup atual atende aos requisitos de negócio.  
**Fix:** Documentar RTO/RPO no `PDR_VIVENSI.md` ou criar `DISASTER_RECOVERY.md` com runbook.

---

### [BAIXO] Backup com retenção de apenas 7 dias

**Categoria:** BACKUP  
**Arquivo:** `app/Console/Commands/BackupDatabase.php:11`  
**Impacto:** Ataques de ransomware ou corrupção silenciosa de dados podem não ser descobertos em 7 dias. Recomendação mínima para SaaS é 30 dias.  
**Fix:** Aumentar retenção para 30 dias no servidor local + retenção de 1 ano em cold storage S3 (Glacier).

---

### [BAIXO] Sem verificação de integridade do backup

**Categoria:** BACKUP  
**Impacto:** Não há verificação automática de que o backup gerado é válido e restaurável.  
**Fix:** Após gerar o backup, executar `mysqlcheck` ou restaurar em banco temporário e verificar contagem de tabelas. Logar o resultado.

---

## ✅ Categorias com Checks que Passaram

### SECRETS
- [x] `.env` está no `.gitignore` (linha 7)
- [x] `.env.example` existe e não contém chaves reais (com exceção do placeholder de DEPLOY)
- [x] Secrets acessados via `env()` e `config()`, não hardcoded em código
- [x] Webhook secrets verificados com `hash_equals()` (Asaas, AbacatePay, OpenPix)

### CÓDIGO
- [x] Nenhum uso de `DB::raw()` com input de usuário encontrado — Eloquent ORM usado corretamente (proteção contra SQLi)
- [x] Sem uso de `yaml_parse()`, `unserialize()` com dados externos (sem risco de desserialização)
- [x] Sem uso de `exec()` / `shell_exec()` com input do usuário (apenas dados fixos do SO)
- [x] CSRF: Laravel VerifyCsrfToken ativo com exceções apenas para webhooks legítimos
- [x] `$fillable` definido em todos os Models (sem `$guarded = []` irrestrito)
- [x] Rate limiting aplicado em login (10/min), registro (10/min), reset de senha (5/min), raffle (20/min)
- [x] Multi-tenant isolation via `BelongsToTenant` global scope em todos os models críticos
- [x] IDOR protegido nos controllers com `findForTenant()` verificando `tenant_id`
- [x] Sem `open redirect` detectado — todos os redirects usam rotas nomeadas ou paths fixos
- [x] Respostas de erro não expõem stack traces (APP_DEBUG=false no .env.example)

### INFRA
- [x] `X-Frame-Options: SAMEORIGIN` configurado (anti-clickjacking)
- [x] `X-Content-Type-Options: nosniff` configurado
- [x] `X-XSS-Protection: 1; mode=block` configurado
- [x] `Referrer-Policy` configurado
- [x] CORS configurado com allowlist via env (não `*`)
- [x] Session cookie com `HttpOnly: true`
- [x] Session cookie com `SameSite: lax`
- [x] Session data criptografada (`SESSION_ENCRYPT=true`)

### IAM
- [x] Passwords com `Hash::make()` (bcrypt via Laravel)
- [x] Reset de senha via token assinado com expiração
- [x] Super Admin verificado por role em todos os endpoints admin
- [x] Middleware `super_admin` configurado para grupo `/admin`
- [x] Tokens de webhook com `hash_equals()` (timing-safe comparison)

### LGPD
- [x] Rota `/privacidade` e `/termos` existem
- [x] Cookie consent com revogação implementada (`/cookie/accept`, `/cookie/revoke`)
- [x] Mecanismo de consentimento LGPD presente

### LOGS
- [x] Eventos de autenticação parcialmente logados (login, webhook invalido)
- [x] Sentry DSN configurado para captura de erros em produção
- [x] Erros de webhook logados com contexto (IP, token prefix)
- [x] Logs de backup com informações de arquivo e tamanho

### BACKUP
- [x] Backup automático agendado (`$schedule->command('db:backup')` no Kernel)
- [x] Backup comprimido com gzip
- [x] Rotação de backups antigos implementada (7 dias)
- [x] Credenciais do banco via `MYSQL_PWD` (não expostas no comando)

---

## Plano de Remediação — Prioridade

| # | Severidade | Achado | Esforço |
|---|-----------|--------|---------|
| 1 | 🔴 CRÍTICO | Rotacionar EVOLUTION_GLOBAL_KEY comprometida | 30 min |
| 2 | 🔴 CRÍTICO | Rotacionar Sentry DSN comprometido | 15 min |
| 3 | 🔴 CRÍTICO | Corrigir TLS bypass no EvolutionApiService | 1h |
| 4 | 🔴 CRÍTICO | DB root sem senha — criar usuário dedicado | 2h |
| 5 | 🔴 CRÍTICO | Garantir APP_DEBUG=false em produção | 30 min |
| 6 | 🟠 ALTO | Implementar HMAC no webhook Meta WhatsApp | 2h |
| 7 | 🟠 ALTO | Implementar assinatura no webhook PagSeguro | 1h |
| 8 | 🟠 ALTO | Restaurar bloqueio unauthenticated no BelongsToTenant | 1h |
| 9 | 🟠 ALTO | SESSION_SECURE_COOKIE=true em produção | 15 min |
| 10 | 🟠 ALTO | HSTS max-age=31536000 | 15 min |
| 11 | 🟠 ALTO | Backup offsite (S3) + criptografia | 4h |
| 12 | 🟡 MÉDIO | CSP completa | 3h |
| 13 | 🟡 MÉDIO | Substituir shell_exec() por APIs PHP nativas | 2h |
| 14 | 🟡 MÉDIO | Atualizar dependências desatualizadas | 4h |

---

*Relatório gerado automaticamente por Antigravity Security Auditor · Vivensi SaaS · 2026-05-02*
