#!/usr/bin/env bash

# BotWhatsApp – Fix-all para servidor (Ubuntu/Nginx/PHP-FPM)
# Executa correções para 404/500 e QR do WhatsApp Web.
#
# Uso recomendado (como root):
#   bash scripts/fix_server.sh
#
# Variáveis ajustáveis (também podem ser passadas no ambiente):
#   APP=/var/www/app
#   WEB_USER=www-data
#   WEB_GROUP=www-data
#   WAWEB_PORT=3001
#   WAWEB_SHARED_SECRET="sua-chave"
#   LARAVEL_BASE_URL="http://localhost"  # ou https://seu-dominio

set -Eeuo pipefail

log() { echo -e "[fix] $*"; }
abort() { echo "[fix] ERROR: $*" >&2; exit 1; }

[ "$(id -u)" = "0" ] || abort "Execute como root (sudo)."

APP="${APP:-/var/www/app}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
WAWEB_PORT="${WAWEB_PORT:-3001}"
WAWEB_SHARED_SECRET="${WAWEB_SHARED_SECRET:-}"
LARAVEL_BASE_URL="${LARAVEL_BASE_URL:-http://localhost}"

PHPFPM_SERVICE="${PHP_FPM_SERVICE:-}"

# Detecta serviço do PHP-FPM se não informado
if [ -z "$PHPFPM_SERVICE" ]; then
  for svc in php8.4-fpm php8.3-fpm php8.2-fpm php8.1-fpm php-fpm; do
    if systemctl list-units --type=service | grep -q "^$svc"; then PHPFPM_SERVICE="$svc"; break; fi
  done
fi
[ -n "$PHPFPM_SERVICE" ] || abort "Não foi possível detectar o serviço PHP-FPM. Defina PHP_FPM_SERVICE=php8.x-fpm."

# Detecta socket do PHP-FPM para Nginx
guess_php_sock() {
  for sock in /run/php/php-fpm.sock /run/php/php8.4-fpm.sock /run/php/php8.3-fpm.sock /run/php/php8.2-fpm.sock /run/php/php8.1-fpm.sock; do
    [ -S "$sock" ] && { echo "$sock"; return; }
  done
  echo "/run/php/php-fpm.sock"
}
PHP_SOCK="$(guess_php_sock)"

log "APP=$APP | PHPFPM=$PHPFPM_SERVICE | PHP_SOCK=$PHP_SOCK | WAWEB_PORT=$WAWEB_PORT"

# 1) Remover arquivo PHP inválido (se existir)
if [ -f "$APP/_admin_dash_hotfix.php" ]; then
  log "Removendo $APP/_admin_dash_hotfix.php (arquivo inválido)"
  rm -f "$APP/_admin_dash_hotfix.php"
fi

# 2) Nginx: vhost apontando para public com rewrites e socket correto
log "Aplicando vhost Nginx (botapp)"
cat >/etc/nginx/sites-available/botapp <<NGINX
server {
    listen 80;
    server_name _;
    root $APP/public;
    index index.php index.html;

    access_log /var/log/nginx/botapp.access.log;
    error_log  /var/log/nginx/botapp.error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_SOCK;
    }

    location ~ /\.ht { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/botapp /etc/nginx/sites-enabled/botapp
rm -f /etc/nginx/sites-enabled/default || true
nginx -t && systemctl reload nginx

# 3) Permissões Laravel + caches
log "Ajustando permissões do Laravel"
install -d -m 775 -o "$WEB_USER" -g "$WEB_GROUP" "$APP/storage" "$APP/bootstrap/cache"
chown -R "$WEB_USER":"$WEB_GROUP" "$APP/storage" "$APP/bootstrap/cache"
chmod -R ug+rwx "$APP/storage" "$APP/bootstrap/cache"

log "Limpando caches do Laravel"
(cd "$APP" && php artisan optimize:clear) || true

log "Gerando caches (com tolerância a falhas)"
(cd "$APP" && php artisan config:cache) || (cd "$APP" && php artisan optimize:clear || true)
(cd "$APP" && php artisan route:cache)  || (cd "$APP" && php artisan optimize:clear || true)
(cd "$APP" && php artisan view:cache)   || true

log "Reiniciando $PHPFPM_SERVICE"
systemctl restart "$PHPFPM_SERVICE"

# 4) WhatsApp Web – permissões + npm + systemd
log "Preparando ambiente do WhatsApp Web"
install -d -m 775 -o "$WEB_USER" -g "$WEB_GROUP" /var/www/.npm
install -d -m 775 -o "$WEB_USER" -g "$WEB_GROUP" "$APP/whatsapp_service"
chown -R "$WEB_USER":"$WEB_GROUP" "$APP/whatsapp_service"

if command -v npm >/dev/null 2>&1; then
  log "Instalando dependências do WhatsApp Web (npm)"
  (cd "$APP/whatsapp_service" && sudo -u "$WEB_USER" env HOME=/var/www npm install --no-audit --no-fund) || log "npm install falhou (verifique Node 18+)"
else
  log "npm não encontrado – pulando instalação (instale Node 18+)"
fi

log "(Re)criando serviço systemd bot-whatsapp-web"
cat >/etc/systemd/system/bot-whatsapp-web.service <<EOF
[Unit]
Description=BotWhatsApp Web (whatsapp-web.js)
After=network.target

[Service]
WorkingDirectory=$APP/whatsapp_service
ExecStart=/usr/bin/env node server.js
Environment=PORT=$WAWEB_PORT
Environment=WAWEB_SHARED_SECRET=$WAWEB_SHARED_SECRET
Environment=LARAVEL_BASE_URL=$LARAVEL_BASE_URL
Environment=TENANT_ID=tenant-1
Restart=always
User=$WEB_USER
Group=$WEB_GROUP

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now bot-whatsapp-web
systemctl restart bot-whatsapp-web || true

# 5) Health-checks
log "Health /up (Laravel)"
if command -v curl >/dev/null 2>&1; then
  curl -fsS -m 5 http://localhost/up || log "Health /up falhou (verifique logs Laravel)"
else
  log "curl não instalado – pulando health /up"
fi

log "WhatsApp Web /status"
if command -v curl >/dev/null 2>&1; then
  curl -fsS -m 5 http://127.0.0.1:"$WAWEB_PORT"/status || log "/status falhou – verifique 'journalctl -u bot-whatsapp-web'"
fi

log "Pronto. Teste no navegador: /login e /whatsapp (use 'Resetar sessão' para gerar QR)."

