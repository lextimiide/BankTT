#!/bin/bash

# =============================================================================
# Script de correction des permissions Laravel
# Auteur: Kilo Code - Ingénieur Logiciel
# Description: Corrige automatiquement les permissions dans un projet Laravel
# Compatible: Ubuntu/Debian et environnements Docker
# =============================================================================

# Configuration des couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction d'affichage des messages colorés
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_header() {
    echo -e "${BLUE}================================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================================================================${NC}"
}

# Fonction de vérification si on est dans un projet Laravel
check_laravel_project() {
    if [ ! -f "artisan" ]; then
        print_error "Ce n'est pas un projet Laravel (fichier artisan manquant)"
        exit 1
    fi
    print_success "Projet Laravel détecté"
}

# Fonction de vérification des permissions sudo
check_sudo() {
    if ! sudo -n true 2>/dev/null; then
        print_warning "Certaines opérations nécessitent sudo. Vous devrez saisir votre mot de passe."
    fi
}

# Fonction de correction des permissions de base
fix_basic_permissions() {
    print_header "CORRECTION DES PERMISSIONS DE BASE"

    # Attribution de la propriété à l'utilisateur courant
    print_info "Attribution de la propriété du projet à $USER:$USER..."
    sudo chown -R "$USER:$USER" . 2>/dev/null || {
        print_warning "Impossible de changer la propriété (peut nécessiter sudo)"
        chown -R "$USER:$USER" . 2>/dev/null || print_warning "Propriété non modifiée"
    }

    # Permissions des dossiers critiques
    print_info "Configuration des permissions pour storage et bootstrap/cache..."
    chmod -R 775 storage 2>/dev/null && print_success "Permissions storage configurées" || print_warning "Erreur permissions storage"
    chmod -R 775 bootstrap/cache 2>/dev/null && print_success "Permissions bootstrap/cache configurées" || print_warning "Erreur permissions bootstrap/cache"

    # Permissions du fichier artisan
    chmod +x artisan 2>/dev/null && print_success "Permissions artisan configurées" || print_warning "Erreur permissions artisan"
}

# Fonction de nettoyage des caches Laravel
clean_laravel_cache() {
    print_header "NETTOYAGE DES CACHES LARAVEL"

    if ! command -v php &> /dev/null; then
        print_warning "PHP n'est pas installé ou n'est pas dans le PATH"
        return 1
    fi

    print_info "Nettoyage du cache d'application..."
    php artisan cache:clear 2>/dev/null && print_success "Cache d'application nettoyé" || print_warning "Erreur nettoyage cache"

    print_info "Nettoyage du cache de configuration..."
    php artisan config:clear 2>/dev/null && print_success "Cache de configuration nettoyé" || print_warning "Erreur nettoyage config"

    print_info "Nettoyage du cache des routes..."
    php artisan route:clear 2>/dev/null && print_success "Cache des routes nettoyé" || print_warning "Erreur nettoyage routes"

    print_info "Nettoyage du cache des vues..."
    php artisan view:clear 2>/dev/null && print_success "Cache des vues nettoyé" || print_warning "Erreur nettoyage vues"

    print_info "Nettoyage du cache des événements..."
    php artisan event:clear 2>/dev/null && print_success "Cache des événements nettoyé" || print_warning "Erreur nettoyage événements"
}

# Fonction de correction des permissions Docker
fix_docker_permissions() {
    print_header "CORRECTION DES PERMISSIONS DOCKER"

    if ! command -v docker &> /dev/null; then
        print_warning "Docker n'est pas installé"
        return 1
    fi

    if ! docker compose ps | grep -q "app"; then
        print_warning "Conteneur 'app' non trouvé. Démarrez d'abord avec 'docker compose up'"
        return 1
    fi

    print_info "Correction des permissions dans le conteneur Docker..."
    docker compose exec -T app chown -R www-data:www-data storage 2>/dev/null && print_success "Permissions Docker storage configurées" || print_warning "Erreur permissions Docker storage"
    docker compose exec -T app chown -R www-data:www-data bootstrap/cache 2>/dev/null && print_success "Permissions Docker bootstrap/cache configurées" || print_warning "Erreur permissions Docker bootstrap/cache"
}

# Fonction d'affichage du résumé final
show_summary() {
    print_header "RÉSUMÉ DES PERMISSIONS"

    echo "Permissions actuelles des dossiers critiques :"
    echo ""
    ls -ld storage bootstrap/cache 2>/dev/null || print_warning "Impossible de lire les permissions"

    echo ""
    print_success "Script terminé avec succès !"
    echo ""
    print_info "Pour rendre ce script global :"
    echo "  1. sudo cp fix-laravel-permissions.sh /usr/local/bin/fix-laravel"
    echo "  2. sudo chmod +x /usr/local/bin/fix-laravel"
    echo "  3. Utilisation : fix-laravel [--docker]"
}

# Fonction principale
main() {
    # Vérification des arguments
    DOCKER_MODE=false
    if [ "$1" = "--docker" ]; then
        DOCKER_MODE=true
    fi

    print_header "FIX LARAVEL PERMISSIONS - KILO CODE"
    echo "Script de correction automatique des permissions Laravel"
    echo ""

    # Vérifications préalables
    check_laravel_project
    check_sudo

    # Exécution des corrections
    fix_basic_permissions
    clean_laravel_cache

    if [ "$DOCKER_MODE" = true ]; then
        fix_docker_permissions
    fi

    # Affichage du résumé
    show_summary
}

# Gestion des signaux d'interruption
trap 'echo -e "\n${RED}Script interrompu par l utilisateur${NC}"; exit 1' INT TERM

# Exécution du script
main "$@"