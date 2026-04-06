#!/bin/bash
set -e

# Configurações
APP_DIR="/var/www/vivensi"

echo "🚀 Iniciando Deployment em $APP_DIR (Vivensi 2.0)..."

# Validar se o diretório existe
if [ ! -d "$APP_DIR" ]; then
    echo "❌ Erro: Diretório $APP_DIR não encontrado!"
    exit 1
fi

cd $APP_DIR

# 1. Permissões Iniciais
echo "🔐 Ajustando permissões iniciais..."
sudo chown -R ubuntu:www-data .
sudo chmod -R 775 storage bootstrap/cache

# 2. Entrar no modo de manutenção
sudo php artisan down || true

# 3. Atualizar código do GitHub
echo "📥 Puxando as atualizações mais recentes do GitHub..."
git pull origin main

# 4. Instalar dependências do Composer
echo "📦 Instalando dependências do Composer..."
composer install --no-dev --optimize-autoloader

# 5. Rodar Migrações
echo "🗄️ Executando migrações de banco de dados..."
php artisan migrate --force

# 6. Otimizar Laravel (Cache de Configs e Rotas)
echo "⚡ Otimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Permissões de Pasta Final (Garantir que o Webserver consiga ler)
echo "🔐 Ajustando permissões finais..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 7. Sair do modo de manutenção
sudo php artisan up

echo "✅ Deployment Finalizado com Sucesso!"
