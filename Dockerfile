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
    ca-certificates \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js (NodeSource — Debian's own repo only ships Node 20)
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (also runs package:discover, needed before the
# Vite build since resources/js/app.js resolves Ziggy from vendor/)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# .env is excluded from the build context (see .dockerignore), so the Vite
# build needs these passed explicitly — same VITE_REVERB_* values consumed
# by resources/js/bootstrap.js's Echo/Pusher setup. Without them, Pusher
# throws at runtime and the whole Vue app fails to mount.
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

# Build frontend assets so public/build/manifest.json exists in the image —
# the app no longer relies on a bind-mounted, locally-built public/build
# like in the dev container.
RUN npm ci && npm run build

# Ensure storage and bootstrap/cache are writable
RUN chown -R www-data:www-data storage bootstrap/cache

# Ensure curl is available for Docker healthchecks
RUN which curl || apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

EXPOSE 80

CMD ["apache2-foreground"]
