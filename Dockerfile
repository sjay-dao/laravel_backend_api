FROM php:8.3-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libonig-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libgmp-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_mysql mbstring bcmath intl gd zip opcache gmp \
    && a2enmod rewrite \
    && ln -s /usr/local/bin/php /usr/local/bin/php8 \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts \
    && php8 artisan package:discover --no-interaction \
    && composer check-platform-reqs --no-dev \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/apache-prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/mica.ini
RUN chmod +x deploy/start.sh
EXPOSE 10000
CMD ["sh", "/var/www/html/deploy/start.sh"]
