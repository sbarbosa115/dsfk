ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-apache

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql intl zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Run Apache as the host user so files created in the bind mount stay editable.
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data \
    && mkdir -p /var/www/.composer && chown -R www-data:www-data /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/backend
