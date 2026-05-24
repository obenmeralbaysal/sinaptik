# syntax=docker/dockerfile:1.6

############################################
# Stage 1: composer deps (no dev)
############################################
FROM composer:2 AS vendor

WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

############################################
# Stage 2: runtime (php-fpm + nginx + supervisor)
############################################
FROM php:8.2-fpm-alpine AS runtime

RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        git \
        icu-dev \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

COPY backend/ /var/www/html/
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/backend/nginx.conf /etc/nginx/nginx.conf
COPY docker/backend/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
