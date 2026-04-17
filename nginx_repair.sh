#!/bin/bash
set -e

echo "=== 1. Reconfigurando Nginx ==="
sudo cat > /etc/nginx/sites-available/vivensi << 'NGINXEOF'
server {
    listen 80;
    server_name vivensi.app.br www.vivensi.app.br 34.193.132.112;
    location / {
        return 301 https://vivensi.app.br$request_uri;
    }
}

server {
    listen 443 ssl;
    server_name vivensi.app.br www.vivensi.app.br;

    root /var/www/vivensi/public;
    index index.php index.html;

    # Se os certificados existirem, usa SSL. 
    # Se nao existirem, o nginx -t vai avisar.
    ssl_certificate /etc/letsencrypt/live/vivensi.app.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vivensi.app.br/privkey.pem;
    
    # Configuracoes padrao do certbot
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

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

echo "=== 2. Criando links e limpando padrao ==="
sudo ln -sf /etc/nginx/sites-available/vivensi /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

echo "=== 3. Atualizando .env para HTTPS ==="
sudo sed -i 's|APP_URL=.*|APP_URL=https://vivensi.app.br|g' /var/www/vivensi/.env

echo "=== 4. Testando e Reiniciando Nginx ==="
sudo nginx -t && sudo systemctl restart nginx

echo "=== 5. Limpando cache do Laravel ==="
cd /var/www/vivensi && sudo php artisan optimize:clear

echo ""
echo "✅ REPARO CONCLUÍDO! Tente acessar https://vivensi.app.br"
