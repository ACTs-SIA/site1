# Use the official PHP Apache image
FROM php:8.2-apache

# Install system dependencies and MySQL extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module for Lumen routing
RUN a2enmod rewrite

# Change the Apache Document Root to the /public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Set the working directory
WORKDIR /var/www/html

# Copy all project files into the container
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install project dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for storage and bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage

# --- THE FIX FOR RENDER FREE TIER ---
# This command uses the absolute path to PHP and Artisan to ensure migrations run
# then it starts the web server (apache2-foreground)
CMD ["sh", "-c", "php /var/www/html/artisan migrate --force && apache2-foreground"]