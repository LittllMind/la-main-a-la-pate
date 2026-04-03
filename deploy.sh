#!/bin/bash

###############################################################
# Script de déploiement automatique - Stock Vinyles
# Hostinger SSH - Branch: production
###############################################################

set -e # Arrêt immédiat en cas d'erreur

echo "🚀 Début du déploiement..."

# Variables (à adapter selon votre config Hostinger)
PROJECT_DIR="/home/u574934258/domains/la-main-a-la-pate.online/public_html"
GIT_BRANCH="production"

# Étape 1 : Mise en maintenance
echo "🔒 Activation du mode maintenance..."
php artisan down --render="errors::503" --retry=60

# Étape 2 : Pull des dernières modifications
echo "📥 Récupération du code depuis GitHub..."
git fetch origin $GIT_BRANCH
git reset --hard origin/$GIT_BRANCH

# Étape 3 : Installation des dépendances
echo "📦 Installation des dépendances Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# Étape 4 : Optimisations Laravel
echo "⚙️ Nettoyage et optimisations Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Étape 5 : Migrations (sans seeders en production)
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# Étape 6 : Build des assets (si Vite configuré sur serveur)
# Décommentez si npm est disponible
# echo "🎨 Build des assets frontend..."
# npm ci --production
# npm run build

# Étape 7 : Permissions
echo "🔐 Ajustement des permissions..."
chmod -R 755 storage bootstrap/cache
chown -R $USER:$USER storage bootstrap/cache

# Étape 8 : Fin de la maintenance
echo "✅ Désactivation du mode maintenance..."
php artisan up

echo "🎉 Déploiement terminé avec succès !"
echo "📊 Version déployée : $(git log -1 --pretty=format:'%h - %s')"
