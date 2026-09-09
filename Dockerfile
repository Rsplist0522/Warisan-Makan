FROM php:8.3-apache
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y libonig-dev libzip-dev unzip git curl
RUN docker-php-ext-install mbstring zip pdo pdo_mysql

RUN printf "upload_max_filesize=4M\npost_max_size=32M\nmax_file_uploads=20\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Enable OPcache for performance
RUN docker-php-ext-install opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini
RUN echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Install Node.js for building frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs

COPY . .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN npm install
RUN npm run build

RUN cp .env.example .env
RUN chown -R www-data:www-data /var/www/html

RUN php artisan route:cache
RUN php artisan view:cache

EXPOSE 80
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
CMD ["sh", "-c", "echo Checking-CA; ls -l /etc/secrets; if [ -f /etc/secrets/ca.pem ]; then echo CA-EXISTS; else echo CA-MISSING; fi; apache2-foreground"]
CMD ["sh", "-c", "echo Checking-CA; ls -l /etc/secrets; php -r \"var_dump(file_exists('/etc/secrets/ca.pem')); var_dump(is_readable('/etc/secrets/ca.pem')); var_dump(defined('Pdo\\\\Mysql::ATTR_SSL_CA'));\"; apache2-foreground"]