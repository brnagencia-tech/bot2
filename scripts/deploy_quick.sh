#!/usr/bin/env bash

# BotWhatsApp – Deploy Rápido e Seguro (wrapper)
# Uso típico (como root no servidor):
#   REPO=https://github.com/brnagencia-tech/bot2.git \
#   BRANCH=main \
#   APP=/var/www/app \
#   PHP_FPM_SERVICE=php8.4-fpm \
#   WEB_USER=www-data \
#   WEB_GROUP=www-data \
#   WAWEB_MANAGE=true \
#   WAWEB_PORT=3001 \
#   WAWEB_SHARED_SECRET="sua-chave" \
#   LARAVEL_BASE_URL="http://localhost" \
#   BUILD_ASSETS=false \
#   APP_HEALTH_URL="http://localhost/up" \
#   bash scripts/deploy_quick.sh

set -Eeuo pipefail

echo "[quick] Pré-checando binários (git, rsync, php, composer, curl)"
for b in git rsync php composer curl; do
  command -v "$b" >/dev/null 2>&1 || { echo "[quick] ERRO: binário '$b' não encontrado" >&2; exit 1; }
done

: "${REPO:?Defina REPO (URL do repositório)}"
: "${BRANCH:?Defina BRANCH (ex.: main)}"
: "${APP:?Defina APP (ex.: /var/www/app)}"
: "${PHP_FPM_SERVICE:?Defina PHP_FPM_SERVICE (ex.: php8.4-fpm)}"

WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
WAWEB_MANAGE="${WAWEB_MANAGE:-true}"
WAWEB_PORT="${WAWEB_PORT:-3001}"
WAWEB_SHARED_SECRET="${WAWEB_SHARED_SECRET:-}"
LARAVEL_BASE_URL="${LARAVEL_BASE_URL:-http://localhost}"
BUILD_ASSETS="${BUILD_ASSETS:-false}"
APP_HEALTH_URL="${APP_HEALTH_URL:-http://localhost/up}"

echo "[quick] Iniciando deploy seguro em $APP (branch $BRANCH)"

REPO="$REPO" \
BRANCH="$BRANCH" \
APP="$APP" \
PHP_FPM_SERVICE="$PHP_FPM_SERVICE" \
WEB_USER="$WEB_USER" \
WEB_GROUP="$WEB_GROUP" \
WAWEB_MANAGE="$WAWEB_MANAGE" \
WAWEB_PORT="$WAWEB_PORT" \
WAWEB_SHARED_SECRET="$WAWEB_SHARED_SECRET" \
LARAVEL_BASE_URL="$LARAVEL_BASE_URL" \
BUILD_ASSETS="$BUILD_ASSETS" \
APP_HEALTH_URL="$APP_HEALTH_URL" \
bash "$(dirname "$0")/deploy.sh"

echo "[quick] Deploy finalizado. Versão publicada em $APP/.release (se disponível)."

