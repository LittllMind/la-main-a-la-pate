#!/bin/bash

# ============================================
# SCRIPT DE DÉPLOIEMENT AUTOMATISÉ
# Projet: Vinyles Stock Management
# ============================================

set -e  # Arrêt immédiat si erreur

# CONFIGURATION
REMOTE_USER="u417457839"
REMOTE_HOST="la-main-a-la-pate.online"
REMOTE_PORT="65002"
REMOTE_PATH="/home/$REMOTE_USER/domains/la-main-a-la-pate.online/public_html"
BRANCH="master"

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}🚀 DÉPLOIEMENT AUTOMATIQUE EN PRODUCTION${NC}"
echo -e "${BLUE}================================================${NC}\n"

# ============================================
# ÉTAPE 1 : VÉRIFICATIONS LOCALES
# ============================================
echo -e "${YELLOW}[1/8] Vérifications locales...${NC}"

CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${RED}❌ Tu n'es pas sur la branche '$BRANCH' (actuellement sur '$CURRENT_BRANCH')${NC}"
    read -p "Continuer quand même ? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

if [[ -n $(git status -s) ]]; then
    echo -e "${RED}⚠️  Modifications non commitées détectées :${NC}"
    git status -s
    read -p "Commit automatique avec message '[AUTO] Deploy' ? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git add .
        git commit -m "[AUTO] Deploy $(date +'%Y-%m-%d %H:%M:%S')"
    else
        echo -e "${RED}❌ Commit tes modifications avant de déployer${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✅ Vérifications locales OK${NC}\n"

# ============================================
# ÉTAPE 2 : TESTS LOCAUX
# ============================================
if [ "$1" == "--skip-tests" ]; then
    echo -e "${YELLOW}[2/8] Tests ignorés (--skip-tests)${NC}\n"
else
    echo -e "${YELLOW}[2/8] Exécution des tests...${NC}"

    # Installer systématiquement les dépendances Composer pour garantir un environnement propre (inclut dev deps)
    echo -e "${YELLOW}📦 Installation des dépendances — exécution de 'composer install' (dev inclus)...${NC}"
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}❌ Composer introuvable dans le PATH. Installe Composer ou lance 'composer install' manuellement.${NC}"
        exit 1
    fi
    composer install --no-interaction --prefer-dist --quiet || {
        echo -e "${RED}❌ Échec de 'composer install'${NC}"
        exit 1
    }

    php artisan test --stop-on-failure || {
        echo -e "${RED}❌ Tests échoués${NC}"
        exit 1
    }
    echo -e "${GREEN}✅ Tests OK${NC}\n"
fi

echo "DEBUG: Après les tests, avant optimisations"
read -p "Appuyer sur ENTER pour continuer..."

# ============================================
# ÉTAPE 3 : OPTIMISATIONS LARAVEL
# ============================================
echo -e "${YELLOW}[3/8] Optimisations Laravel...${NC}"

composer install --optimize-autoloader --no-dev --quiet
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✅ Optimisations terminées${NC}\n"

# ============================================
# ÉTAPE 4 : PUSH GIT
# ============================================
echo -e "${YELLOW}[4/8] Push vers GitHub...${NC}"

git push origin $BRANCH || {
    echo -e "${RED}❌ Échec du push Git${NC}"
    exit 1
}

echo -e "${GREEN}✅ Code poussé sur GitHub${NC}\n"

# ============================================
# ÉTAPE 5 : DÉPLOIEMENT SUR SERVEUR
# ============================================
echo -e "${YELLOW}[5/8] Connexion au serveur et déploiement...${NC}"

ssh -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST << ENDSSH
    set -e

    echo "📂 Accès au dossier de production..."
    cd $REMOTE_PATH

    echo "🔄 Pull des dernières modifications..."
    git pull origin $BRANCH

    echo "📦 Installation des dépendances Composer..."
    composer install --optimize-autoloader --no-dev

    echo "🔧 Migrations base de données..."
    php artisan migrate --force

    echo "🔗 Création lien symbolique storage..."
    php artisan storage:link 2>/dev/null || echo "Lien déjà existant"

    echo "⚡ Optimisations Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize

    echo "🔒 Configuration permissions..."
    chmod -R 755 storage bootstrap/cache
    chmod 644 .env

    echo "🧹 Nettoyage caches..."
    php artisan cache:clear
    php artisan view:clear

    echo "✅ Déploiement serveur terminé !"
ENDSSH

echo -e "${GREEN}✅ Déploiement serveur réussi${NC}\n"

# ============================================
# ÉTAPE 6 : VÉRIFICATION CRON
# ============================================
echo -e "${YELLOW}[6/8] Vérification du scheduler Laravel...${NC}"

ssh -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST << ENDSSH
    if ! crontab -l 2>/dev/null | grep -q "schedule:run"; then
        echo "⚠️  Cron Laravel non trouvé"
        echo "📝 Ligne à ajouter manuellement dans cPanel :"
        echo "   * * * * * cd $REMOTE_PATH && php artisan schedule:run >> /dev/null 2>&1"
    else
        echo "✅ Cron Laravel configuré"
    fi
ENDSSH

echo -e "${GREEN}✅ Vérification cron terminée${NC}\n"

# ============================================
# ÉTAPE 7 : TESTS POST-DÉPLOIEMENT
# ============================================
echo -e "${YELLOW}[7/8] Tests de santé du site...${NC}"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://$REMOTE_HOST)
if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ Site accessible (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠️  Réponse HTTP inhabituelle : $HTTP_CODE${NC}"
fi

echo ""

# ============================================
# ÉTAPE 8 : NETTOYAGE LOCAL
# ============================================
echo -e "${YELLOW}[8/8] Nettoyage local...${NC}"

php artisan config:clear
php artisan route:clear
php artisan view:clear
composer install --quiet

echo -e "${GREEN}✅ Nettoyage terminé${NC}\n"

# ============================================
# RÉSUMÉ FINAL
# ============================================
echo -e "${BLUE}================================================${NC}"
echo -e "${GREEN}🎉 DÉPLOIEMENT RÉUSSI !${NC}"
echo -e "${BLUE}================================================${NC}"
echo -e "🌐 URL: https://$REMOTE_HOST"
echo -e "📅 Date: $(date +'%Y-%m-%d %H:%M:%S')"
echo -e "🔗 Commit: $(git rev-parse --short HEAD)"
echo -e "${BLUE}================================================${NC}\n"

# Ouvrir le site dans le navigateur (Windows)
if command -v cmd.exe &> /dev/null; then
    cmd.exe /c start https://$REMOTE_HOST
fi
