# 📊 VPS Vivensi — Análise, Otimizações e Roadmap

**Data:** 2026-07-14
**Autor:** análise técnica com base em observação do server em produção
**Objetivo:** Documento de referência para decisões de infra: quando otimizar, quando migrar, quando não fazer nada.

---

## 🎯 TL;DR (leia isso primeiro)

- **Status:** Server subutilizado (CPU 96% idle, 42% RAM em uso). Comporta 3-5× a carga atual.
- **Ação recomendada agora:** NADA de urgente. Aplicar apenas **Rodada 1** de otimização (Nginx gzip + Laravel caches). Zero risco.
- **Migração de plano:** NÃO ainda. Só migrar quando `avail Mem < 500 MB` OU `load average > 1.5` por dias seguidos.
- **Economia:** ficando no $20 até de fato precisar, economiza ~$240/ano.

---

## 1️⃣ Situação atual (2026-07-14)

### Infraestrutura

| Item | Detalhe |
|---|---|
| **Provider** | AWS Lightsail |
| **Instância** | `ip-172-26-8-204` |
| **Plano** | $20/mês |
| **RAM** | 4 GB |
| **vCPU** | 2 |
| **SSD** | 80 GB |
| **Bandwidth** | 4 TB/mês incluído |
| **Static IP** | Sim, atrelado ao domain `business.vivensi.app.br` |
| **Uptime** | 88 dias corridos (estável) |

### Stack em produção

```
Nginx  → PHP-FPM (8.x) → Laravel (Vivensi)
                       ↓
                     MySQL 8
                     Redis (cache + sessions + queue) ✓
                     Supervisor:
                       - vivensi-scheduler       (schedule:run a cada 60s)
                       - vivensi-worker-default
                       - vivensi-worker-ai
                       - vivensi-worker-emails
                       - vivensi-worker-whatsapp
```

**Redis ativo:** `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` ✓

---

## 2️⃣ Diagnóstico observado

Comando `top -bn1 | head -5` retornou:

```
top - 03:14:27 up 88 days, load average: 0.05, 0.03, 0.00
Tasks: 139 total, 1 running, 138 sleeping
%Cpu(s): 3.2 us, 0.0 sy, 96.8 id, 0.0 wa
MiB Mem: 3836.8 total, 375.3 free, 1636.8 used, 1824.7 buff/cache
MiB Swap: 2048.0 total, 2047.5 free, 0.5 used, 1765.6 avail Mem
```

### Interpretação

| Métrica | Valor | Verdict |
|---|---|---|
| **Load average** | 0.05 / 0.03 / 0.00 | 🟢 CPU praticamente parada |
| **CPU idle** | 96.8% | 🟢 Muito sobrando |
| **RAM total** | 3.8 GB | — |
| **RAM used** | 1.6 GB (42%) | 🟢 Saudável |
| **RAM free** | 375 MB | 🟡 Parece pouco, MAS ver linha abaixo |
| **RAM buff/cache** | 1.8 GB | 🟢 Linux cacheando, libera se precisar |
| **RAM avail (real)** | 1.7 GB | 🟢 O que importa: folga confortável |
| **Swap** | 0.5 MB de 2 GB | 🟢 Nem toca no swap |

### Mito comum: "só 375 MB livre é pouco?"

**NÃO.** No Linux, RAM não usada é RAM desperdiçada. Os 1.8 GB de `buff/cache` são o kernel cacheando arquivos pra ficar mais rápido. Se qualquer processo pedir RAM, o kernel libera essa cache **instantaneamente**.

**Métrica real de folga:** `avail Mem` = 1.7 GB → tem espaço.

---

## 3️⃣ Planos Lightsail — Comparação

| Plano | RAM | vCPU | SSD | Bandwidth | Preço/mês | Bom pra |
|---|---|---|---|---|---|---|
| **$10** | 2 GB | 1 | 60 GB | 3 TB | $10 | Dev/staging |
| **$20** (atual) | 4 GB | 2 | 80 GB | 4 TB | $20 | 5-15 tenants ativos |
| **$40** | 8 GB | 2 | 160 GB | 5 TB | $40 | 15-40 tenants |
| **$80** | 16 GB | 4 | 320 GB | 6 TB | $80 | 40-100 tenants |
| **$160** | 32 GB | 8 | 640 GB | 7 TB | $160 | 100+ tenants |

**Vantagem Lightsail:** bandwidth incluído. Na EC2 você paga cada GB de saída (~$0.09/GB depois de 100 GB gratuitos). Pra SaaS com email/WhatsApp/downloads, isso ajuda.

**Managed Database Lightsail** (quando fizer sentido separar DB):
- **$15/mês:** MySQL 1 GB, 40 GB SSD
- **$30/mês:** MySQL 2 GB, 80 GB SSD
- **$60/mês:** MySQL 4 GB, 160 GB SSD (multi-AZ opcional)

---

## 4️⃣ Quando migrar de plano (thresholds)

Migre para o **$40 (8 GB)** quando **qualquer** métrica ficar TRUE por vários dias:

| Métrica | Threshold | Como checar |
|---|---|---|
| Load average (15 min) | > 1.5 constante | `uptime` |
| RAM disponível | < 500 MB | `free -h` |
| Swap usado | > 100 MB | `free -h` |
| CPU idle | < 50% sustentado | `top` |
| Requests com timeout no Nginx | > 1% | logs |
| MySQL slow queries | > 100/dia | `mysqladmin extended` |

Migre para o **$80 (16 GB / 4 vCPU)** quando:
- $40 já estiver estressado (mesmas métricas acima)
- Ou passar de 40 tenants ativos com uso real

Migre para **arquitetura horizontal** (Load Balancer + réplicas) quando:
- Passar de 100 tenants ativos
- Precisar SLA de 99.9%+
- Um único server virar SPOF crítico

---

## 5️⃣ Otimizações grátis (por rodadas)

### 🥇 Rodada 1 — SEGURA (aplicar quando quiser)

**Ganho estimado:** +15% throughput
**Risco:** mínimo
**Reversível em:** 30s

Otimizações:
- **Nginx gzip** — comprime respostas de texto/JSON antes de enviar (economia de banda + resposta mais rápida)
- **Laravel caches:** `config:cache`, `route:cache`, `view:cache`, `event:cache`

Comandos:

```bash
# Nginx gzip (editar /etc/nginx/nginx.conf)
gzip on;
gzip_comp_level 6;
gzip_types text/plain application/json application/javascript text/css text/xml application/xml;
gzip_min_length 1024;

# Reload
sudo systemctl reload nginx

# Laravel caches (dentro de /var/www/vivensi)
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

**Reversão:** `sudo -u www-data php artisan config:clear` + remover gzip do nginx.

---

### 🥈 Rodada 2 — MÉDIA (aplicar quando ver RAM > 60% constante)

**Ganho estimado:** +10% throughput
**Risco:** baixo
**Reversível em:** editar de volta o config

**PHP-FPM tuning** (`/etc/php/8.x/fpm/pool.d/www.conf`):

```ini
pm = dynamic
pm.max_children = 15         # antes: 5
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500        # recicla worker (evita memory leak)
```

`sudo systemctl restart php8.x-fpm`

**Risco:** cada worker consome ~50 MB, então 15 = 750 MB. No plano $20 (4 GB) é OK, mas se RAM estiver justa, alguns workers não sobem.

---

### 🥉 Rodada 3 — REQUER DISCIPLINA (aplicar antes de escalar cliente)

**Ganho estimado:** +15% throughput em requests PHP
**Risco:** ALTO se deploy não fizer reload php-fpm
**Reversível:** sim

**OPcache** (`/etc/php/8.x/fpm/php.ini`):

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0    # ⚠️ requer reload no deploy
opcache.revalidate_freq=0
```

**⚠️ Cuidado:** `validate_timestamps=0` faz o PHP não recarregar arquivo alterado até restart. Você precisa que TODO deploy futuro rode:

```bash
sudo systemctl reload php8.x-fpm
```

**Se esquecer:** código deployado não aparece até restart. Isso já ficou coberto no seu script de deploy (`optimize:clear` já reload PHP-FPM automaticamente).

---

### 🔧 Rodada 4 — MySQL tuning (aplicar se DB começar a esquentar)

**Ganho estimado:** +20% em queries lentas
**Risco:** MÉDIO (se buffer não couber na RAM, MySQL não sobe)
**Reversível:** sim

**MySQL** (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
innodb_buffer_pool_size = 1500M    # 40% da RAM no plano 4GB
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2 # 3x mais rápido, aceita perder 1s de dados se crash
innodb_flush_method = O_DIRECT
max_connections = 100
thread_cache_size = 16
tmp_table_size = 64M
max_heap_table_size = 64M
```

**Reversão:** editar de volta e `sudo systemctl restart mysql`.

**⚠️ Só aplicar se MySQL estiver local no server.** Se um dia migrar pra Managed Database Lightsail, isso não se aplica.

---

## 6️⃣ Roteiro de migração $20 → $40 (quando precisar)

Tempo estimado: 30 min de janela, ~2 min de downtime real.

### Passo 1: Backup
- Console Lightsail → instância → aba "Snapshots" → **Create snapshot**
- Nome: `pre-migration-YYYY-MM-DD`
- Aguarda "Available" (~5 min)

### Passo 2: Criar nova instância
- Console → Snapshots → seu snapshot → **Create new instance**
- Região/AZ: mesmas
- Plan: **$40 (8 GB / 2 vCPU / 160 GB)**
- Nome: `vivensi-8gb`
- Aguarda "Running" (~3-5 min)

### Passo 3: Verificar tudo funcionando na nova (com IP temporário)
SSH na nova:

```bash
sudo systemctl status nginx        # active
sudo systemctl status php8.x-fpm   # active
sudo systemctl status mysql        # active
sudo systemctl status redis-server # active
sudo supervisorctl status          # 6 processos running
redis-cli ping                     # PONG
free -h                            # ~8 GB total
```

### Passo 4: Swap do Static IP
- Console → Networking → seu Static IP → **Detach** da antiga → **Attach** na nova
- Aguarda 1-2 min

### Passo 5: Confirmar no domínio
- Browser: `https://business.vivensi.app.br`
- Testa login, `/admin`, `/eu/dados`, etc.

### Passo 6: Aproveitar mais RAM (opcional)
Com 8 GB agora, pode subir os limites:

```ini
# MySQL
innodb_buffer_pool_size = 3000M   # 40% de 8 GB

# PHP-FPM
pm.max_children = 30              # antes: 15
```

### Passo 7: Instância antiga
- Console → antiga → **Stop** (deixa parada por 2-3 dias)
- Se nada quebrar, **Delete**

---

## 7️⃣ Bottleneck real do Vivensi (por feature)

Independente de plano, esses são os pontos onde a carga concentra:

| Feature | Gargalo | Mitigação atual | Ação futura |
|---|---|---|---|
| **Broadcast WhatsApp** | 1 worker por 30s+ por msg | `throttle:10,1` | Cap 10k destinatários/campanha |
| **DeepSeek/IA calls** | Latência 5-15s por request | Queue AI dedicada | Cache de prompts frequentes |
| **LGPD exports** | ZIP grande em memória | Job single-shot | Streaming grande volume |
| **Failed jobs** | Acumula sem cleanup | Manual | Cleanup semanal via schedule |
| **Storage upload** | 80 GB SSD pode encher | Compartilhado | Migrar pra S3 com lifecycle |
| **Backups MySQL** | `db:backup` no cron diário | OK | Enviar pra S3 pra retenção |

---

## 8️⃣ Monitoramento simples (grátis)

**Cheatsheet — rode uma vez por semana:**

```bash
uptime                              # load average 15 min
free -h                             # RAM avail
df -h /                             # disco
sudo supervisorctl status           # workers OK?
sudo -u www-data php artisan queue:failed  # jobs falhados?
```

### Alertas por email (setup opcional em 15 min)

Crie `/etc/cron.d/vivensi-monitor`:

```cron
# a cada 5 min: se load > 2.0, avisa
*/5 * * * * root uptime | awk '{print $10}' | tr -d , | awk '$1 > 2.0 {print "Load alta: "$1}' | mail -s "Vivensi alerta" admin@vivensi.app.br

# a cada hora: se RAM avail < 500 MB, avisa
0 * * * * root free -m | awk '/^Mem:/ {if ($7 < 500) print "RAM baixa: "$7" MB"}' | mail -s "Vivensi RAM baixa" admin@vivensi.app.br
```

Ou use **Lightsail Metrics** (console → instância → Metrics) que já tem CPU/RAM/Network gráfico grátis.

---

## 9️⃣ Roadmap futuro (quando escalar)

### Curto prazo (1-3 meses)
- [ ] Aplicar Rodada 1 (Nginx gzip + Laravel caches) — 5 min, seguro
- [ ] Configurar Lightsail Alarms pra CPU > 80% e RAM > 80%
- [ ] Adicionar `db:backup` pra S3 com lifecycle 90 dias

### Médio prazo (3-6 meses, quando ver 15+ tenants ativos)
- [ ] Upgrade pro plano $40 (8 GB)
- [ ] Aplicar Rodada 2 (PHP-FPM tuning) e Rodada 4 (MySQL) já ampliados
- [ ] Migrar uploads (attachments, LGPD exports) pra S3
- [ ] Configurar CloudFront pra assets estáticos

### Longo prazo (6-12 meses, quando ver 40+ tenants)
- [ ] Upgrade pro plano $80 (16 GB / 4 vCPU)
- [ ] Separar MySQL em Managed Database Lightsail
- [ ] Aplicar Rodada 3 (OPcache com validate_timestamps=0) — mais confiança no processo de deploy
- [ ] Considerar Read Replica MySQL pra reports pesados

### Enterprise (12+ meses, quando ver 100+ tenants ou SLA crítico)
- [ ] Load Balancer Lightsail ($18/mês)
- [ ] 2+ instâncias app server atrás do LB
- [ ] Managed DB multi-AZ (~$60/mês)
- [ ] CloudFront + WAF
- [ ] Backup cross-region

---

## 🔟 Comandos úteis (cheatsheet)

### Diagnóstico rápido

```bash
# CPU + RAM
top -bn1 | head -20

# Só RAM
free -h

# Disco
df -h /

# Processos consumindo memória
ps aux --sort=-%mem | head

# Processos consumindo CPU
ps aux --sort=-%cpu | head

# Uptime + load
uptime

# MySQL status
sudo mysqladmin extended-status | grep -E "Threads|Connections|Queries"

# Redis status
redis-cli info stats | grep -E "instantaneous|total"

# Nginx active connections
curl -s http://localhost/nginx_status  # requer config
```

### Manutenção

```bash
# Limpar cache Laravel
sudo -u www-data php artisan optimize:clear

# Regerar caches
sudo -u www-data php artisan optimize

# Ver failed jobs
sudo -u www-data php artisan queue:failed

# Retry todos jobs falhados
sudo -u www-data php artisan queue:retry all

# Rotacionar log Laravel (se estiver enorme)
sudo truncate -s 0 /var/www/vivensi/storage/logs/laravel.log

# Ver tamanho de tabelas MySQL (identificar quais crescem mais)
mysql -e "SELECT table_schema AS db, table_name AS tbl, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb FROM information_schema.tables WHERE table_schema='vivensi' ORDER BY size_mb DESC LIMIT 20;"
```

### Deploy padrão (com todas caches)

```bash
cd /var/www/vivensi
sudo git pull origin main
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo supervisorctl restart all
```

---

## 📚 Referências

- [Lightsail Pricing](https://aws.amazon.com/lightsail/pricing/)
- [Lightsail Snapshots](https://docs.aws.amazon.com/lightsail/latest/userguide/lightsail-taking-instance-snapshot.html)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [MySQL Buffer Pool Sizing](https://dev.mysql.com/doc/refman/8.0/en/innodb-buffer-pool-resize.html)

---

## 📝 Histórico de decisões

| Data | Decisão | Motivo |
|---|---|---|
| 2026-07-14 | Manter plano $20 | Server subutilizado (CPU 96% idle, RAM 42% used). Migrar seria desperdício. |
| 2026-07-13 | Redis já ativo | CACHE/SESSION/QUEUE todos em redis. Grande ganho de performance. |
| Anterior | 2 GB Swap | Configurado como safety net, praticamente não usado (0.5 MB). |

---

**Fim do documento.** Revisar em: `2026-10-14` (3 meses) OU quando notar mudança nas métricas de monitoramento.
