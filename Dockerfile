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

# Utilise l'image officielle PHP 8.3 (production ready)
FROM php:8.3-cli-alpine AS base

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer les dépendances système nécessaires
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

# Nettoyer le cache APK
RUN rm -rf /var/cache/apk/*

# Configurer et installer les extensions PHP requises
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le code source de l'application
COPY --chown=www-data:www-data . /var/www/html

# -----------------------------------------
# 🧩 Installation des dépendances
# -----------------------------------------

# Installer les dépendances PHP sans les dev (prod)
# 👉 Faker sera ignoré si ton seeder le gère déjà
RUN composer install --no-dev --optimize-autoloader || true

# Vérification optionnelle : si Faker manque, avertir mais ne pas échouer
RUN if ! composer show fakerphp/faker > /dev/null 2>&1; then \
      echo "⚠️  Faker non installé (production mode). Les seeders doivent gérer ce cas."; \
    fi

# -----------------------------------------
# ⚙️ Configuration Laravel
# -----------------------------------------

# Créer le fichier .env s'il n'existe pas
RUN cp .env.example .env || true

# Générer la clé d’application
RUN php artisan key:generate --force

# Exécuter les migrations et les seeders
RUN php artisan migrate --force || echo "⚠️  Migration échouée ou déjà exécutée"
RUN php artisan db:seed --force || echo "⚠️  Seeding partiel ou Faker non dispo, vérifie tes seeders"

# Installer les clients Passport
RUN php artisan passport:client --personal --name="API Personal Access Client" --no-interaction || true
RUN php artisan passport:client --password --name="API Password Grant Client" --no-interaction || true

# Générer la documentation Swagger
RUN php artisan l5-swagger:generate || echo "ℹ️  Swagger non configuré, étape ignorée"

# -----------------------------------------
# 🔐 Permissions et exécution
# -----------------------------------------

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Exposer le port 8000
EXPOSE 8000

# Exécuter les migrations et seeders au démarrage, puis démarrer le serveur
CMD php artisan migrate --force && php artisan db:seed --force && php artisan passport:install --force && php artisan serve --host=0.0.0.0 --port=8000

