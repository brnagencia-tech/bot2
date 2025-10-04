#!/usr/bin/env bash

# BotWhatsApp – Deploy Script
# Usage (example):
#   REPO=https://github.com/brnagencia-tech/bot2.git \
#   BRANCH=main \
#   APP=/var/www/app \
#   PHP_FPM_SERVICE=php8.4-fpm \
#   WEB_USER=www-data \
#   WEB_GROUP=www-data \
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
chmod -R ug+rwx storage bootstrap/cache || true

log "Instalando dependências do Composer"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

log "Executando migrações"
php artisan migrate --force || abort "Falha ao migrar banco de dados"

log "Limpando e gerando caches do Laravel"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Garante symlink public/storage
if [ ! -L public/storage ]; then
  log "Criando symlink public/storage"
  php artisan storage:link || true
fi

log "Reiniciando serviço PHP-FPM ($PHP_FPM_SERVICE)"
systemctl restart "$PHP_FPM_SERVICE"

log "Deploy concluído com sucesso."

