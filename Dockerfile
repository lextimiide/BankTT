# # Use the official PHP image for production
# FROM php:8.3-cli-alpine AS base

# # Set working directory
# WORKDIR /var/www/html

# # Install system dependencies
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
#     postgresql-dev

# # Clear cache
# RUN apk cache clean && rm -rf /var/cache/apk/*

# # Configure and install GD extension with all dependencies
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
#     && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# # Get latest Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # Copy existing application directory contents
# COPY . /var/www/html

# # Copy existing application directory permissions
# COPY --chown=www-data:www-data . /var/www/html

# # Install PHP dependencies
# RUN composer install --no-dev --optimize-autoloader

# # Create .env file from .env.example if it doesn't exist
# RUN cp .env.example .env || true

# # Generate application key
# RUN php artisan key:generate

# # Run database migrations and seeders
# RUN php artisan migrate --force
# RUN php artisan db:seed --force

# # Install Passport clients for API authentication
# RUN php artisan passport:client --personal --name="API Personal Access Client" --no-interaction
# RUN php artisan passport:client --password --name="API Password Grant Client" --no-interaction

# # Generate Swagger documentation
# RUN php artisan l5-swagger:generate

# # Set permissions
# RUN chown -R www-data:www-data /var/www/html \
#     && chmod -R 755 /var/www/html/storage \
#     && chmod -R 755 /var/www/html/bootstrap/cache

# # Expose port 8000
# EXPOSE 8000

# # Run Laravel development server
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# -----------------------------------------
# ✅ Dockerfile Laravel 10+ (Render Ready)
# Fonctionne avec ou sans Faker
# -----------------------------------------

# -----------------------------------------
# ✅ Dockerfile Laravel 10+ (Render Ready)
# Compatible Local & Prod - Avec ou sans Faker
# -----------------------------------------

FROM php:8.3-cli-alpine AS base

WORKDIR /var/www/html

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

RUN rm -rf /var/cache/apk/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data . /var/www/html

# Installer les dépendances PHP sans les dev
RUN composer install --no-dev --optimize-autoloader || true

# Vérifier la présence de Faker
RUN if ! composer show fakerphp/faker > /dev/null 2>&1; then \
      echo "ℹ️  Faker non trouvé (normal en prod, seeders doivent le gérer)."; \
    else \
      echo "✅ Faker détecté, seeders prêts pour le dev."; \
    fi

RUN cp .env.example .env || true

# Préparer l’environnement
RUN php artisan config:clear && php artisan cache:clear

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Nettoyer le cache Composer
RUN rm -rf /root/.composer/cache

EXPOSE 8000

# Commande de démarrage
CMD php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan passport:install --force && \
    php artisan serve --host=0.0.0.0 --port=8000


