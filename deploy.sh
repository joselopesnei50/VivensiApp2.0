#!/bin/bash
set -e

APP_DIR="/var/www/vivensi"
PHP_BIN="${PHP_BIN:-php}"
PHP_FLAGS="-d memory_limit=512M"

echo "🚀 Iniciando Deployment Vivensi — $(date)"
echo "──────────────────────────────────────────"

# Validar diretório
if [ ! -d "$APP_DIR" ]; then
    echo "❌ Diretório $APP_DIR não encontrado."
    exit 1
fi

cd "$APP_DIR"

# ── 1. Verificação de pré-requisitos ──────────────────────────────────────

echo "🔍 Verificando pré-requisitos..."

# PHP disponível?
if ! command -v "$PHP_BIN" &>/dev/null; then
    echo "❌ PHP não encontrado. Instale PHP 8.0+ antes de continuar."
    exit 1
fi
PHP_VERSION=$($PHP_BIN -r "echo PHP_VERSION;")
echo "   PHP: $PHP_VERSION"

# .env existe?
if [ ! -f "$APP_DIR/.env" ]; then
    echo "❌ Arquivo .env não encontrado em $APP_DIR"
    echo "   Execute: cp .env.example .env && nano .env"
    exit 1
fi

# APP_KEY está definida?
APP_KEY_VALUE=$(grep "^APP_KEY=" "$APP_DIR/.env" | cut -d= -f2)
if [ -z "$APP_KEY_VALUE" ]; then
    echo "⚠️  APP_KEY vazia — gerando chave automaticamente..."
    $PHP_BIN $PHP_FLAGS artisan key:generate --no-interaction --force
fi

# Testar conexão com banco de dados
echo "🗄️  Testando conexão com banco de dados..."
DB_CHECK=$($PHP_BIN $PHP_FLAGS artisan db:show --no-interaction 2>&1 || true)
if echo "$DB_CHECK" | grep -qi "refused\|Connection refused\|Access denied\|No such file"; then
    echo "❌ Falha na conexão com banco de dados:"
    echo "   $DB_CHECK"
    echo "   Verifique DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD no .env"
    exit 1
fi
echo "   Banco de dados: OK"

# Verificar se Redis está disponível quando configurado como driver
CACHE_DRIVER_VAL=$(grep "^CACHE_DRIVER=" "$APP_DIR/.env" | cut -d= -f2)
SESSION_DRIVER_VAL=$(grep "^SESSION_DRIVER=" "$APP_DIR/.env" | cut -d= -f2)
QUEUE_DRIVER_VAL=$(grep "^QUEUE_CONNECTION=" "$APP_DIR/.env" | cut -d= -f2)

if [[ "$CACHE_DRIVER_VAL" == "redis" || "$SESSION_DRIVER_VAL" == "redis" || "$QUEUE_DRIVER_VAL" == "redis" ]]; then
    REDIS_HOST_VAL=$(grep "^REDIS_HOST=" "$APP_DIR/.env" | cut -d= -f2)
    REDIS_PORT_VAL=$(grep "^REDIS_PORT=" "$APP_DIR/.env" | cut -d= -f2)
    REDIS_PORT_VAL="${REDIS_PORT_VAL:-6379}"
    echo "🔴 Verificando Redis em ${REDIS_HOST_VAL}:${REDIS_PORT_VAL}..."
    if ! timeout 3 bash -c "echo PING | nc -q1 ${REDIS_HOST_VAL} ${REDIS_PORT_VAL} 2>/dev/null" | grep -q "PONG"; then
        echo "❌ Redis não respondeu em ${REDIS_HOST_VAL}:${REDIS_PORT_VAL}"
        echo "   Drivers configurados: CACHE=${CACHE_DRIVER_VAL} SESSION=${SESSION_DRIVER_VAL} QUEUE=${QUEUE_DRIVER_VAL}"
        echo "   SOLUÇÃO: instale Redis (sudo apt install redis-server) OU mude para:"
        echo "     CACHE_DRIVER=file"
        echo "     SESSION_DRIVER=file"
        echo "     QUEUE_CONNECTION=database"
        echo "   Abortando para evitar travamento do artisan."
        exit 1
    fi
    echo "   Redis: OK"
fi

# ── 2. Permissões iniciais ────────────────────────────────────────────────
echo "🔐 Ajustando permissões..."
sudo chown -R ubuntu:www-data . 2>/dev/null || sudo chown -R www-data:www-data . 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache

# ── 3. Modo de manutenção ─────────────────────────────────────────────────
echo "🔧 Entrando em modo de manutenção..."
$PHP_BIN $PHP_FLAGS artisan down --no-interaction || true

# ── 4. Git pull ───────────────────────────────────────────────────────────
echo "📥 Puxando atualizações do GitHub..."
git pull origin main

# ── 5. Composer install ───────────────────────────────────────────────────
echo "📦 Instalando dependências (Composer)..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Regenerar cache de pacotes após composer install
$PHP_BIN $PHP_FLAGS artisan package:discover --no-interaction --ansi

# ── 6. Migrações ──────────────────────────────────────────────────────────
echo "🗄️  Executando migrações..."
$PHP_BIN $PHP_FLAGS artisan migrate --force --no-interaction

# ── 7. Cache de config/rotas/views ───────────────────────────────────────
echo "⚡ Otimizando cache Laravel..."
$PHP_BIN $PHP_FLAGS artisan config:cache --no-interaction
$PHP_BIN $PHP_FLAGS artisan route:cache --no-interaction
$PHP_BIN $PHP_FLAGS artisan view:cache --no-interaction

# ── 8. Permissões finais ──────────────────────────────────────────────────
echo "🔐 Ajustando permissões finais..."
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache

# ── 9. Reiniciar workers de fila ─────────────────────────────────────────
if [[ "$QUEUE_DRIVER_VAL" != "sync" && "$QUEUE_DRIVER_VAL" != "" ]]; then
    echo "🔄 Reiniciando workers de fila..."
    $PHP_BIN $PHP_FLAGS artisan queue:restart --no-interaction || true

    # Reiniciar workers via Supervisor
    if command -v supervisorctl &>/dev/null; then
        echo "🔄 Reiniciando workers via Supervisor..."
        sudo supervisorctl reread 2>/dev/null || true
        sudo supervisorctl update 2>/dev/null || true
        sudo supervisorctl restart vivensi-worker-default:* 2>/dev/null || true
        sudo supervisorctl restart vivensi-worker-whatsapp:* 2>/dev/null || true
        sudo supervisorctl status 2>/dev/null || true
    fi
fi

# ── 10. Sair do modo de manutenção ───────────────────────────────────────
echo "✅ Saindo do modo de manutenção..."
$PHP_BIN $PHP_FLAGS artisan up --no-interaction

echo ""
echo "✅ Deploy concluído com sucesso! — $(date)"
