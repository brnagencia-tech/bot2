#!/usr/bin/env bash
set -euo pipefail

DOMAIN="bot.agenciabrn.com.br"
NODE_SERVICE_NAME="whatsapp"
WHATSAPP_BASE_URL="http://127.0.0.1:3001"

echo "==> Entrando no diretório da aplicação"
cd "$APP_DIR"

echo "==> Atualizando código"
git fetch --all --prune
git checkout main
git pull --ff-only origin main

echo '==> Instalando dependências do PHP (composer)'
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --prefer-dist --optimize-autoloader
else
  echo "ERRO: composer não encontrado no servidor." >&2
  exit 1
fi

# Opcional: build front-end se existir package.json com scripts build
if [ -f "package.json" ] && grep -q '"build"' package.json; then
  echo '==> Build front-end'
  npm ci
  npm run build
fi

echo "==> Garantindo variável do serviço WhatsApp no .env"
if grep -q '^WHATSAPP_WEB_BASE_URL=' .env; then
  sed -i "s|^WHATSAPP_WEB_BASE_URL=.*|WHATSAPP_WEB_BASE_URL=${WHATSAPP_BASE_URL}|" .env
else
  echo "WHATSAPP_WEB_BASE_URL=${WHATSAPP_BASE_URL}" >> .env
fi

echo "==> Ajustando permissões"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
find "$APP_DIR/storage" -type f -exec chmod 664 {} \;
chmod -R 775 "$APP_DIR/bootstrap/cache"

echo "==> Laravel: modo manutenção / migrações / caches"
php artisan down || true
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
php artisan up

echo "==> Recarregando serviços do PHP/Nginx"
systemctl reload nginx || true
systemctl restart php*-fpm

echo "==> Garantindo serviço PM2 do WhatsApp"
if pm2 describe "${NODE_SERVICE_NAME}" >/dev/null 2>&1; then
  pm2 restart "${NODE_SERVICE_NAME}"
else
  pm2 start "$APP_DIR/whatsapp_service/server.js" --name "${NODE_SERVICE_NAME}" --time
fi
pm2 save

echo "==> Smoke tests"
curl -fsS "https://${DOMAIN}/login" -H "Host: ${DOMAIN}" >/dev/null
curl -fsS "http://127.0.0.1:3001/status" >/dev/null

echo "==> Deploy OK ✔"

