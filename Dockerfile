FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    tesseract-ocr \
    tesseract-ocr-spa \
    tesseract-ocr-eng \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    opcache \
    pdo \
    pdo_pgsql \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY docker/php/docker-entrypoint.sh /usr/local/bin/bufete-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/bufete-entrypoint.sh \
    && chmod +x /usr/local/bin/bufete-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/bufete-entrypoint.sh"]
