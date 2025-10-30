# Use the official PHP image for production
FROM php:8.3-cli-alpine AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev

# Clear cache
RUN apk cache clean && rm -rf /var/cache/apk/*

# Configure and install GD extension with all dependencies
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

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

# Generate application key
RUN php artisan key:generate

# Run database migrations
RUN php artisan migrate --force

# Run database seeders
RUN php artisan db:seed --force

# Generate Swagger documentation
RUN php artisan l5-swagger:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port 8000
EXPOSE 8000

# Run Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]






# # -------------------------------------------------------
# # Stage 1 : Base PHP image
# # -------------------------------------------------------
# # ----------------------------------------
# # Stage 1: Build
# # ----------------------------------------
# FROM php:8.3-cli-alpine AS build

# WORKDIR /var/www/html

# # Installer les dépendances système
# RUN apk add --no-cache \
#     git \
#     curl \
#     libpng-dev \
#     libjpeg-turbo-dev \
#     libwebp-dev \
#     freetype-dev \
#     oniguruma-dev \
#     libxml2-dev \
#     zip \
#     unzip \
#     postgresql-dev \
#     bash \
#     nodejs \
#     npm \
#     yarn

# # Installer les extensions PHP
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
#     && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# # Installer Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # Copier le code
# COPY . /var/www/html

# # Installer toutes les dépendances Composer (dev + prod)
# RUN composer install --optimize-autoloader

# # Installer les dépendances front (npm/yarn)
# RUN npm install \
#     && npm run build

# # ----------------------------------------
# # Stage 2: Production
# # ----------------------------------------
# FROM php:8.3-cli-alpine AS production

# WORKDIR /var/www/html

# # Installer seulement les dépendances système nécessaires
# RUN apk add --no-cache \
#     libpng-dev \
#     libjpeg-turbo-dev \
#     libwebp-dev \
#     freetype-dev \
#     oniguruma-dev \
#     libxml2-dev \
#     zip \
#     unzip \
#     postgresql-dev \
#     bash

# # Installer les extensions PHP
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
#     && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# # Copier Composer depuis build (pour autoload)
# COPY --from=build /usr/bin/composer /usr/bin/composer

# # Copier le code + vendor + assets compilés
# COPY --from=build /var/www/html /var/www/html

# # Créer .env si nécessaire
# RUN cp .env.example .env || true

# # Définir permissions
# RUN chown -R www-data:www-data /var/www/html \
#     && chmod -R 755 /var/www/html/storage \
#     && chmod -R 755 /var/www/html/bootstrap/cache

# # Exposer le port
# EXPOSE 8000

# # Lancer le serveur Laravel
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

