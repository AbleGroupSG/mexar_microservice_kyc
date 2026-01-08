# Build frontend assets via Node to avoid shipping Node in runtime image
FROM node:20-alpine AS frontend-builder

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci --no-audit --progress=false

COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN rm -rf public/build \
 && npm run build

# Base PHP stage that holds system dependencies and compiled extensions
FROM php:8.3-fpm-alpine AS php-base

RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
      git unzip \
      freetype-dev libjpeg-turbo-dev libpng-dev \
      icu-dev libzip-dev zlib-dev oniguruma-dev linux-headers \
      rabbitmq-c-dev openssl-dev \
      $PHPIZE_DEPS; \
    apk add --no-cache --virtual .run-deps \
      bash curl ca-certificates nghttp2-libs mariadb-client redis netcat-openbsd procps \
      freetype libjpeg-turbo libpng icu-libs libzip oniguruma rabbitmq-c openssl; \
    update-ca-certificates; \
    ln -s /usr/include/freetype2 /usr/include/freetype; \
    docker-php-ext-configure gd --with-freetype=/usr/include/freetype --with-jpeg=/usr/include; \
    docker-php-ext-install -j"$(nproc)" gd pdo_mysql mbstring exif pcntl bcmath zip opcache intl sockets; \
    pecl install redis-5.3.7 amqp-1.11.0; \
    docker-php-ext-enable redis amqp; \
    apk del --no-network .build-deps $PHPIZE_DEPS; \
    rm -rf /var/cache/apk/* /tmp/*

WORKDIR /var/www

# Composer (copied from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Composer dependency stage keeps vendor caching isolated
FROM php-base AS php-deps

COPY composer.json composer.lock ./
RUN set -eux; \
    composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts; \
    rm -rf /root/.composer/cache

# Final app stage with source code, vendors, and built assets
FROM php-base AS app

# Copy application source
COPY --chown=www-data:www-data . .

# Copy Composer vendor directory from deps stage
COPY --from=php-deps --chown=www-data:www-data /var/www/vendor vendor

# Copy compiled frontend assets from the Node builder stage
COPY --from=frontend-builder --chown=www-data:www-data /var/www/html/public/build public/build

# Now that source is present, finish autoload/scripts
RUN composer dump-autoload -o \
 && php artisan package:discover --ansi || true \
 && rm -f /usr/bin/composer

# Ensure runtime permissions (storage, cache, vendor)
RUN chown -R www-data:www-data storage bootstrap/cache vendor || true \
 && chmod -R ug+rwX storage bootstrap/cache || true

# Entrypoints and scheduler script
COPY docker/entry-point.sh /usr/local/bin/entry-point.sh
COPY docker/run-scheduler.sh /usr/local/bin/run-scheduler.sh
RUN chmod +x /usr/local/bin/entry-point.sh /usr/local/bin/run-scheduler.sh

EXPOSE 9000
USER www-data
ENTRYPOINT ["/usr/local/bin/entry-point.sh"]
