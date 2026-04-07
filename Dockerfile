FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql

# Configure Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# --- FIX PERMISSIONS ---
# Create the storage structure and give ownership to the web user
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- STARTUP ---
# Refresh autoloader, migrate, and start
CMD ["sh", "-c", "composer dump-autoload && php artisan migrate --force && apache2-foreground"]