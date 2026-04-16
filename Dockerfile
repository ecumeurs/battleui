# Base image
FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    curl \
    libpq-dev \
    postgresql-client \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    gd \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    intl \
    pcntl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Update Apache configuration to point to public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy application source
COPY . .

# Ensure storage and bootstrap/cache are writable
RUN chown -R www-data:www-data storage bootstrap/cache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Ensure curl is available for Docker healthchecks
RUN which curl || apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

EXPOSE 80

CMD ["apache2-foreground"]
