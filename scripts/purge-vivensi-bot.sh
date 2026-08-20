#!/bin/bash
# Purga mensagens/chats da instancia VIVENSI-BOT (bot interno Vivensi) mais
# velhos que 60s no Postgres da Evolution API v2.3.6. Ninguem com acesso a
# Evolution DB ve os dados enviados/consultados pelo cliente via bot.
# Bot processa mensagem in-memory via webhook — nao le da tabela Message.
#
# Deploy: rodar como root a cada 1 minuto (cron ou systemd timer).
#   * * * * * root /opt/evolution-api/purge-vivensi-bot.sh
#
export PGPASSWORD='evolution@2026'
IID='7ac50e73-330a-4746-b8c7-e16b2133e8df'
CUT=$(($(date +%s) - 60))
LOG=/var/log/vivensi-bot-purge.log
{
  echo "=== $(date -Is) ==="
  psql -U evolution -h localhost -d evolution -v ON_ERROR_STOP=1 \
    -c "DELETE FROM public.\"Message\" WHERE \"instanceId\"='${IID}' AND \"messageTimestamp\"<${CUT};" \
    -c "DELETE FROM public.\"Chat\" WHERE \"instanceId\"='${IID}' AND \"updatedAt\" < NOW() - INTERVAL '60 seconds';"
} >> "$LOG" 2>&1
