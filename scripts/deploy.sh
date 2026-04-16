#!/usr/bin/env bash
# =============================================================================
# Vivensi — Deploy Script
# Executado pelo webhook do GitHub após git push no branch main
# Uso: bash /var/www/vivensi/scripts/deploy.sh
# =============================================================================

set -euo pipefail

APP_DIR="/var/www/vivensi"
LOG_FILE="${APP_DIR}/storage/logs/deploy.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
PHP_BIN="/usr/bin/php8.1"
ARTISAN="${APP_DIR}/artisan"
WEB_USER="www-data"

log() {
    echo "[${TIMESTAMP}] $1" | tee -a "$LOG_FILE"
}

log "========================================"
log "🚀 Deploy iniciado"

# 1. Puxar código mais recente
log "📥 Atualizando código..."
cd "$APP_DIR"
sudo -u "$WEB_USER" git pull origin main

# 2. Instalar/atualizar dependências (sem dev, otimizado)
log "📦 Atualizando dependências Composer..."
sudo -u "$WEB_USER" composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --quiet

# 3. Executar migrações pendentes
log "🗃️  Rodando migrações..."
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" migrate --force

# 4. Recriar caches
log "⚡ Recriando caches..."
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" config:clear
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" config:cache
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" route:clear
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" route:cache
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" view:clear
sudo -u "$WEB_USER" "$PHP_BIN" "$ARTISAN" view:cache

# 5. Reiniciar workers de fila
log "🔄 Reiniciando workers..."
sudo supervisorctl restart vivensi-worker:* 2>/dev/null || true

log "✅ Deploy concluído com sucesso!"
log "========================================"
