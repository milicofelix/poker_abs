FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    nodejs \
    npm \
    mysql-client \
    $PHPIZE_DEPS \
    && docker-php-ext-install pcntl\
        pdo \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        bcmath \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php-fpm"]