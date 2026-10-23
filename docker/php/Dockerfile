FROM php:8.5-fpm

# Paquets système et extensions PHP nécessaires à Laravel
RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install pdo_mysql zip bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

# Composer (gestionnaire de dépendances PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

# Aligne l'utilisateur du conteneur sur le tien pour éviter les problèmes de droits sur les fichiers
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g www-data www-data

WORKDIR /var/www/html
USER www-data
