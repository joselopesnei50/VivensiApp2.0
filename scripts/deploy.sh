#!/usr/bin/env bash
# deploy.sh — Script de deploy seguro do Vivensi
# Executar na raiz do projeto: bash scripts/deploy.sh
set -euo pipefail

echo "🚀 Vivensi Deploy — $(date '+%Y-%m-%d %H:%M:%S')"

# 1. Código
git pull origin main

# 2. Dependências (sem pacotes de dev)
composer install --no-dev --optimize-autoloader

# 3. Cache Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Migrações
php artisan migrate --force

# 5. Storage
php artisan storage:link 2>/dev/null || true

# 6. Reiniciar workers
if command -v supervisorctl &>/dev/null; then
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl restart vivensi-worker-default:* 2>/dev/null || sudo supervisorctl start vivensi-worker-default:* || true
    sudo supervisorctl restart vivensi-worker-whatsapp:* 2>/dev/null || sudo supervisorctl start vivensi-worker-whatsapp:* || true
    echo "✅ Workers reiniciados"
fi

# 7. Permissões de storage
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "✅ Deploy concluído"
