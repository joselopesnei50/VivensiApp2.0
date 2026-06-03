#!/usr/bin/env bash
# rotate-db-password.sh — rotação segura da senha do MySQL do Vivensi (zero downtime)
#
# Uso (no VPS, como root ou via sudo):
#   bash infra/rotate-db-password.sh                  # gera senha nova com openssl
#   NEW_PASS='senha_definida' bash infra/rotate-db-password.sh
#
# Variáveis opcionais:
#   APP_DIR        (default: /var/www/vivensi)
#   PHP_FPM        (default: detectado automaticamente — php8.1-fpm, php8.0-fpm, etc)
#   ASSUME_YES=1   pula a confirmação interativa
#
# Faz: rotaciona a senha do usuário MySQL do .env, atualiza o .env, refaz
# caches, reinicia workers do Supervisor e dá reload no PHP-FPM. Se qualquer
# etapa falhar, restaura .env e volta a senha antiga no MySQL.

set -u

APP_DIR="${APP_DIR:-/var/www/vivensi}"
ENV_FILE="$APP_DIR/.env"
BACKUP_DIR="$APP_DIR/storage/backups"
TS="$(date +%Y%m%d_%H%M%S)"
ENV_BACKUP="$BACKUP_DIR/.env.$TS.bak"

red()    { printf '\033[31m%s\033[0m\n' "$*"; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*"; }
bold()   { printf '\033[1m%s\033[0m\n' "$*"; }

die() { red "❌ $*"; exit 1; }

# ── 0. Pré-requisitos ────────────────────────────────────────────────────────
command -v mysql      >/dev/null || die "mysql client ausente"
command -v php        >/dev/null || die "php ausente"
command -v openssl    >/dev/null || die "openssl ausente"
[ -f "$ENV_FILE" ]               || die "$ENV_FILE não encontrado"
[ "$(id -u)" -eq 0 ] || sudo -n true 2>/dev/null || die "Precisa rodar como root ou com sudo sem senha"

# ── 1. Ler credenciais atuais ────────────────────────────────────────────────
get_env() { grep -E "^${1}=" "$ENV_FILE" | head -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/; s/^'\''(.*)'\''$/\1/'; }

DB_HOST="$(get_env DB_HOST)";     DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(get_env DB_PORT)";     DB_PORT="${DB_PORT:-3306}"
DB_NAME="$(get_env DB_DATABASE)"
DB_USER="$(get_env DB_USERNAME)"
OLD_PASS="$(get_env DB_PASSWORD)"

[ -n "$DB_NAME" ] || die "DB_DATABASE vazio no .env"
[ -n "$DB_USER" ] || die "DB_USERNAME vazio no .env"
[ -n "$OLD_PASS" ] || die "DB_PASSWORD vazio no .env (nada a rotacionar)"

bold "── Rotação de senha MySQL ──"
echo "  App dir: $APP_DIR"
echo "  Usuário: $DB_USER@$DB_HOST:$DB_PORT  /  Banco: $DB_NAME"

# ── 2. Detectar PHP-FPM ──────────────────────────────────────────────────────
PHP_FPM="${PHP_FPM:-}"
if [ -z "$PHP_FPM" ]; then
  PHP_FPM=$(systemctl list-units --type=service --no-legend 2>/dev/null \
            | awk '/^php[0-9.]+-fpm\.service/ {print $1; exit}' \
            | sed 's/\.service$//')
fi
[ -n "$PHP_FPM" ] || yellow "⚠️  PHP-FPM service não detectado — pulo o reload (defina PHP_FPM=... se quiser)"

# ── 3. Testar conexão atual ──────────────────────────────────────────────────
test_mysql() {
  local pass="$1"
  MYSQL_PWD="$pass" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
    -e "SELECT 1" "$DB_NAME" >/dev/null 2>&1
}

test_mysql "$OLD_PASS" || die "Conexão com a senha atual falhou — abortando antes de mexer em nada"
green "✅ Conexão atual OK"

# ── 4. Gerar / aceitar senha nova ────────────────────────────────────────────
NEW_PASS="${NEW_PASS:-$(openssl rand -base64 36 | tr -d '/+=\n' | head -c 32)}"
[ "${#NEW_PASS}" -ge 16 ] || die "NEW_PASS muito curta (mín. 16 chars)"
[ "$NEW_PASS" != "$OLD_PASS" ] || die "NEW_PASS é igual à atual"

bold ""
bold "── Nova senha (ANOTE EM UM COFRE AGORA — não será mostrada de novo) ──"
echo "  $NEW_PASS"
bold ""

if [ "${ASSUME_YES:-0}" != "1" ]; then
  read -r -p "Prosseguir com a rotação? [y/N] " ans
  case "$ans" in
    y|Y|yes|YES) ;;
    *) die "Cancelado pelo usuário" ;;
  esac
fi

# ── 5. Backup do .env ────────────────────────────────────────────────────────
mkdir -p "$BACKUP_DIR"
cp -a "$ENV_FILE" "$ENV_BACKUP"
chmod 600 "$ENV_BACKUP"
green "✅ Backup do .env: $ENV_BACKUP"

# ── 6. Rollback helpers ──────────────────────────────────────────────────────
ROLLBACK_MYSQL=0
ROLLBACK_ENV=0

rollback() {
  red "↩️  Rollback iniciado..."
  if [ "$ROLLBACK_MYSQL" -eq 1 ]; then
    sudo mysql -e "ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$OLD_PASS'; FLUSH PRIVILEGES;" \
      && yellow "   senha MySQL revertida" \
      || red "   ⚠️  FALHA ao reverter senha MySQL — intervenha manualmente"
  fi
  if [ "$ROLLBACK_ENV" -eq 1 ] && [ -f "$ENV_BACKUP" ]; then
    cp -a "$ENV_BACKUP" "$ENV_FILE" && yellow "   .env restaurado do backup"
  fi
  if [ -n "$PHP_FPM" ]; then
    sudo systemctl reload "$PHP_FPM" 2>/dev/null || true
  fi
  red "❌ Rotação abortada — estado anterior restaurado"
  exit 1
}
trap rollback ERR

# ── 7. ALTER USER no MySQL ───────────────────────────────────────────────────
echo ""
echo "→ Alterando senha no MySQL..."
sudo mysql -e "ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$NEW_PASS'; FLUSH PRIVILEGES;"
ROLLBACK_MYSQL=1

# Confirmar com client puro (independe do app)
test_mysql "$NEW_PASS" || { red "Mysql client não autentica com a senha nova"; rollback; }
green "✅ MySQL aceita a senha nova"

# Confirmar que a senha antiga foi de fato invalidada
if test_mysql "$OLD_PASS"; then
  red "⚠️  Senha antiga ainda funciona — possivelmente FLUSH PRIVILEGES não propagou"
  rollback
fi
green "✅ Senha antiga rejeitada"

# ── 8. Atualizar .env (escape de caracteres especiais para sed) ──────────────
echo "→ Atualizando $ENV_FILE..."
ESCAPED_NEW=$(printf '%s\n' "$NEW_PASS" | sed -e 's/[\/&|]/\\&/g')
sudo sed -i.tmp -E "s|^DB_PASSWORD=.*|DB_PASSWORD=${ESCAPED_NEW}|" "$ENV_FILE"
sudo rm -f "${ENV_FILE}.tmp"
ROLLBACK_ENV=1

# Confere que ficou correto
WROTE="$(get_env DB_PASSWORD)"
[ "$WROTE" = "$NEW_PASS" ] || { red ".env não ficou com a nova senha (escrito: '$WROTE')"; rollback; }
green "✅ .env atualizado"

# ── 9. Refazer caches Laravel ────────────────────────────────────────────────
echo "→ Recriando config:cache..."
cd "$APP_DIR"
sudo -u www-data php artisan config:clear >/dev/null
sudo -u www-data php artisan config:cache >/dev/null
green "✅ config:cache recriado"

# ── 10. Reiniciar workers e dar reload no PHP-FPM ────────────────────────────
if command -v supervisorctl >/dev/null; then
  echo "→ Reiniciando workers Supervisor..."
  sudo supervisorctl restart \
    vivensi-worker-default:* \
    vivensi-worker-whatsapp:* \
    vivensi-worker-emails:* 2>&1 | sed 's/^/   /'
  green "✅ Workers reiniciados"
fi

if [ -n "$PHP_FPM" ]; then
  echo "→ Reload do $PHP_FPM..."
  sudo systemctl reload "$PHP_FPM"
  green "✅ $PHP_FPM recarregado"
fi

# ── 11. Smoke test pela aplicação ────────────────────────────────────────────
echo "→ Testando conexão via Laravel..."
OUT=$(sudo -u www-data php artisan tinker --execute="echo DB::connection()->getPdo() ? 'OK' : 'FAIL';" 2>&1 | tail -1)
echo "   $OUT"
case "$OUT" in
  *OK*) green "✅ Laravel conecta com a senha nova" ;;
  *)    red "Laravel não conseguiu conectar"; rollback ;;
esac

# ── 12. Limpeza e sucesso ────────────────────────────────────────────────────
trap - ERR
green ""
green "════════════════════════════════════════════════════════════════════"
green "  ✅ ROTAÇÃO CONCLUÍDA COM SUCESSO"
green "════════════════════════════════════════════════════════════════════"
echo "  Backup do .env anterior: $ENV_BACKUP"
echo "  Apague o backup quando confirmar que está tudo OK:"
echo "     rm $ENV_BACKUP"
echo ""
yellow "  ⚠️  A senha nova só está no .env e no MySQL — guarde no seu cofre."
