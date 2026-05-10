# Vivensi — Fix Team Orchestrator

## Stack
Laravel 11 · Livewire 3 · FilamentPHP 3 · MySQL 8 · Redis · AWS S3/CloudFront
PHP 8.2 · Queue: database driver · Supervisor (a configurar)

## Missão desta sessão
Corrigir os 23 itens CRÍTICOS e ALTOS encontrados no AUDIT_SUMMARY.md.
**Nenhuma alteração no servidor de produção.** Todas as correções são feitas no código local.
Após cada grupo de correções, rodar `php artisan test --filter` para validar.

## Regras de delegação

### Fluxo obrigatório para qualquer correção
1. `env-secrets-fixer` → primeiro, sempre. Sem credenciais no código, nada mais adianta.
2. `payment-hardener` → idempotência e lockForUpdate nos webhooks financeiros.
3. `tenant-guard` → isolamento de tenant e model $hidden.
4. `attack-surface-patcher` → rotas, headers, throttle, senhas.
5. `infra-configurator` → Supervisor, SESSION_SECURE_COOKIE, queue workers.
6. `progress-reporter` → roda por último, gera relatório visual no terminal.

### Paralelismo permitido
Os agentes 2, 3 e 4 podem rodar em paralelo após o agente 1 terminar.
O agente 5 (infra) pode rodar em paralelo com 2, 3 e 4.
O agente 6 (reporter) só roda quando todos os outros terminarem.

### Nunca fazer
- `rm -rf` em qualquer diretório
- Alterar arquivos fora do projeto (sem `~/`, sem `/etc/`, sem `/var/`)
- Push para git remoto
- Executar `php artisan migrate` em produção
- Alterar `.env.production` ou qualquer `.env` de servidor real

### Git safety
Antes de qualquer modificação, verificar se `git status` está limpo.
Se não estiver, parar e reportar ao usuário.
Após cada agente concluir, fazer `git add -p` seletivo e commit descritivo.

## Arquivos de referência
- Checklist: `AUDIT_SUMMARY.md` (deve estar na raiz do projeto)
- Agentes: `.claude/agents/`
