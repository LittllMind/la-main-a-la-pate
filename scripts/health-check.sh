#!/bin/bash
# Health Check Script — vinyles-stock
# Vérifie l'état du système avant/après déploiement

# set -e  # Désactivé pour éviter arrêt brutal sur erreur non critique

VERBOSE=false
FIX=false

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --verbose|-v) VERBOSE=true ; shift ;;
    --fix|-f) FIX=true ; shift ;;
    --help|-h) 
      echo "Usage: $0 [--verbose|--fix]"
      echo "  --verbose, -v : Affiche les détails"
      echo "  --fix, -f     : Tente de corriger automatiquement les erreurs"
      exit 0
      ;;
    *) shift ;;
  esac
done

# Compteurs
CHECKS_PASSED=0
CHECKS_FAILED=0
WARNINGS=0

# Fonctions helpers
pass() { echo -e "${GREEN}✓${NC} $1"; ((CHECKS_PASSED++)); }
fail() { echo -e "${RED}✗${NC} $1"; ((CHECKS_FAILED++)); }
warn() { echo -e "${YELLOW}⚠${NC} $1"; ((WARNINGS++)); }
info() { [[ "$VERBOSE" == true ]] && echo -e "  $1"; }

header() {
  echo ""
  echo "═══════════════════════════════════════"
  echo "  $1"
  echo "═══════════════════════════════════════"
}

# ============================================
header "1. ENVIRONNEMENT PHP"
# ============================================

PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "")
if [[ -n "$PHP_VERSION" ]]; then
  MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
  MINOR=$(echo $PHP_VERSION | cut -d. -f2)
  if [[ $MAJOR -ge 8 && $MINOR -ge 2 ]]; then
    pass "PHP $PHP_VERSION (≥ 8.2)"
  else
    fail "PHP $PHP_VERSION (requis: ≥ 8.2)"
  fi
  info "Version: $PHP_VERSION"
else
  fail "PHP non trouvé"
fi

# Extensions requises
EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "openssl" "fileinfo" "gd" "redis" "tokenizer" "xml" "ctype" "json" "bcmath" "curl")
for ext in "${EXTENSIONS[@]}"; do
  if php -m 2>/dev/null | grep -qi "^$ext$"; then
    pass "Extension PHP: $ext"
  else
    warn "Extension PHP manquante: $ext"
    if [[ "$FIX" == true ]]; then
      info "→ Installer: sudo apt-get install php-$ext (Ubuntu)"
    fi
  fi
done

# ============================================
header "2. BASE DE DONNÉES"
# ============================================

# Vérifier connexion DB via artisan
cd "$(dirname "$0")/.." 2>/dev/null || cd . || exit 1
if php artisan tinker --execute="echo DB::connection()->getDatabaseName();" 2>/dev/null | grep -q "vinyles"; then
  pass "Connexion DB OK"
  info "Database: $(php artisan tinker --execute="echo DB::connection()->getDatabaseName();" 2>/dev/null)"
else
  fail "Impossible de se connecter à la base de données"
  info "Vérifier DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD dans .env"
fi

# Vérifier migrations
if php artisan migrate:status 2>/dev/null | grep -q "No migrations"; then
  fail "Aucune migration trouvée ou connexion échouée"
else
  MIGRATIONS_PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" 2>/dev/null | head -1 || echo 0)
  if [[ "$MIGRATIONS_PENDING" -gt 0 ]]; then
    warn "$MIGRATIONS_PENDING migrations en attente"
    if [[ "$FIX" == true ]]; then
      php artisan migrate --force 2>/dev/null && pass "Migrations exécutées" || fail "Échec migrations"
    fi
  else
    pass "Migrations à jour"
  fi
fi

# ============================================
header "3. FICHIERS & PERMISSIONS"
# ============================================

# Dossiers critiques
DIRS=("storage" "bootstrap/cache" "storage/app" "storage/framework" "storage/logs")
for dir in "${DIRS[@]}"; do
  if [[ -d "$dir" ]]; then
    if [[ -w "$dir" ]]; then
      pass "Permissions OK: $dir"
    else
      fail "Permissions insuffisantes: $dir"
      if [[ "$FIX" == true ]]; then
        chmod -R 775 "$dir" && pass "Permissions corrigées: $dir"
      fi
    fi
  else
    fail "Dossier manquant: $dir"
  fi
done

# Liens symboliques
if [[ -L "public/storage" ]]; then
  pass "Lien storage:link OK"
else
  fail "Lien storage manquant"
  if [[ "$FIX" == true ]]; then
    php artisan storage:link 2>/dev/null && pass "Lien créé" || fail "Échec création lien"
  fi
fi

# ============================================
header "4. CACHE & CONFIG"
# ============================================

if [[ -f "bootstrap/cache/config.php" ]]; then
  pass "Config en cache"
else
  warn "Config non cachée (optionnel en dev)"
  if [[ "$FIX" == true ]]; then
    php artisan config:cache 2>/dev/null && pass "Config cachée"
  fi
fi

if [[ -d "storage/framework/cache/data" ]] && [[ $(ls -A "storage/framework/cache/data" 2>/dev/null | wc -l) -gt 0 ]]; then
  pass "Cache actif"
else
  info "Cache vidé ou vide"
fi

# APP_KEY
if grep -q "APP_KEY=base64:" .env 2>/dev/null || grep -q "^APP_KEY=" .env 2>/dev/null; then
  KEY=$(grep "^APP_KEY=" .env | cut -d= -f2)
  if [[ ${#KEY} -gt 10 ]]; then
    pass "APP_KEY configuré"
  else
    fail "APP_KEY invalide"
  fi
else
  fail "APP_KEY manquant"
  if [[ "$FIX" == true ]]; then
    php artisan key:generate 2>/dev/null && pass "APP_KEY généré"
  fi
fi

# ============================================
header "5. COMPOSER DÉPENDANCES"
# ============================================

if [[ -d "vendor" ]]; then
  pass "Dossier vendor présent"
  
  # Vérifier si à jour
  if [[ -f "composer.lock" ]] && [[ -f "composer.json" ]]; then
    COMPOSER_OLD=$(stat -c %Y composer.lock 2>/dev/null || stat -f %m composer.lock)
    JSON_NEW=$(stat -c %Y composer.json 2>/dev/null || stat -f %m composer.json)
    if [[ $COMPOSER_OLD -lt $JSON_NEW ]]; then
      warn "composer.lock obsolète (composer.json plus récent)"
      if [[ "$FIX" == true ]]; then
        composer install --no-dev --optimize-autoloader 2>/dev/null && pass "Dépendances mises à jour"
      fi
    fi
  fi
else
  fail "Dossier vendor manquant (composer install requis)"
fi

# PHP CS Fixer (optionnel)
if [[ -f "vendor/bin/php-cs-fixer" ]]; then
  pass "PHP CS Fixer présent"
fi

# ============================================
header "6. TESTS"
# ============================================

if php artisan test --list-tests 2>/dev/null | grep -q "tests"; then
  pass "Tests disponibles"
  if [[ "$VERBOSE" == true ]]; then
    TEST_COUNT=$(php artisan test --list-tests 2>/dev/null | wc -l)
    info "$TEST_COUNT tests trouvés"
  fi
else
  warn "Tests non disponibles ou exécution échouée"
fi

# ============================================
header "7. RÉSUMÉ"
# ============================================

echo ""
echo "╔═════════════════════════════════════╗"
echo "║         RÉSULTAT GLOBAL             ║"
echo "╠═════════════════════════════════════╣"
printf "║ ${GREEN}✓ Passés  : %2d${NC}                    ║\n" $CHECKS_PASSED
printf "║ ${RED}✗ Échecs  : %2d${NC}                    ║\n" $CHECKS_FAILED
printf "║ ${YELLOW}⚠ Avertiss: %2d${NC}                    ║\n" $WARNINGS
echo "╚═════════════════════════════════════╝"
echo ""

if [[ $CHECKS_FAILED -eq 0 ]]; then
  echo -e "${GREEN}✓ Système OK — Prêt pour déploiement${NC}"
  exit 0
else
  echo -e "${RED}✗ Système a des problèmes — Voir détails ci-dessus${NC}"
  exit 1
fi
