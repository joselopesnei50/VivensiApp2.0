#!/usr/bin/env bash
# verify-leaked-credentials.sh — testa se credenciais vazadas no histórico Git
# ainda são aceitas pelos serviços de produção. 100% read-only, não altera nada.
#
# Uso (no VPS Vivensi, como root ou via sudo):
#   bash infra/verify-leaked-credentials.sh
#
# O que faz:
#   1. Testa DB_PASSWORD antiga contra o MySQL
#   2. Testa EVOLUTION_GLOBAL_KEY antiga contra a Evolution API
#   3. Testa AUTHENTICATION_API_KEY antiga (mesma coisa que 2, semanticamente)
#   4. Reporta veredicto por credencial: INERTE / ATIVA (ROTACIONAR!)
#
# NUNCA escreve em .env, nunca reinicia serviço, nunca cria arquivo.

set -u

APP_DIR="${APP_DIR:-/var/www/vivensi}"
ENV_FILE="$APP_DIR/.env"

# ── Valores vazados no histórico Git (fixos, referência do relatório) ────────
LEAKED_DB_PASS='Viv3nsi@2026'
LEAKED_EVO_KEY='e838f5d5b86ea0fe27492c283c27498ffe0b250085896f1eaa8093baf0a3309e'
LEAKED_AUTH_KEY='SenhaForteVivensi2026@!'

# ── Cores ────────────────────────────────────────────────────────────────────
red()    { printf '\033[31m%s\033[0m\n' "$*"; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*"; }
bold()   { printf '\033[1m%s\033[0m\n' "$*"; }
dim()    { printf '\033[2m%s\033[0m\n' "$*"; }

[ -f "$ENV_FILE" ] || { red "❌ $ENV_FILE não encontrado"; exit 1; }

get_env() {
  grep -E "^${1}=" "$ENV_FILE" | head -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/; s/^'\''(.*)'\''$/\1/'
}

DB_HOST="$(get_env DB_HOST)";     DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(get_env DB_PORT)";     DB_PORT="${DB_PORT:-3306}"
DB_NAME="$(get_env DB_DATABASE)"
DB_USER="$(get_env DB_USERNAME)"
EVO_URL="$(get_env EVOLUTION_API_URL)"
[ -z "$EVO_URL" ] && EVO_URL="$(get_env EVOLUTION_URL)"

bold "════════════════════════════════════════════════════════════════════"
bold "  Vivensi — Verificação de credenciais vazadas no histórico Git"
bold "════════════════════════════════════════════════════════════════════"
echo ""
dim "  App dir:       $APP_DIR"
dim "  MySQL:         $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
dim "  Evolution URL: ${EVO_URL:-<não configurada>}"
echo ""

FINDINGS=()

# ── 1. Testar DB_PASSWORD vazada ─────────────────────────────────────────────
bold "── [1/3] DB_PASSWORD ──"
echo "   Testando senha vazada: '$LEAKED_DB_PASS'"

if ! command -v mysql >/dev/null; then
  yellow "   ⚠️  mysql client ausente — pulando teste"
  FINDINGS+=("DB_PASSWORD: TESTE_PULADO")
else
  if MYSQL_PWD="$LEAKED_DB_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
      -e "SELECT 1" "$DB_NAME" >/dev/null 2>&1; then
    red "   🚨 AINDA ACEITA — a senha vazada ainda funciona no MySQL!"
    FINDINGS+=("DB_PASSWORD: 🚨 ATIVA — ROTACIONAR JÁ (use rotate-db-password.sh)")
  else
    green "   ✅ REJEITADA — a senha vazada não abre mais o MySQL"
    FINDINGS+=("DB_PASSWORD: ✅ INERTE")
  fi
fi
echo ""

# ── 2. Testar EVOLUTION_GLOBAL_KEY vazada ────────────────────────────────────
bold "── [2/3] EVOLUTION_GLOBAL_KEY ──"

if [ -z "$EVO_URL" ]; then
  yellow "   ⚠️  EVOLUTION_API_URL não configurada no .env — pulando teste"
  FINDINGS+=("EVOLUTION_GLOBAL_KEY: TESTE_PULADO (URL ausente)")
elif ! command -v curl >/dev/null; then
  yellow "   ⚠️  curl ausente — pulando teste"
  FINDINGS+=("EVOLUTION_GLOBAL_KEY: TESTE_PULADO (sem curl)")
else
  # Normaliza URL sem barra final
  EVO_URL_CLEAN="${EVO_URL%/}"
  echo "   URL alvo:       $EVO_URL_CLEAN/instance/fetchInstances"
  echo "   Chave vazada:   ${LEAKED_EVO_KEY:0:16}...${LEAKED_EVO_KEY: -8}"

  HTTP_CODE=$(curl -sS -o /tmp/evo_leaked_test.$$ -w "%{http_code}" \
                --max-time 15 \
                -H "apikey: $LEAKED_EVO_KEY" \
                "$EVO_URL_CLEAN/instance/fetchInstances" 2>/dev/null || echo "000")

  BODY_PREVIEW=$(head -c 200 /tmp/evo_leaked_test.$$ 2>/dev/null | tr -d '\n')
  rm -f /tmp/evo_leaked_test.$$

  echo "   HTTP status:    $HTTP_CODE"
  [ -n "$BODY_PREVIEW" ] && dim "   Resposta:       $BODY_PREVIEW"

  case "$HTTP_CODE" in
    401|403)
      green "   ✅ REJEITADA — Evolution recusou a chave vazada ($HTTP_CODE)"
      FINDINGS+=("EVOLUTION_GLOBAL_KEY: ✅ INERTE")
      ;;
    200)
      red "   🚨 AINDA ACEITA — Evolution respondeu 200 com a chave vazada!"
      FINDINGS+=("EVOLUTION_GLOBAL_KEY: 🚨 ATIVA — ROTACIONAR JÁ")
      ;;
    000)
      yellow "   ⚠️  Falha ao contatar Evolution API — verifique EVOLUTION_API_URL"
      FINDINGS+=("EVOLUTION_GLOBAL_KEY: TESTE_INCONCLUSIVO (rede)")
      ;;
    *)
      yellow "   ⚠️  Resposta inesperada ($HTTP_CODE) — verificar manualmente"
      FINDINGS+=("EVOLUTION_GLOBAL_KEY: TESTE_INCONCLUSIVO ($HTTP_CODE)")
      ;;
  esac
fi
echo ""

# ── 3. AUTHENTICATION_API_KEY vazada (mesmo header, valor diferente) ─────────
bold "── [3/3] AUTHENTICATION_API_KEY ──"
echo "   Nota: no Evolution API, esta chave é enviada no MESMO header 'apikey'"
echo "   que o EVOLUTION_GLOBAL_KEY. Testamos separadamente por completude."

if [ -z "$EVO_URL" ] || ! command -v curl >/dev/null; then
  yellow "   ⚠️  Sem URL ou curl — pulando"
  FINDINGS+=("AUTHENTICATION_API_KEY: TESTE_PULADO")
else
  EVO_URL_CLEAN="${EVO_URL%/}"
  echo "   Chave vazada:   $LEAKED_AUTH_KEY"

  HTTP_CODE=$(curl -sS -o /tmp/evo_leaked_auth.$$ -w "%{http_code}" \
                --max-time 15 \
                -H "apikey: $LEAKED_AUTH_KEY" \
                "$EVO_URL_CLEAN/instance/fetchInstances" 2>/dev/null || echo "000")

  BODY_PREVIEW=$(head -c 200 /tmp/evo_leaked_auth.$$ 2>/dev/null | tr -d '\n')
  rm -f /tmp/evo_leaked_auth.$$

  echo "   HTTP status:    $HTTP_CODE"
  [ -n "$BODY_PREVIEW" ] && dim "   Resposta:       $BODY_PREVIEW"

  case "$HTTP_CODE" in
    401|403)
      green "   ✅ REJEITADA — Evolution recusou a chave vazada ($HTTP_CODE)"
      FINDINGS+=("AUTHENTICATION_API_KEY: ✅ INERTE")
      ;;
    200)
      red "   🚨 AINDA ACEITA — Evolution respondeu 200!"
      FINDINGS+=("AUTHENTICATION_API_KEY: 🚨 ATIVA — ROTACIONAR JÁ")
      ;;
    000)
      yellow "   ⚠️  Falha ao contatar Evolution API"
      FINDINGS+=("AUTHENTICATION_API_KEY: TESTE_INCONCLUSIVO (rede)")
      ;;
    *)
      yellow "   ⚠️  Resposta inesperada ($HTTP_CODE)"
      FINDINGS+=("AUTHENTICATION_API_KEY: TESTE_INCONCLUSIVO ($HTTP_CODE)")
      ;;
  esac
fi
echo ""

# ── Veredicto final ─────────────────────────────────────────────────────────
bold "════════════════════════════════════════════════════════════════════"
bold "  VEREDICTO"
bold "════════════════════════════════════════════════════════════════════"
echo ""

ANY_ACTIVE=0
for f in "${FINDINGS[@]}"; do
  echo "  • $f"
  echo "$f" | grep -q "🚨 ATIVA" && ANY_ACTIVE=1
done
echo ""

if [ "$ANY_ACTIVE" -eq 1 ]; then
  red "════════════════════════════════════════════════════════════════════"
  red "  ⚠️  PELO MENOS UMA CREDENCIAL VAZADA AINDA ESTÁ ATIVA"
  red "  Rotacionar ANTES de reescrever o histórico Git."
  red "════════════════════════════════════════════════════════════════════"
  exit 2
else
  green "════════════════════════════════════════════════════════════════════"
  green "  ✅ TODAS AS CREDENCIAIS VAZADAS ESTÃO INERTES"
  green "  Seguro prosseguir para a fase de limpeza do histórico Git."
  green "════════════════════════════════════════════════════════════════════"
  exit 0
fi
