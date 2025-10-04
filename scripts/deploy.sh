#!/usr/bin/env bash

# BotWhatsApp – Deploy Script (robusto)
# Uso (exemplo):
#   REPO=https://github.com/brnagencia-tech/bot2.git \
#   BRANCH=main \
#   APP=/var/www/app \
#   PHP_FPM_SERVICE=php8.4-fpm \
#   WEB_USER=www-data \
#   WEB_GROUP=www-data \
#   BUILD_ASSETS=false \
#   bash scripts/deploy.sh

set -Eeuo pipefail

log() { echo -e "[deploy] $*"; }
abort() { echo "[deploy] ERROR: $*" >&2; exit 1; }

: "${REPO:?REPO não definido}"
: "${BRANCH:?BRANCH não definido}"
: "${APP:?APP não definido}"

PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
APP_HEALTH_URL="${APP_HEALTH_URL:-http://localhost/up}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
BUILD_ASSETS="${BUILD_ASSETS:-false}"
# WhatsApp Web (Baileys) systemd management
WAWEB_MANAGE="${WAWEB_MANAGE:-true}"
WAWEB_SERVICE_NAME="${WAWEB_SERVICE_NAME:-bot-whatsapp-web}"
WAWEB_PORT="${WAWEB_PORT:-3001}"
WAWEB_USER="${WAWEB_USER:-$WEB_USER}"
WAWEB_GROUP="${WAWEB_GROUP:-$WEB_GROUP}"
WAWEB_WORKDIR="${WAWEB_WORKDIR:-$APP/whatsapp_service}"
BACKUP="${BACKUP:-true}"
NPM_CACHE_DIR="${NPM_CACHE_DIR:-/var/www/.npm}"

TMP_DIR=$(mktemp -d /tmp/app-release.XXXXXX)
cleanup() { rm -rf "$TMP_DIR" || true; }
trap cleanup EXIT

log "Clonando $REPO (branch $BRANCH) em $TMP_DIR"
git clone --depth=1 -b "$BRANCH" "$REPO" "$TMP_DIR"

SRC="$TMP_DIR"
if [ ! -f "$SRC/artisan" ] && [ -f "$SRC/app/laravel/artisan" ]; then
  SRC="$SRC/app/laravel"
fi

[ -f "$SRC/artisan" ] || abort "Arquivo artisan não encontrado no release. SRC=$SRC"

mkdir -p "$APP"

# Modo manutenção se já existir uma app anterior
if [ -f "$APP/artisan" ]; then
  (cd "$APP" && php artisan down) || true
fi

# Backup leve (sem vendor/node_modules)
if [ "$BACKUP" = "true" ]; then
  ts=$(date +%Y%m%d-%H%M%S)
  log "Criando backup leve do app atual: /root/app-backup-$ts.tgz"
  tar --exclude="vendor" --exclude="node_modules" -czf "/root/app-backup-$ts.tgz" -C "$APP" . || true
fi

log "Sincronizando arquivos para $APP"
rsync -a --delete \
  --exclude=".git/" \
  --exclude=".github/" \
  --exclude=".env" \
  --exclude="storage/" \
  --exclude="vendor/" \
  --exclude="node_modules/" \
  --exclude="public/storage" \
  "$SRC"/ "$APP"/

cd "$APP"

# Remover possíveis arquivos inválidos fora do repo que quebrem o PHP
[ -f "_admin_dash_hotfix.php" ] && { log "Removendo arquivo inválido _admin_dash_hotfix.php"; rm -f _admin_dash_hotfix.php; }

log "Ajustando permissões de storage e cache"
mkdir -p storage bootstrap/cache
chown -R "$WEB_USER":"$WEB_GROUP" storage bootstrap/cache || true
find storage bootstrap/cache -type d -exec chmod 775 {} + 2>/dev/null || true

log "Instalando dependências do Composer"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Symlink public/storage
if [ ! -L public/storage ]; then
  log "Criando symlink public/storage"
  php artisan storage:link || true
fi

if [ "$BUILD_ASSETS" = "true" ]; then
  log "BUILD_ASSETS=true → compilando assets com Vite"
  if ! command -v npm >/dev/null 2>&1; then
    abort "npm não encontrado. Instale Node/NPM ou defina BUILD_ASSETS=false e versione public/build."
  fi
  install -d -m 775 -o "$WEB_USER" -g "$WEB_GROUP" "$NPM_CACHE_DIR"
  rm -rf node_modules
  if ! sudo -u "$WEB_USER" env npm_config_cache="$NPM_CACHE_DIR" npm ci --no-audit --no-fund; then
    sudo -u "$WEB_USER" env npm_config_cache="$NPM_CACHE_DIR" npm install --no-audit --no-fund
  fi
  sudo -u "$WEB_USER" env npm_config_cache="$NPM_CACHE_DIR" npx vite build
else
  log "BUILD_ASSETS=false → pulando build; usando public/build versionado"
fi

log "Executando migrações"
php artisan migrate --force --no-interaction || abort "Falha ao migrar banco de dados"

log "Limpando caches do Laravel"
php artisan optimize:clear || true

log "Gerando config cache"
if ! php artisan config:cache; then
  log "Falha em config:cache → limpando caches e seguindo sem cache"
  php artisan optimize:clear || true
fi

log "Gerando route cache (ignora closures)"
if ! php artisan route:cache; then
  log "route:cache falhou (rotas com closures?) → mantendo sem cache"
  php artisan optimize:clear || true
fi

log "Gerando view cache"
php artisan view:cache || true

log "Reiniciando serviço PHP-FPM ($PHP_FPM_SERVICE)"
systemctl restart "$PHP_FPM_SERVICE"

# Sair do modo manutenção
php artisan up || true

# Gerenciar serviço do WhatsApp Web (Node/Baileys)
if [ "$WAWEB_MANAGE" = "true" ]; then
  log "Instalando/atualizando serviço systemd do WhatsApp Web ($WAWEB_SERVICE_NAME)"
  # Instala dependências do whatsapp_service, se npm estiver disponível
  if command -v npm >/dev/null 2>&1; then
    log "Preparando ambiente npm e diretório do whatsapp_service"
    install -d -m 775 -o "$WAWEB_USER" -g "$WAWEB_GROUP" "$NPM_CACHE_DIR"
    install -d -m 775 -o "$WAWEB_USER" -g "$WAWEB_GROUP" "$WAWEB_WORKDIR"
    chown -R "$WAWEB_USER":"$WAWEB_GROUP" "$WAWEB_WORKDIR"
    log "Instalando dependências do whatsapp_service (como $WAWEB_USER)"
    (cd "$WAWEB_WORKDIR" && sudo -u "$WAWEB_USER" env HOME=/var/www npm install --no-audit --no-fund) || true
  else
    log "npm não encontrado; garanta Node 18+ para o serviço WhatsApp Web"
  fi
  UNIT_TEMPLATE="$APP/scripts/systemd/bot-whatsapp-web.service"
  if [ -f "$UNIT_TEMPLATE" ]; then
    TMP_UNIT=$(mktemp)
    sed -e "s|{{PORT}}|$WAWEB_PORT|g" \
        -e "s|{{USER}}|$WAWEB_USER|g" \
        -e "s|{{GROUP}}|$WAWEB_GROUP|g" \
        -e "s|{{WORKDIR}}|$WAWEB_WORKDIR|g" \
        -e "s|{{WAWEB_SHARED_SECRET}}|${WAWEB_SHARED_SECRET:-}|g" \
        -e "s|{{LARAVEL_BASE_URL}}|${LARAVEL_BASE_URL:-}|g" \
        -e "s|{{TENANT_ID}}|${TENANT_ID:-tenant-1}|g" \
        "$UNIT_TEMPLATE" > "$TMP_UNIT"
    install -o root -g root -m 0644 "$TMP_UNIT" \
      "/etc/systemd/system/${WAWEB_SERVICE_NAME}.service"
    rm -f "$TMP_UNIT"
    systemctl daemon-reload
    systemctl enable --now "${WAWEB_SERVICE_NAME}.service"
    log "Reiniciando serviço ${WAWEB_SERVICE_NAME}.service"
    systemctl restart "${WAWEB_SERVICE_NAME}.service" || true
  else
    log "Template não encontrado: $UNIT_TEMPLATE (pulando gestão do serviço)"
  fi
fi

# Grava marcador de versão (.release)
{
  COMMIT="n/a"; MSG="local sync"; SRC="local"
  if [ -d "$TMP_DIR/.git" ]; then
    COMMIT=$(git -C "$TMP_DIR" rev-parse --short HEAD 2>/dev/null || echo "n/a")
    MSG=$(git -C "$TMP_DIR" log -1 --pretty=%s 2>/dev/null || echo "")
    SRC="github"
  elif [ -d /tmp/app-release/.git ]; then
    COMMIT=$(git -C /tmp/app-release rev-parse --short HEAD 2>/dev/null || echo "n/a")
    MSG=$(git -C /tmp/app-release log -1 --pretty=%s 2>/dev/null || echo "")
    SRC="github"
  fi
  printf "commit=%s | %s | source=%s | time=%s\n" \
    "$COMMIT" "$MSG" "$SRC" "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" \
    > "$APP/.release"
  chown "$WEB_USER:$WEB_GROUP" "$APP/.release" 2>/dev/null || true
  log "Release marker gravado em $APP/.release -> $(cat "$APP/.release" 2>/dev/null)"
} || true

log "Deploy concluído com sucesso."

# Health check final
sleep 1
if command -v curl >/dev/null 2>&1; then
  if curl -fsS -m 5 "$APP_HEALTH_URL" >/dev/null; then
    log "Health OK: $APP_HEALTH_URL"
  else
    log "Health FAIL em $APP_HEALTH_URL"
    log "Últimas linhas do log Laravel:"
    tail -n 200 storage/logs/laravel.log 2>/dev/null || true
    exit 1
  fi
else
  log "curl não disponível para health-check"
fi
