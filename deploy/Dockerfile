FROM php:8.3-apache

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql zip exif pcntl bcmath gd

COPY ./src .

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

RUN docker-php-ext-install pdo pdo_mysql mysqli

EXPOSE 8080
COPY ./deploy/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./deploy/php.ini /usr/local/etc/php/
COPY ./deploy/apache/*.conf /etc/apache2/sites-enabled/
COPY ./deploy/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chown -R www-data:www-data /var/www/;
RUN find /var/www/ -type f -exec chmod 644 {} \;
RUN find /var/www/ -type d -exec chmod 755 {} \;

#RUN mv .env.example .env
RUN php artisan key:generate
RUN php artisan vendor:publish --tag=log-viewer-assets --force
RUN chgrp -R www-data storage bootstrap/cache
RUN chmod -R ug+rwx storage bootstrap/cache
RUN echo "Listen 8080" >> /etc/apache2/ports.conf
RUN a2enmod rewrite
