# Используем официальный образ PHP на базе Alpine
FROM php:7.4-fpm-alpine

# Устанавливаем зависимости для PHP и необходимые инструменты для сборки расширений
RUN apk --no-cache add \
    freetype-dev \
    jpeg-dev \
    libpng-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    rabbitmq-c-dev \
    openssh-client \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl sockets \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && docker-php-ext-enable pdo_mysql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем права на запись для файловой системы
RUN chown -R www-data:www-data /var/www

# Открываем порт
EXPOSE 9000

# Запускаем PHP-FPM
CMD ["php-fpm"]
