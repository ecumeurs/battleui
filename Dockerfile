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

# Note: Composer install and npm build should be done before or handled via volumes/multistage if needed.
# For this MVP, we assume local builds or volume binds during dev-prod testing.
# But for a "standard" Dockerfile, we'll assume the code is already built or we can install composer here.

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# (Optional) Run composer install if vendor doesn't exist
# RUN composer install --no-dev --optimize-autoloader

EXPOSE 80

CMD ["apache2-foreground"]
