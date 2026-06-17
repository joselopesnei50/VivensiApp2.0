# Deploy systemd — Horizon

Unit file para rodar o **Laravel Horizon** como serviço persistente no VPS
Ubuntu, garantindo que a fila WhatsApp seja processada 24/7 mesmo sem o painel
aberto.

Path de instalação no VPS: `/var/www/vivensi`. PHP esperado: `/usr/bin/php8.1`.

## Instalação

```bash
# 1) Copia o unit pra pasta do systemd
sudo cp /var/www/vivensi/deploy/systemd/horizon.service /etc/systemd/system/horizon.service

# 2) Recarrega o systemd
sudo systemctl daemon-reload

# 3) Habilita pra subir no boot e inicia agora
sudo systemctl enable --now horizon.service

# 4) Confirma
sudo systemctl status horizon.service
php artisan horizon:status        # deve dizer "Horizon is running."
```

## Diagnóstico

```bash
# Status do serviço
sudo systemctl status horizon

# Logs (últimas 100 linhas, segue em tempo real)
sudo journalctl -u horizon -n 100 -f

# Filas pendentes
redis-cli LLEN queues:whatsapp
redis-cli LLEN queues:default
```

## Operação no dia a dia

```bash
# Após git pull — reinicia workers respeitando jobs em execução
sudo systemctl restart horizon

# Pause (jobs continuam empilhando)
sudo systemctl stop horizon

# Desabilita do boot
sudo systemctl disable horizon
```

## Por que rodar como www-data

O FPM/Nginx já roda como `www-data`. Rodando o Horizon com o mesmo
user/group, todos os arquivos em `storage/logs/laravel-*.log` ficam com dono
consistente — elimina o "permission denied" recorrente que aparecia quando o
worker era iniciado manualmente como `ubuntu`.

`storage/` está em `ubuntu:www-data 775` e `storage/logs/` em
`www-data:www-data 2775` (setgid) — o `www-data` tem permissão de escrita em
ambos.
