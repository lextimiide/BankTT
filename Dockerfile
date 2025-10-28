# Use the official PHP image with FPM for production
FROM php:8.3-fpm-alpine AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    nginx \
    supervisor

# Clear cache
RUN apk cache clean && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create .env file from .env.example if it doesn't exist
RUN cp .env.example .env || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy nginx configuration for production
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy supervisor configuration for production
COPY docker/supervisord.conf /etc/supervisord.conf

# Create log directories
RUN mkdir -p /var/log/supervisor /var/log/nginx /var/log/php-fpm

# Expose port 80
EXPOSE 80

# Run supervisor for production
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
