# Use the official PHP Apache image
FROM php:8.2-apache

# Install MySQL extensions for Lumen/Eloquent
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module (required for Lumen routing)
RUN a2enmod rewrite

# Change the Apache Document Root to the /public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Copy your project files from C:\src\dds_sia\site1 into the container
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Run composer install to fetch dependencies (Guzzle, etc.)
RUN composer install --no-dev --optimize-autoloader

# Set permissions for the storage folder so Lumen can write logs
RUN chown -R www-data:www-data /var/www/html/storage