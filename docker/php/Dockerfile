# Используем базовый образ PHP 8.3 с FPM
FROM php:8.3-fpm

# Устанавливаем зависимости и необходимые расширения для PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# Установите расширение Redis
RUN pecl install redis && docker-php-ext-enable redis

