# PRE_PRODUCTION_CHECKLIST — Vivensi SaaS

> Itens ordenados por prioridade. Marque cada item antes de abrir para usuários pagantes.

---

## 🔴 BLOQUEANTE — Não ir a produção sem isso

- [ ] **[A01]** Confirmar que `php artisan schedule:run` está configurado no cron da AWS (a cada minuto). Sem isso, nenhum agendamento roda — inclusive limpeza de rifas e backup.
  ```
  * * * * * cd /var/www/vivensi && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] **[A01]** Verificar que o scheduler está executando `raffles:cleanup-reservations` (já adicionado ao Kernel). Testar manualmente: `php artisan raffles:cleanup-reservations`.
- [ ] **[A02]** Verificar que `SecurityHeaders` middleware está ativo (correção já aplicada). Testar com `curl -I https://seudominio.com.br` e confirmar presença dos headers.
- [ ] **[A03]** Confirmar que `throttle:20,1` na rota de reserva não bloqueia usuários legítimos em pico de vendas. Ajustar o limite se necessário.
- [ ] **[A04]** Atualizar o template da view de upload de comprovante (`public/raffles/checkout.blade.php`) para incluir o campo `buyer_email` oculto no formulário de upload, pois a correção do IDOR exige esse campo.
- [ ] **[A05]** Confirmar `SESSION_SECURE_COOKIE=true` no `.env` de produção (o config já tem `true` como padrão, mas `.env` pode sobrescrever).
- [ ] **[A07]** Implementar envio do backup para S3 no comando `BackupDatabase.php`. O backup local em `storage/backups/` não é suficiente para DR.

---

## 🟠 IMPORTANTE — Resolver antes de crescer a base de usuários

- [ ] **[A08]** Rodar `php artisan migrate` em produção para criar o índice composto `(raffle_id, status)` em `raffle_tickets`.
- [ ] **[A06]** Confirmar que o canal `daily` do log está funcionando: verificar criação de `storage/logs/laravel-YYYY-MM-DD.log`.
- [ ] **[A09]** Confirmar que `HSTS max-age=31536000` está sendo enviado. Atenção: depois de ativado, o browser força HTTPS por 1 ano. Só ativar se o certificado SSL estiver estável.
- [ ] **[A12]** Configurar monitoramento externo (UptimeRobot ou AWS CloudWatch) apontando para `GET /ping`. Receber alerta se status != 200.
- [ ] Confirmar que `SENTRY_LARAVEL_DSN` está configurado no `.env` de produção. Sem isso, exceções não são capturadas no Sentry.
- [ ] Confirmar que `APP_DEBUG=false` está no `.env` de produção.
- [ ] Confirmar que `APP_ENV=production` está no `.env` de produção.

---

## 🟡 RECOMENDADO — Resolver em até 30 dias

- [ ] **[A10]** Revisar `.env.example`: alterar `DB_USERNAME=vivensi_app` (princípio do menor privilégio), `MAIL_HOST=` (vazio), `ASAAS_ENVIRONMENT=production`.
- [ ] **[A11]** Fixar versões no `composer.json`: `guzzle ^7.8`, `doctrine/dbal ^3.8`, `guzzle/psr7 ^2.6`. Remover `minimum-stability: dev`.
- [ ] **[A13]** Remover `open-pix/php-sdk` do `composer.json` (não utilizado, AbacatePay é o gateway ativo).
- [ ] Confirmar que o Supervisor está rodando os workers de fila: `supervisorctl status`.
- [ ] Confirmar que `QUEUE_CONNECTION=redis` no `.env` de produção (não `database` ou `sync`).
- [ ] Testar o fluxo completo de pagamento AbacatePay em produção com uma transação real de baixo valor antes de abrir para todos os usuários.
- [ ] Confirmar que `AWS_BUCKET` e credenciais S3 estão configuradas para upload de comprovantes de rifa.
- [ ] Confirmar que `EVOLUTION_API_URL` e `EVOLUTION_GLOBAL_KEY` estão corretos e que as instâncias WhatsApp estão conectadas.
- [ ] Confirmar que `SENTRY_RELEASE` está configurado para rastrear versões de deploy.

---

## 🔵 PLANEJAMENTO — Próximos 90 dias

- [ ] **[A14]** Planejar upgrade de Laravel 9 para Laravel 11 (EOL desde fev/2024).
- [ ] **[A13]** Avaliar substituição de `fruitcake/laravel-cors` pelo CORS nativo do Laravel 9+ (`config/cors.php`).
- [ ] Implementar política de retenção S3 para arquivos de rifa (comprovantes, imagens) — definir lifecycle rules no bucket.
- [ ] Implementar testes automatizados em CI/CD (GitHub Actions) cobrindo os fluxos críticos de rifa e pagamento.
- [ ] Revisar e fortalecer a CSP (`Content-Security-Policy`) no `SecurityHeaders` para incluir `script-src`, `style-src` e `connect-src` explícitos.
- [ ] Documentar o processo de DR (Disaster Recovery): como restaurar o backup do banco, como recriar a instância, SLA de recuperação.
- [ ] Configurar alertas de fila no Horizon para quando jobs começarem a acumular (latência > X segundos).

---

## Checklist de Verificação Final (D-1 antes do go-live)

```
[ ] php artisan config:cache
[ ] php artisan route:cache
[ ] php artisan view:cache
[ ] php artisan migrate --force
[ ] php artisan queue:restart
[ ] curl -I https://seudominio.com.br | grep -E "X-Frame|HSTS|X-Content"
[ ] curl https://seudominio.com.br/ping → {"status":"ok"}
[ ] Testar login, reserva de bilhete e upload de comprovante
[ ] Verificar Sentry recebendo eventos de teste
[ ] Verificar Horizon dashboard mostrando workers ativos
[ ] Verificar backup do dia criado em storage/backups/
```

---

*Checklist gerado em 2026-04-26 — baseado na auditoria técnica completa do repositório.*
