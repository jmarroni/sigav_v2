# SIGAV v2 — imagen de desarrollo
# PHP 8.2 + Apache: Laravel 11 / Passport 12 y el lado legacy (mysqli)
FROM php:8.2-apache

# Dependencias del sistema para las extensiones PHP que usa el stack
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libonig-dev \
        libsqlite3-dev \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP:
#  - pdo_mysql / mysqli : Laravel (Eloquent) y legacy (mysqli_connect)
#  - gd                 : intervention/image y manipulación de imágenes en conection.php
#  - zip                : composer / paquetes
#  - bcmath             : cálculos de facturación
#  - intl               : formato/localización
#  - soap               : WebServices AFIP (WSAA/WSFE)
#  - mbstring           : Laravel core
#  - pdo_sqlite         : tests (SQLite en memoria via phpunit)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        bcmath \
        intl \
        soap \
        mbstring \
        pdo_sqlite

# Apache: docroot en public/ + mod_rewrite (necesario para Laravel y rutas legacy)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

WORKDIR /var/www/html
