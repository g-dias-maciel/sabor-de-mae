# ==============================================
# Estágio 1: Dependências PHP
# ==============================================
FROM composer:latest AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    2>/dev/null || true

# ==============================================
# Estágio 2: Assets Frontend
# ==============================================
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
RUN npm ci 2>/dev/null || npm install 2>/dev/null || true

COPY resources/ resources/
RUN npm run build

# ==============================================
# Estágio 3: Imagem Final
# ==============================================
FROM php:8.2-fpm-alpine

# Instala extensões PHP + Nginx + Supervisor
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    && docker-php-ext-install -j$(nproc) \
        zip \
        pdo_mysql \
        mbstring \
        gd \
    && rm -rf /var/cache/apk/*

# PHP config: produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI_DIR/php.ini"

# PHP-FPM: mantém variáveis de ambiente nos workers (necessário para Coolify)
RUN sed -i 's/^;clear_env = yes/clear_env = no/' "$PHP_INI_DIR/../php-fpm.d/www.conf"

# Nginx config
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor config
RUN mkdir -p /etc/supervisor.d/
COPY docker/supervisord.conf /etc/supervisor.d/app.ini

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN mkdir -p /var/www/html /run/nginx

WORKDIR /var/www/html

# Copia o código da aplicação
COPY . .

# Copia vendor do estágio 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copia assets buildados do estágio 2
COPY --from=assets /app/public/build /var/www/html/public/build

# Permissões de produção
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
