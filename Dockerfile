FROM php:8.3-fpm-alpine AS base
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    mariadb-client \
    autoconf \
    g++ \
    make \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    opcache \
    pcntl \
    dom \
    xml \
    xmlwriter \
    && docker-php-ext-enable opcache

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a non-root user for the application
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

WORKDIR /var/www/html

RUN mkdir -p var/cache var/log && \
    chown -R www:www /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/

# Development stage
FROM base AS development

# Install Xdebug
RUN apk add --no-cache $PHPIZE_DEPS linux-headers && \
    pecl channel-update pecl.php.net && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY --chown=www:www composer.json composer.lock ./
USER www
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY --chown=www:www src/ ./src/
COPY --chown=www:www bin/ ./bin/
COPY --chown=www:www config/ ./config/
COPY --chown=www:www public/ ./public/
COPY --chown=www:www migrations/ ./migrations/
COPY --chown=www:www symfony.lock ./

EXPOSE 9000

CMD ["php-fpm"]

# Production dependencies stage
FROM base AS prod-deps

# Copy dependency files
COPY composer.json composer.lock ./


RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    && rm -rf /root/.composer


# Production stage
FROM base AS production

COPY docker/php/php-production.ini /usr/local/etc/php/
COPY --from=prod-deps --chown=www:www /var/www/html/vendor ./vendor
COPY --chown=www:www src/ ./src/
COPY --chown=www:www bin/ ./bin/
COPY --chown=www:www config/ ./config/
COPY --chown=www:www public/ ./public/
COPY --chown=www:www migrations/ ./migrations/
COPY --chown=www:www symfony.lock ./

USER www

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
RUN php bin/console cache:clear --env=prod --no-debug && \
    php bin/console cache:warmup --env=prod --no-debug

EXPOSE 9000

# Health check to ensure the FPM process is running
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

CMD ["php-fpm"]
