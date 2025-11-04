
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


