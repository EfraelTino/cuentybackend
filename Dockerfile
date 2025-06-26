FROM php:8.2-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia archivos de tu proyecto
WORKDIR /var/www/html
COPY . .

# Instala dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Permisos y Apache config (opcional)
RUN chown -R www-data:www-data /var/www/html
