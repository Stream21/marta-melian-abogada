FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install -j$(nproc) \
    intl \
    opcache \
    pdo \
    pdo_pgsql \
    zip \
    && docker-php-ext-enable opcache pdo_pgsql pdo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 9000

CMD ["php-fpm"]
