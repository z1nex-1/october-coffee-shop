FROM node:20-alpine AS assets
WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci
COPY gulpfile.js ./
COPY themes/coffee/assets/src themes/coffee/assets/src
RUN NODE_ENV=production npx gulp build

FROM php:7.4-apache
# bullseye переехал в архив, с deb.debian.org пакеты отдаются через раз
RUN printf 'deb http://archive.debian.org/debian bullseye main\ndeb http://archive.debian.org/debian-security bullseye-security main\n' > /etc/apt/sources.list \
 && rm -f /etc/apt/sources.list.d/* \
 && apt-get update \
 && apt-get install -y --no-install-recommends unzip libzip-dev libpng-dev libjpeg-dev \
 && docker-php-ext-configure gd --with-jpeg \
 && docker-php-ext-install zip gd \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/shop
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
COPY --from=assets /src/themes/coffee/assets/dist themes/coffee/assets/dist
RUN cp .env.example .env \
 && mkdir -p storage/app/media storage/app/uploads storage/cms/cache storage/cms/combiner storage/cms/twig \
    storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/temp/public \
 && composer dump-autoload --optimize --no-dev \
 && php artisan package:discover \
 && chown -R www-data:www-data storage

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/shop-entrypoint
RUN chmod +x /usr/local/bin/shop-entrypoint

ENV DB_DATABASE=/data/database.sqlite
VOLUME /data
EXPOSE 80
ENTRYPOINT ["shop-entrypoint"]
