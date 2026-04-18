# 🚀 Relatório de Resgate: Infraestrutura Vivensi v2.0

Este documento registra as ações tomadas em 17/04/2026 para restaurar a plataforma Vivensi após colapso crítico do servidor original por esvaziamento de créditos e estouro de disco.

---

## 🏗️ 1. Nova Infraestrutura
*   **Provedor:** AWS Lightsail
*   **Instância:** Ubuntu 22.04 LTS (4GB RAM / 2 vCPUs)
*   **IP Estático:** `34.193.132.112`
*   **Stack:** LEMP (Nginx, MySQL 8.0, PHP 8.1-FPM)

## 🔧 2. Otimizações Críticas Realizadas
Para evitar que o servidor trave novamente como ocorreu no anterior, aplicamos:
- **`SESSION_DRIVER=database`**: As sessões agora são salvas no banco de dados. Isso impede a criação de milhares de micro-arquivos que lotam os inodes do disco rígido.
- **Remoção de Dependências Cloudflare**: Os middlewares de headers de segurança e proxies confiáveis foram simplificados para permitir conexão direta via IP/Domínio sem loops de redirecionamento.
- **Scheduler Otimizado**: O comando `posts:publish` foi ajustado para rodar a cada 5 minutos em vez de cada minuto, reduzindo carga de CPU.

## 🔐 3. Segurança e Acesso
- **Domínio:** `vivensi.app.br` e `www` apontados diretamente para o IP.
- **SSL:** Certificado Let's Encrypt instalado e configurado para renovação automática.
- **Super Admin:** Usuário restaurado com permissão total (`super_admin`).

## 🛠️ 4. Comandos de Manutenção Essenciais
Sempre que precisar atualizar o servidor com código novo:

```bash
# Entrar na pasta e puxar o código
cd /var/www/vivensi && sudo git pull origin main

# Limpar caches para aplicar mudanças
sudo -u www-data php artisan optimize:clear
```

---
*Gerado automaticamente pelo Agente de Codificação em 17/04/2026.*
