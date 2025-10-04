#!/usr/bin/env bash
# Deploy remoto: DigitalOcean baixa do GitHub e faz build/migrations.
# Requisitos no servidor: git, composer (instala se não tiver), node/npm (v20+), php-fpm.

set -euo pipefail

# ==== CONFIGURE AQUI ====
SSH_HOST="45.55.62.237"
SSH_USER="root"
APP_PATH="/var/www/app"
REPO_URL="https://github.com/brnagencia-tech/bot2.git"   # público
BRANCH="main"

PHP_FPM_SERVICE="php8.4-fpm"
WEB_USER="www-data"
WEB_GROUP="www-data"

# (opcional) caminho da chave se não for a padrão (~/.ssh/id_ed25519)
SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519}"

# ==== NADA ABAIXO PRECISA MEXER ====
echo "==> Testando SSH em ${SSH_USER}@${SSH_HOST} ..."
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new ${SSH_USER}@${SSH_HOST} "echo 'ssh ok'"

echo "==> Executando deploy no servidor..."
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new ${SSH_USER}@${SSH_HOST} bash <<'REMOTE'
set -euo pipefail

APP_PATH="/var/www/app"
REPO_URL="https://github.com/brnagencia-tech/bot2.git"
BRANCH="main"
PHP_FPM_SERVICE="php8.4-fpm"
WWW_USER="www-data"
WWW_GROUP="www-data"

echo "==> Atualizando pacotes essenciais..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y git rsync unzip curl ca-certificates

echo "==> Criando diretório do app (se necessário)..."
mkdir -p "$APP_PATH"

# Se já é git repo, faz reset para a origem. Se não, clona em /tmp e sincroniza.
if [ -d "$APP_PATH/.git" ]; then
  echo "==> Repo já existe. Dando fetch/reset em $APP_PATH ..."
  cd "$APP_PATH"
  git remote set-url origin "$REPO_URL" || true
  git fetch --depth=1 origin "$BRANCH"
  git reset --hard "origin/$BRANCH"
else
  echo "==> Clonando repo para /tmp e sincronizando para $APP_PATH ..."
  rm -rf /tmp/app-release
  git clone --depth=1 -b "$BRANCH" "$REPO_URL" /tmp/app-release
  SRC_DIR="/tmp/app-release"
  # Detecta se o Laravel está em app/laravel
  if [ ! -f "$SRC_DIR/artisan" ] && [ -f "$SRC_DIR/app/laravel/artisan" ]; then
    SRC_DIR="$SRC_DIR/app/laravel"
  fi
  rsync -a --delete \
    --exclude=".git/" \
    --exclude=".github/" \
    --exclude=".env" \
    --exclude="storage/" \
    --exclude="vendor/" \
    --exclude="node_modules/" \
    --exclude="public/storage" \
    "$SRC_DIR"/ "$APP_PATH"/
fi

echo "==> Permissões de pasta..."
chown -R "$WWW_USER:$WWW_GROUP" "$APP_PATH"
find "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" -type d -exec chmod 775 {} \; || true

echo "==> Garantindo composer instalado..."
if ! command -v composer >/dev/null 2>&1; then
  php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
  php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm composer-setup.php
fi

cd "$APP_PATH"

echo "==> Entrando em manutenção (down)..."
php artisan down || true

echo "==> Instala dependências PHP (prod, autoloader otimizado)..."
sudo -u "$WWW_USER" composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Link de storage..."
php artisan storage:link || true

echo "==> Frontend: cache npm e build do Vite..."
install -d -m 775 -o "$WWW_USER" -g "$WWW_GROUP" /var/www/.npm
rm -rf node_modules
sudo -u "$WWW_USER" env npm_config_cache=/var/www/.npm npm ci --no-audit --no-fund || \
sudo -u "$WWW_USER" env npm_config_cache=/var/www/.npm npm install --no-audit --no-fund

sudo -u "$WWW_USER" env npm_config_cache=/var/www/.npm npx vite build
test -f public/build/manifest.json && echo "✅ manifest OK" || (echo "❌ manifest faltando" && exit 1)

echo "==> Migrações (forçadas)..."
php artisan migrate --force

echo "==> Limpando e recarregando caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Reiniciando PHP-FPM..."
systemctl restart "$PHP_FPM_SERVICE"

echo "==> Saindo de manutenção (up)..."
php artisan up

echo "✅ Deploy finalizado com sucesso."
REMOTE

echo "🎉 Pronto! Acesse: https://bot.agenciabrn.com.br/"
