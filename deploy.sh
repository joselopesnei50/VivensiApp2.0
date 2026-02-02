#!/bin/bash
set -e

echo "🚀 Iniciando Deployment (Vivensi 2.0)..."

# 1. Entrar no modo de manutenção
php artisan down || true

# 2. Atualizar código do GitHub
echo "📥 Puxando as atualizações mais recentes do GitHub..."
git pull origin main

# 3. Instalar dependências do Composer
echo "📦 Instalando dependências do Composer..."
composer install --no-dev --optimize-autoloader

# 4. Rodar Migrações
echo "🗄️ Executando migrações de banco de dados..."
php artisan migrate --force

# 5. Otimizar Laravel (Cache de Configs e Rotas)
echo "⚡ Otimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissões de Pasta
echo "🔐 Ajustando permissões..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Sair do modo de manutenção
php artisan up

echo "✅ Deployment Finalizado com Sucesso!"
