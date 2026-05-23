#!/bin/bash
# Deploy script — Vivensi VPS
# Uso: bash infra/deploy.sh
# Requisito: rodar a partir do servidor como root ou www-data com sudo

set -euo pipefail

APP_DIR="/var/www/vivensi"
PHP="php"
ARTISAN="$PHP $APP_DIR/artisan"
COMPOSER="composer"

echo "==> [1/8] Pull do código"
cd "$APP_DIR"
git pull origin main

echo "==> [2/8] Dependências PHP (sem dev)"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "==> [3/8] Migrations"
$ARTISAN migrate --force

echo "==> [4/8] Limpeza de cache"
$ARTISAN config:clear
$ARTISAN cache:clear
$ARTISAN view:clear
$ARTISAN route:clear
$ARTISAN event:clear

echo "==> [5/8] Rebuild de cache (produção)"
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache

echo "==> [6/8] Reiniciando queue workers"
# Horizon (se ativo): php artisan horizon:terminate && supervisorctl restart vivensi-horizon
# Sem Horizon — reinicia workers via queue:restart signal
$ARTISAN queue:restart

echo "==> [7/8] Verificação de saúde"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ping)
if [ "$STATUS" != "200" ]; then
    echo "ALERTA: /ping retornou HTTP $STATUS"
    exit 1
fi
echo "    /ping → HTTP 200 OK"

echo "==> [8/8] Deploy concluído"
echo ""
echo "Checklist pós-deploy:"
echo "  [ ] Verificar Horizon em /horizon (workers ativos)"
echo "  [ ] Verificar Sentry recebendo eventos"
echo "  [ ] Testar login, pagamento e WhatsApp"
