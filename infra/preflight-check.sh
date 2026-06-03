#!/usr/bin/env bash
# preflight-check.sh — checagem pré go-live (rodar NO VPS, na raiz do app)
# Cobre B2 (migrations) e B3 (.env de produção) do veredito final.
# Uso: bash infra/preflight-check.sh

set -u

APP_DIR="${APP_DIR:-/var/www/vivensi}"
ENV_FILE="$APP_DIR/.env"
cd "$APP_DIR" || { echo "❌ APP_DIR=$APP_DIR não existe"; exit 1; }
[ -f "$ENV_FILE" ] || { echo "❌ $ENV_FILE não encontrado"; exit 1; }

red()    { printf '\033[31m%s\033[0m\n' "$*"; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*"; }

fail=0

echo "── 1. Migrations pendentes ──────────────────────────────────────────"
pending=$(php artisan migrate:status --pending 2>/dev/null | grep -cE "Pending" || true)
if [ "$pending" -gt 0 ]; then
  red "❌ $pending migrations pendentes:"
  php artisan migrate:status --pending
  yellow "   Rodar: php artisan migrate --force"
  fail=1
else
  green "✅ Nenhuma migration pendente"
fi

echo ""
echo "── 2. APP_ENV / APP_DEBUG / APP_URL / SESSION_SECURE_COOKIE ─────────"
php artisan about --only=environment 2>/dev/null

# Lê valor direto do .env (env() do Laravel não funciona em php -r puro,
# e config:cache pode estar usando valores diferentes do .env vigente).
# Normaliza: tira aspas, espaços e comentários inline.
read_env() {
  grep -E "^${1}=" "$ENV_FILE" 2>/dev/null \
    | head -1 \
    | sed -E "s/^${1}=//; s/[[:space:]]*#.*$//; s/^['\"](.*)['\"]$/\1/; s/[[:space:]]+$//"
}

is_truthy() {
  case "$(echo "$1" | tr '[:upper:]' '[:lower:]')" in
    true|1|yes|on) return 0 ;;
    *) return 1 ;;
  esac
}

app_env=$(read_env APP_ENV)
app_debug=$(read_env APP_DEBUG)
app_url=$(read_env APP_URL)
session_secure=$(read_env SESSION_SECURE_COOKIE)
queue_conn=$(read_env QUEUE_CONNECTION)

[ "$app_env" = "production" ]      && green "✅ APP_ENV=production"          || { red "❌ APP_ENV='$app_env' (esperado: production)"; fail=1; }
is_truthy "$app_debug"             && { red "❌ APP_DEBUG='$app_debug' (esperado: false)"; fail=1; } \
                                   || green "✅ APP_DEBUG=$app_debug"
is_truthy "$session_secure"        && green "✅ SESSION_SECURE_COOKIE=$session_secure" \
                                   || { red "❌ SESSION_SECURE_COOKIE='$session_secure' (esperado: true)"; fail=1; }
[ "$queue_conn" = "redis" ]        && green "✅ QUEUE_CONNECTION=redis"      || { red "❌ QUEUE_CONNECTION='$queue_conn' (esperado: redis)"; fail=1; }
case "$app_url" in
  https://*) green "✅ APP_URL=$app_url" ;;
  *)         red "❌ APP_URL='$app_url' (deve começar com https://)"; fail=1 ;;
esac

echo ""
echo "── 3. PHP-FPM e extensões ───────────────────────────────────────────"
php -v | head -1
for ext in redis pdo_mysql mbstring bcmath gd intl zip; do
  if php -m | grep -qi "^${ext}$"; then green "✅ ext-$ext"; else red "❌ ext-$ext ausente"; fail=1; fi
done

echo ""
echo "── 4. Conectividade Redis e MySQL ───────────────────────────────────"
php -r "
require __DIR__.'/vendor/autoload.php';
\$app = require __DIR__.'/bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try { DB::connection()->getPdo(); echo \"✅ MySQL OK\n\"; } catch (Throwable \$e) { echo \"❌ MySQL: \".\$e->getMessage().\"\n\"; exit(1); }
try { Cache::store('redis')->put('preflight', '1', 5); echo \"✅ Redis OK\n\"; } catch (Throwable \$e) { echo \"❌ Redis: \".\$e->getMessage().\"\n\"; exit(1); }
" || fail=1

echo ""
echo "── 5. Supervisor (queue workers) ────────────────────────────────────"
if command -v supervisorctl >/dev/null 2>&1; then
  sudo supervisorctl status | grep -E "vivensi-(worker|scheduler)" || true
  running=$(sudo supervisorctl status | grep -cE "vivensi-(worker|scheduler).*RUNNING" || true)
  if [ "$running" -ge 1 ]; then
    green "✅ $running processo(s) Supervisor RUNNING"
  else
    red "❌ Nenhum worker Supervisor RUNNING"; fail=1
  fi
else
  yellow "⚠️  supervisorctl não encontrado (ok se você usa Horizon)"
fi

echo ""
echo "── 6. Caches de produção ────────────────────────────────────────────"
[ -f bootstrap/cache/config.php ] && green "✅ config:cache ativo"   || yellow "⚠️  Rodar: php artisan config:cache"
[ -f bootstrap/cache/routes-v7.php ] && green "✅ route:cache ativo" || yellow "⚠️  Rodar: php artisan route:cache"

echo ""
echo "── 7. Sentry recebendo eventos ──────────────────────────────────────"
dsn=$(read_env SENTRY_LARAVEL_DSN)
if [ -n "$dsn" ]; then
  green "✅ SENTRY_LARAVEL_DSN configurado (${dsn:0:32}...)"
else
  red "❌ SENTRY_LARAVEL_DSN vazio"; fail=1
fi

echo ""
echo "── 8. Backup do banco existe e é recente ────────────────────────────"
last_bk=$(ls -t storage/backups/*.sql.gz 2>/dev/null | head -1 || true)
if [ -n "$last_bk" ]; then
  age_h=$(( ( $(date +%s) - $(stat -c %Y "$last_bk") ) / 3600 ))
  if [ "$age_h" -lt 36 ]; then
    green "✅ Último backup: $last_bk (${age_h}h atrás)"
  else
    yellow "⚠️  Último backup tem ${age_h}h. Forçar: php artisan db:backup"
  fi
else
  red "❌ Nenhum backup em storage/backups/ — rodar: php artisan db:backup"; fail=1
fi

echo ""
if [ "$fail" -eq 0 ]; then
  green "════════════════════════════════════════════════════════════════"
  green "  ✅ PREFLIGHT OK — pode prosseguir com o deploy"
  green "════════════════════════════════════════════════════════════════"
  exit 0
else
  red   "════════════════════════════════════════════════════════════════"
  red   "  ❌ PREFLIGHT FALHOU — corrigir antes do go-live"
  red   "════════════════════════════════════════════════════════════════"
  exit 1
fi
