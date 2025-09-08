FROM php:8.3-apache

# Installer les dépendances système
RUN apt-get update \
    && apt-get install -y \
        build-essential curl zlib1g-dev g++ git libicu-dev zip libzip-dev \
        libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev libcurl4-openssl-dev \
        pkg-config libssl-dev ca-certificates unzip \
    && docker-php-ext-install intl opcache pdo pdo_mysql zip gd exif \
    && pecl install apcu mongodb xdebug \
    && docker-php-ext-enable apcu mongodb xdebug

# Activer Apache modules
RUN a2enmod rewrite ssl

# Installer Node.js 18 LTS
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Définir le répertoire de travail
WORKDIR /var/www

# Copier configuration PHP custom si nécessaire
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Copier configuration Xdebug si nécessaire
COPY conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini