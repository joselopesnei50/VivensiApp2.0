#!/bin/bash
# ============================================================
# setup_evolution_lightsail.sh
# Script de instalação da Evolution API (Vivensi Infra)
# Servidor: AWS Lightsail Ubuntu 22.04
# Uso: bash setup_evolution_lightsail.sh
# ============================================================
set -e

DOMAIN="evo.vivensi.app.br"      # Subdomínio para a Evolution API
EMAIL="admin@vivensi.app.br"     # Email para certificado SSL (Let's Encrypt)
DB_PASSWORD=$(openssl rand -hex 24)
EVOLUTION_GLOBAL_KEY=$(openssl rand -hex 32)

echo "=================================================="
echo "🚀 Vivensi — Setup Evolution API no Lightsail"
echo "=================================================="
echo ""
echo "📋 ANOTE ESTAS CREDENCIAIS AGORA:"
echo "   DB_PASSWORD:          $DB_PASSWORD"
echo "   EVOLUTION_GLOBAL_KEY: $EVOLUTION_GLOBAL_KEY"
echo ""
# (Prompts iterativos removidos para automação)

# ── 1. Atualizar sistema ──────────────────────────────────────────────────
echo ""
echo "📦 Atualizando sistema..."
sudo apt-get update -qq
sudo apt-get upgrade -y -qq

# ── 2. Instalar Docker ────────────────────────────────────────────────────
echo "🐳 Instalando Docker..."
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
sudo systemctl enable docker --now

# Instalar Docker Compose v2
sudo apt-get install -y docker-compose-plugin

# ── 3. Instalar Nginx + Certbot ───────────────────────────────────────────
echo "🌐 Instalando Nginx + Certbot..."
sudo apt-get install -y nginx certbot python3-certbot-nginx

# ── 4. Criar diretório e arquivos de configuração ─────────────────────────
APP_DIR="/opt/vivensi-evolution"
sudo mkdir -p $APP_DIR
cd $APP_DIR

# Criar .env para o docker-compose
cat > .env << EOF
EVOLUTION_API_URL=https://${DOMAIN}
EVOLUTION_GLOBAL_KEY=${EVOLUTION_GLOBAL_KEY}
DB_PASSWORD=${DB_PASSWORD}
EOF

sudo chmod 600 .env

# ── 5. Configuração do Nginx (antes do SSL) ───────────────────────────────
cat > /tmp/nginx_evolution.conf << EOF
server {
    listen 80;
    server_name ${DOMAIN};

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        client_max_body_size 50M;
    }
}
EOF

sudo cp /tmp/nginx_evolution.conf /etc/nginx/sites-available/evolution
sudo ln -sf /etc/nginx/sites-available/evolution /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# ── 6. Emitir certificado SSL ─────────────────────────────────────────────
echo "🔐 Emitindo certificado SSL para ${DOMAIN}..."
echo "⚠️  CERTIFIQUE-SE QUE o DNS de ${DOMAIN} já aponta para este servidor!"
# (Ignorando prompt DNS pois já orientamos o usuário)
    sudo certbot --nginx -d $DOMAIN --email $EMAIL --agree-tos --non-interactive
    echo "✅ SSL emitido com sucesso (se DNS já propagado)!"

# ── 7. Criar nginx.conf para o docker-compose ─────────────────────────────
cat > $APP_DIR/nginx.conf << EOF
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name ${DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    location / {
        proxy_pass http://evolution_api:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_read_timeout 300s;
        client_max_body_size 50M;
    }
}
EOF

# ── 8. Copiar docker-compose.yml e subir containers ───────────────────────
echo "🐳 Subindo containers da Evolution API..."
# O docker-compose.yml deve estar neste diretório
# (faça upload do arquivo infrastructure/evolution/docker-compose.yml para $APP_DIR)
if [ -f "$APP_DIR/docker-compose.yml" ]; then
    cd $APP_DIR
    docker compose up -d
    echo "✅ Containers subindo! Aguarde ~30s e acesse: https://${DOMAIN}"
else
    echo "⚠️  docker-compose.yml não encontrado em $APP_DIR"
    echo "   Faça upload do arquivo e rode: cd $APP_DIR && docker compose up -d"
fi

# ── 9. Configurar renovação automática do SSL ─────────────────────────────
echo "🔄 Configurando renovação automática do SSL..."
(crontab -l 2>/dev/null; echo "0 0 1 * * certbot renew --quiet && sudo systemctl reload nginx") | crontab -

# ── 10. Resumo final ──────────────────────────────────────────────────────
echo ""
echo "=================================================="
echo "✅ Setup concluído! Adicione ao .env do Vivensi:"
echo "=================================================="
echo "EVOLUTION_API_URL=https://${DOMAIN}"
echo "EVOLUTION_GLOBAL_KEY=${EVOLUTION_GLOBAL_KEY}"
echo ""
echo "E no AWS Lightsail, abra as portas: 80 (HTTP), 443 (HTTPS)"
echo "=================================================="
