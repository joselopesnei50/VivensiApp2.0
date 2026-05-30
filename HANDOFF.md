# HANDOFF — Continuação no terminal local

Branch com todo o trabalho: **`claude/beautiful-johnson-6V2ep`** (no GitHub, repo `joselopesnei50/VivensiApp2.0`).
PR aberto: **#1** → https://github.com/joselopesnei50/VivensiApp2.0/pull/1

---

## 1. Como pegar TUDO no seu terminal (não precisa re-executar nada)

```bash
cd /caminho/do/seu/projeto        # ex: /var/www/vivensi
git fetch origin claude/beautiful-johnson-6V2ep
git checkout claude/beautiful-johnson-6V2ep
git pull origin claude/beautiful-johnson-6V2ep
```

Pronto — todos os commits abaixo estarão na sua máquina, e você revê o diff com:

```bash
git log --oneline origin/main..HEAD     # lista os commits
git diff origin/main...HEAD             # diff completo vs main
```

---

## 2. Commits desta branch (em ordem)

| Commit | O que faz |
|---|---|
| `d3e5277` | Relatório de auditoria (`GO_LIVE_AUDIT_2026-05-30.md`) |
| `eb8be5b` | **P0/P1**: throttle nos webhooks, expiração de token, senha forte, telefone exato no bot |
| `72272ca` | **P2**: sanitização XSS, hash_equals no bot, fila redis no supervisor, higiene de repo |
| `2813a8c` | Atualiza relatório com status de implementação |
| `8d03c6a` | **Revisão Codex**: throttle dedicado nos webhooks + reverte pin do composer |

---

## 3. Arquivos alterados e o porquê

### Segurança aplicada
- **`routes/api.php`** — webhooks Asaas/PagSeguro: `withoutMiddleware('throttle:api')` + `throttle:200,1`
  (saem do limite global de 60/min e ganham 200/min dedicado — conforme revisão do Codex).
- **`config/sanctum.php`** — tokens de API expiram em 30 dias via `SANCTUM_TOKEN_EXPIRATION`.
- **`app/Http/Controllers/RegisterController.php`**, **`Auth/ResetPasswordController.php`**,
  **`ProfileController.php`** — senha mínima 12 + maiúscula/minúscula + número
  (`\Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()`).
- **`app/Http/Controllers/Api/WhatsAppBotController.php`** — `findUserByPhone` faz match EXATO de telefone
  (recusa sufixo ambíguo entre tenants); `bot_token` comparado com `hash_equals`.
- **`app/helpers.php`** — nova função `sanitize_user_html()` (remove `on*=`, `javascript:/data:`, `style`).
- **`resources/views/pages/show.blade.php`** e **`resources/views/public/blog/show.blade.php`** —
  usam `sanitize_user_html()` no conteúdo público.
- **`supervisord.conf`** — driver `redis` (era `database`), worker de e-mails, `--tries=3 --backoff`.
- **`.gitignore`** + remoção do versionamento: zip da Evolution API, screenshots, PDF de transparência,
  dumps de paths (`local_files.txt`, `remote_files.txt`), `create_settings.sql`.

---

## 4. ⚠️ Situação do CI (importante)

As 2 falhas do CI são **PRÉ-EXISTENTES** (existem na `main`, não foram causadas por estas mudanças):

1. **Tests (PHP 8.1)** — falha no `php artisan migrate` (passo antes dos testes):
   ```
   SQLSTATE[HY000]: near "MODIFY": syntax error
   SQL: ALTER TABLE whatsapp_automations MODIFY COLUMN `trigger` ENUM(...)
   ```
   Uma migration usa sintaxe **MySQL** (`MODIFY COLUMN ENUM`) que o **SQLite** do CI não suporta.
   → Corrigir: tornar a migration cross-DB (Doctrine DBAL / `Schema::table` com `change()`), OU
     rodar o CI em MySQL em vez de SQLite (ajuste no `.github/workflows/laravel.yml`).

2. **Code Style (Pint)** — `laravel/pint` **não é dependência** do projeto (só aparece em "suggest").
   `./vendor/bin/pint --test` falha com "arquivo não encontrado", sempre.
   → Corrigir: `composer require --dev laravel/pint` e depois `./vendor/bin/pint` para formatar.
     ATENÇÃO: isso reformata o repo inteiro (diff grande) — faça num commit isolado.

Nota: localmente o `composer install` exige **PHP 8.1–8.3** (deps travadas não rodam em 8.4).
No CI é PHP 8.1, então instala normal.

---

## 5. 🔶 Itens NÃO aplicados de propósito (precisam de desenho + teste)

- **`BelongsToTenant` no model `User`** — o trait chama `Auth::check()` no global scope; no `User` isso
  causaria **recursão infinita** ao resolver o usuário logado (login quebrado). Precisa de um scope de tenant
  que não dependa de `Auth` durante `retrieveById`. O vetor explorável real (bot por telefone) já foi fechado.
- **Uploads sensíveis → disco privado** — `PublicRaffleController` (comprovantes PIX) e `NgoGrantController`
  salvam em disco `public`. Trocar só o disco quebra o acesso; precisa de rotas de download autenticadas +
  ajuste das views. (Docs de transparência são públicos de propósito e já têm controller de download.)
- **SSRF DNS-rebinding no proxy WhatsApp** — `WhatsappInstanceController::isPrivateOrMetadataHost()` valida
  o DNS no save, mas o Guzzle re-resolve no envio. Fixar o IP resolvido (`CURLOPT_RESOLVE`) no momento do request.

---

## 6. ✅ Checklist antes do deploy em produção

- [ ] `composer install` no deploy (mudou o `composer.json`).
- [ ] Testar fluxo de **cadastro e reset de senha** em homologação (a regra de senha forte afeta novos cadastros).
- [ ] Conferir que `QUEUE_CONNECTION=redis` no `.env` de produção bate com o supervisor (já confirmado: ok).
- [ ] Voltar o **repositório para privado** (foi deixado público durante a sessão).
- [ ] (Opcional) Limpar o histórico git se o PDF de transparência removido tiver dados reais sensíveis
      (`git filter-repo --path "relatorio-transparencia-gestor-ong-2026.pdf" --invert-paths`).

---

## 7. Comandos úteis para validar local (PHP 8.1)

```bash
# instalar deps (precisa PHP 8.1–8.3)
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# lint dos arquivos PHP alterados
php -l routes/api.php
php -l app/helpers.php

# rodar os testes (após corrigir a migration SQLite, ou apontando para MySQL)
cp .env.example .env && php artisan key:generate
php artisan migrate --force
./vendor/bin/pest
```

> Detalhe: o workflow chama `./vendor/bin/pest --ci`, mas a opção `--ci` não é válida na versão de pest
> travada (o binário responde como PHPUnit 9.6). Use só `./vendor/bin/pest` localmente.
