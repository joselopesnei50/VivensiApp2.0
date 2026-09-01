# Custom Domain Add-on — Setup no VPS Vivensi (one-time)

Fase 2 do custom domain de landing pages exige alguns preparativos manuais
no VPS Vivensi (`34.193.132.112`, `/var/www/vivensi`). Roda uma vez só;
depois disso, cada cliente e provisionado via `landing:provision-domain`.

## 1. Instalar dependências

```
sudo apt update && sudo apt install -y certbot python3-certbot-nginx dnsutils
```

## 2. Criar diretório onde o PHP vai escrever os configs nginx

```
sudo mkdir -p /etc/nginx/sites-available/vivensi-landings
sudo chown www-data:www-data /etc/nginx/sites-available/vivensi-landings
sudo chmod 755 /etc/nginx/sites-available/vivensi-landings
```

## 3. Incluir esse diretório no nginx.conf

Edita `/etc/nginx/nginx.conf` (`sudo nano /etc/nginx/nginx.conf`) e, dentro
do bloco `http { ... }`, adiciona:

```
include /etc/nginx/sites-available/vivensi-landings/*.conf;
```

Recomendo colocar perto do `include /etc/nginx/sites-enabled/*;` existente.
Salva (Ctrl+O, Enter, Ctrl+X).

Valida e recarrega:

```
sudo nginx -t
sudo systemctl reload nginx
```

## 4. Instalar sudoers whitelist

Baixa a config do repo (3 linhas curtas pra evitar quebra de paste em URL
longa):

```
U=https://raw.githubusercontent.com/joselopesnei50
U=$U/VivensiApp2.0/main/infra/sudoers/vivensi-landing
sudo wget -q -O /etc/sudoers.d/vivensi-landing $U
```

Valida (checa syntax de TODOS os sudoers):

```
sudo visudo -c
```

Deve mostrar `parsed OK` em cada arquivo. Ajusta permissao:

```
sudo chmod 0440 /etc/sudoers.d/vivensi-landing
```

## 5. Deploy do código Laravel (Fases 1 + 2)

```
cd /var/www/vivensi
sudo -u www-data git pull origin main
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:clear
```

## 6. Teste com um domínio real (SMOKE TEST antes de vender pra cliente)

Cria uma LP no painel, marca via tinker `custom_domain = 'teste.seudominio.com.br'`.
Aponta DNS de `teste.seudominio.com.br` pra `34.193.132.112` (registro A).
Espera propagação (5-30min).

Testa DNS:

```
dig +short teste.seudominio.com.br @8.8.8.8
```

Deve retornar `34.193.132.112`. Se sim, dry-run:

```
sudo -u www-data php artisan landing:provision-domain 1 --dry-run
```

Deve dizer `DNS ok`. Enfim, provisiona de verdade:

```
sudo -u www-data php artisan landing:provision-domain 1
```

Deve terminar com `SUCESSO: LP #1 servindo em https://teste.seudominio.com.br`.

Abre no browser — deve carregar a landing page com cadeado verde.

## Comandos de operação (depois do setup)

- Provisionar: `sudo -u www-data php artisan landing:provision-domain {id}`
- Re-provisionar (troca de cert): idem com `--force`
- Cliente cancelou: `sudo -u www-data php artisan landing:unprovision-domain {id}`
- Renovar certs manualmente: `sudo -u www-data php artisan landing:renew-ssl-certs`
- Cron ja agendado no `app/Console/Kernel.php` pra rodar renewal diario 3am

## Rollback / troubleshooting

- Config nginx quebrada: `sudo rm /etc/nginx/sites-available/vivensi-landings/lp-{id}.conf && sudo systemctl reload nginx`
- Cert Let's Encrypt não emitido: `sudo tail -50 /var/log/letsencrypt/letsencrypt.log`
- Nginx não reload: `sudo systemctl status nginx` mostra erro específico
- Sudoers quebrado (perigoso — sudo trava): boot em modo recovery e edita
  `/etc/sudoers.d/vivensi-landing` como root direto
