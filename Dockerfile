FROM php:8.2-apache
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y libonig-dev libzip-dev unzip git
RUN docker-php-ext-install mbstring zip pdo pdo_mysql
COPY . .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
RUN cp .env.example .env
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf