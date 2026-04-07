FROM php:8.2-apache

# 1. Install system dependencies for PHP and MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# 2. Enable Apache mod_rewrite for Lumen/Laravel routing
RUN a2enmod rewrite

# 3. Set Apache Document Root to the /public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# 4. Set the working directory
WORKDIR /var/www/html

# 5. Copy your project files into the container
COPY . /var/www/html

# 6. Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 7. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# 8. --- FIX PERMISSIONS (The "Fatal Error" Killer) ---
# This ensures the web server user (www-data) can write logs and cache
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. --- STARTUP COMMAND ---
# This refreshes the autoloader, runs your migrations on Aiven, and starts Apache
CMD ["sh", "-c", "composer dump-autoload && php artisan migrate --force && apache2-foreground"]