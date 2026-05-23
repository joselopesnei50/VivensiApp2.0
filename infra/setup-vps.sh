#!/bin/bash
# Setup inicial do VPS — Vivensi
# Rodar UMA VEZ como root após o servidor estar provisionado.
# Pressupõe Ubuntu 22.04 LTS, PHP 8.1+, MySQL 8, Redis instalados.

set -euo pipefail

APP_DIR="/var/www/vivensi"
SUPERVISOR_CONF="/etc/supervisor/conf.d/vivensi.conf"
CRON_USER="www-data"

# ── 1. Supervisor ──────────────────────────────────────────────────────────────
echo "==> Instalando Supervisor"
apt-get install -y supervisor

echo "==> Copiando config do Supervisor"
cp "$APP_DIR/infra/supervisor.conf" "$SUPERVISOR_CONF"
supervisorctl reread
supervisorctl update
supervisorctl start vivensi-horizon
echo "    Supervisor OK — vivensi-horizon iniciado"

# ── 2. Cron para o scheduler ──────────────────────────────────────────────────
CRON_LINE="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo "==> Configurando cron para $CRON_USER"
(crontab -u "$CRON_USER" -l 2>/dev/null | grep -v "schedule:run"; echo "$CRON_LINE") \
    | crontab -u "$CRON_USER" -
echo "    Cron OK — schedule:run a cada minuto"

# ── 3. Permissões de storage ──────────────────────────────────────────────────
echo "==> Ajustando permissões"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ── 4. Verificação final ──────────────────────────────────────────────────────
echo ""
echo "==> Status do Supervisor:"
supervisorctl status

echo ""
echo "==> Crontab de $CRON_USER:"
crontab -u "$CRON_USER" -l

echo ""
echo "Setup concluído. Próximos passos:"
echo "  1. Copiar infra/env.production.example para .env e preencher os valores reais"
echo "  2. Rodar: php artisan key:generate (se APP_KEY ainda não estiver no .env)"
echo "  3. Rodar: bash infra/deploy.sh"
echo "  4. Acessar /horizon para confirmar workers ativos"
