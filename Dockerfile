FROM php:8.3-apache
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y libonig-dev libzip-dev unzip git
RUN docker-php-ext-install mbstring zip pdo pdo_mysql

# Enable OPcache for performance
RUN docker-php-ext-install opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini
RUN echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

COPY . .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
RUN cp .env.example .env
RUN chown -R www-data:www-data /var/www/html

# Cache Laravel config, routes, and views for production
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

EXPOSE 80
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf