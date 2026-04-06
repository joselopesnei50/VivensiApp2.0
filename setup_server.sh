#!/bin/bash

# Abortar se ocorrer algum erro
set -e

echo "🚀 Iniciando Configuração do Servidor Vivensi 2.0 (Ubuntu/AWS)..."
echo "----------------------------------------------------------------"

# 1. Atualizar Sistema
echo "📦 Atualizando repositórios..."
sudo apt-get update
sudo apt-get upgrade -y

# 2. Instalar Ondrej PHP PPA (para ter acesso ao PHP 8.1+)
echo "🐘 Configurando repositório PHP..."
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

# 3. Instalar PHP 8.1 e Extensões Necessárias
echo "🐘 Instalando PHP 8.1 e extensões..."
sudo apt-get install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
    php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath \
    unzip git curl nginx mysql-server

# 4. Instalar Composer
echo "🎼 Instalando Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# 5. Configurar Diretório da Aplicação
APP_DIR="/var/www/vivensi"

if [ ! -d "$APP_DIR" ]; then
    echo "📂 Clonando repositório..."
    # Substitua URL_DO_REPO pela URL do seu repositório ou configure as chaves SSH antes
    # git clone https://github.com/SEU_USUARIO/SEU_REPO.git $APP_DIR
    mkdir -p $APP_DIR
    echo "⚠️  ATENÇÃO: Diretório criado em $APP_DIR. Você precisa clonar o código aqui ou fazer upload."
else
    echo "📂 Diretório $APP_DIR já existe."
fi

# 6. Configurar Nginx
echo "🌐 Configurando Nginx..."
NGINX_CONF="/etc/nginx/sites-available/vivensi"

sudo tee $NGINX_CONF > /dev/null <<EOF
server {
    listen 80;
    server_name _; 

    root $APP_DIR/public;
    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Ativar site e remover default
sudo ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Testar e reiniciar Nginx
sudo nginx -t
sudo systemctl reload nginx

# 7. Configurar Banco de Dados (Interativo)
echo "----------------------------------------------------------------"
echo "🛢️  O MySQL foi instalado. Para configurar a senha do root e criar o banco:"
echo "    Execute: sudo mysql_secure_installation"
echo "    Depois entre no mysql: sudo mysql"
echo "    E crie o banco: CREATE DATABASE vivensi_prod;"
echo "----------------------------------------------------------------"

# 8. Permissões Finais
if [ -d "$APP_DIR" ]; then
    echo "🔐 Ajustando Permissões Iniciais..."
    sudo chown -R www-data:www-data $APP_DIR
    sudo chmod -R 775 $APP_DIR/storage
    sudo chmod -R 775 $APP_DIR/bootstrap/cache
fi

echo "✅ Configuração Base Concluída!"
echo "👉 Próximos passos:"
echo "   1. Clone seu código em $APP_DIR (se ainda não estiver lá)"
echo "   2. Crie o arquivo .env (cp .env.example .env) e configure o Banco de Dados"
echo "   3. Rode 'composer install --no-dev'"
echo "   4. Rode 'php artisan migrate --force'"
