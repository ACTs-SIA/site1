FROM php:8.2-apache

# 1. Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql

# 2. Configure Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . /var/www/html

# 3. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# 4. --- THE PERMANENT PERMISSION FIX ---
# We create the folder, create a blank log file, and give full ownership to www-data
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache \
    && touch /var/www/html/storage/logs/lumen-$(date +%Y-%m-%d).log \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. --- STARTUP ---
# We use 'chown' again right before starting to ensure no files were missed
CMD ["sh", "-c", "chown -R www-data:www-data /var/www/html/storage && php artisan migrate --force && apache2-foreground"]