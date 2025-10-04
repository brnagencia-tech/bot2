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
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
BUILD_ASSETS="${BUILD_ASSETS:-false}"
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

log "Limpando e gerando caches do Laravel"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

log "Reiniciando serviço PHP-FPM ($PHP_FPM_SERVICE)"
systemctl restart "$PHP_FPM_SERVICE"

# Sair do modo manutenção
php artisan up || true

log "Deploy concluído com sucesso."
