#!/bin/bash
set -e

echo "=== 1. Atualizando sistema ==="
sudo apt-get update -y && sudo apt-get upgrade -y

echo "=== 2. Instalando dependências ==="
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y nginx mysql-server git unzip curl zip \
  php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml \
  php8.1-curl php8.1-zip php8.1-bcmath php8.1-intl php8.1-gd \
  php8.1-tokenizer php8.1-ctype php8.1-fileinfo

echo "=== 3. Instalando Composer ==="
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

echo "=== 4. Configurando MySQL ==="
sudo mysql -e "CREATE DATABASE IF NOT EXISTS vivensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'vivensi_user'@'localhost' IDENTIFIED BY 'Viv3nsi@2026';"
sudo mysql -e "GRANT ALL PRIVILEGES ON vivensi.* TO 'vivensi_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "=== 5. Clonando repositório ==="
sudo rm -rf /var/www/vivensi
sudo git clone https://github.com/joselopesnei50/VivensiApp2.0.git /var/www/vivensi
sudo chown -R ubuntu:ubuntu /var/www/vivensi

echo "=== 6. Configurando .env ==="
sudo cat > /var/www/vivensi/.env << 'ENVEOF'
APP_NAME=Vivensi
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://34.193.132.112

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vivensi
DB_USERNAME=vivensi_user
DB_PASSWORD=Viv3nsi@2026

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

EVOLUTION_API_URL=https://evo.vivensi.app.br
EVOLUTION_GLOBAL_KEY=e838f5d5b86ea0fe27492c283c27498ffe0b250085896f1eaa8093baf0a3309e
SESSION_SECURE_COOKIE=false
ENVEOF

echo "=== 7. Instalando dependências PHP ==="
cd /var/www/vivensi
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== 8. Gerando APP_KEY e migrando banco ==="
php artisan key:generate --force
php artisan migrate --force
php artisan optimize:clear

echo "=== 9. Permissões ==="
sudo chown -R www-data:www-data /var/www/vivensi/storage /var/www/vivensi/bootstrap/cache
sudo chmod -R 775 /var/www/vivensi/storage /var/www/vivensi/bootstrap/cache

echo "=== 10. Configurando Nginx ==="
sudo tee /etc/nginx/sites-available/vivensi << 'NGINXEOF'
server {
    listen 80;
    server_name vivensi.app.br www.vivensi.app.br 34.193.132.112;
    root /var/www/vivensi/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.ht { deny all; }
}
NGINXEOF

sudo ln -sf /etc/nginx/sites-available/vivensi /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl restart nginx php8.1-fpm

echo "=== 11. Cron do Laravel ==="
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/vivensi && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo ""
echo "✅ INSTALAÇÃO CONCLUÍDA! Acesse o site no navegador."
