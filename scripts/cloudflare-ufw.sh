#!/bin/bash
echo "Configuring UFW firewall to only allow Cloudflare IPs..."

# Verifica se é root
if [ "$EUID" -ne 0 ]; then
  echo "Por favor, execute como root (sudo bash cloudflare-ufw.sh)"
  exit
fi

# Reseta as regras
ufw --force reset

# Políticas padrão: bloqueia entradas, permite saídas
ufw default deny incoming
ufw default allow outgoing

# CRÍTICO: Permite SSH para você não perder acesso!
ufw allow 22/tcp
echo "Porta 22 (SSH) aberta."

# Pega os IPs atuais da Cloudflare e cria a regra tcp nas portas 80/443
echo "Recuperando IPs da Cloudflare..."

for i in `curl -s https://www.cloudflare.com/ips-v4`; do
    ufw allow proto tcp from $i to any port 80
    ufw allow proto tcp from $i to any port 443
done

for i in `curl -s https://www.cloudflare.com/ips-v6`; do
    ufw allow proto tcp from $i to any port 80
    ufw allow proto tcp from $i to any port 443
done

echo "Ativando firewall..."
ufw --force enable

echo "Concluído! Servidor protegido. Acesso HTTP/HTTPS bloqueado contra navegação direta e liberado APENAS via Cloudflare."
