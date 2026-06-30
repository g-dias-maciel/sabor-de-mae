#!/bin/sh
set -e

# Aguarda o banco ficar disponível
if [ -n "$DB_HOST" ]; then
    echo ">>> Aguardando banco de dados em $DB_HOST:${DB_PORT:-3306}..."
    timeout=60
    while ! php -r "
        \$dsn = sprintf('mysql:host=%s;port=%d', getenv('DB_HOST'), (int)(getenv('DB_PORT') ?: 3306));
        new PDO(\$dsn, getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '');
        echo 'ok';
    " 2>/dev/null | grep -q "ok"; do
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo ">>> Timeout ao aguardar banco de dados."
            break
        fi
        sleep 1
    done
    echo ">>> Banco de dados disponível!"
fi

# Gera chave da aplicação se necessário (Coolify não usa .env — injeta env vars diretamente)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:..." ] || [ "$APP_KEY" = "localhost" ]; then
    echo ">>> Gerando APP_KEY..."
    APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    export APP_KEY
fi

# Executa migrations
php artisan migrate --force --quiet 2>/dev/null || true

# Otimiza cache (produção)
php artisan config:cache --quiet 2>/dev/null || true
php artisan route:cache --quiet 2>/dev/null || true
php artisan view:cache --quiet 2>/dev/null || true

# Ajusta permissões
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null

echo ">>> Iniciando serviços..."

# Executa supervisor (gerencia PHP-FPM + Nginx)
exec /usr/bin/supervisord -c /etc/supervisor.d/app.ini -n
