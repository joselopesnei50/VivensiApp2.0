#!/usr/bin/env bash
# deploy.sh — Deploy seguro do Vivensi em produção.
# Uso:  bash scripts/deploy.sh
# Rode SEMPRE da raiz do projeto (/var/www/vivensi).
set -euo pipefail

# Sanidade: precisa estar na raiz (artisan presente)
if [ ! -f artisan ]; then
    echo "❌ Rode este script da raiz do projeto (onde está o arquivo 'artisan')."
    exit 1
fi

echo "🚀 Vivensi Deploy — $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# 1. Codigo
echo "→ [1/6] git pull"
git pull origin main
echo ""

# 2. Dependencias (sem pacotes de dev)
echo "→ [2/6] composer install"
composer install --no-dev --optimize-autoloader --no-interaction
echo ""

# 3. Cache Laravel (config + view + event; route:cache pulado — quebra se
#    tiver closures em routes/*.php, comum no Vivensi)
echo "→ [3/6] limpar + regenerar caches"
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan view:cache
php artisan event:cache
echo ""

# 4. Migracoes
echo "→ [4/6] migrations"
php artisan migrate --force
echo ""

# 5. Storage
php artisan storage:link 2>/dev/null || true

# 6. Reiniciar TODOS os workers (mais seguro que listar um a um)
#    'restart all' inclui: scheduler, worker-ai, worker-default, worker-emails,
#    worker-whatsapp. Zera OPcache/estado em memoria — necessario apos codigo novo.
echo "→ [5/6] reiniciar workers"
if command -v supervisorctl &>/dev/null; then
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl restart all
    echo "✅ Workers reiniciados"
else
    echo "⚠️  supervisorctl nao encontrado — pule este passo se voce nao usa supervisor"
fi
echo ""

# 7. Permissoes de storage
echo "→ [6/6] ajustar permissoes"
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
echo ""

echo "✅ Deploy concluido"
echo ""
echo "Status dos workers:"
sudo supervisorctl status 2>/dev/null || true
