#!/bin/bash
# Script de commit T11-A : Infrastructure Tests
# Mode HeartBeat Marathon - 2026-03-09

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

# Configuration git
git config user.name "PicoClaw" 2>/dev/null || true
git config user.email "picoclaw@assistant.local" 2>/dev/null || true

# Liste des fichiers
FILES=(
    "phpunit.xml"
    "database/factories/FondFactory.php"
    "database/factories/OrderFactory.php"
    "database/factories/OrderItemFactory.php"
    "database/factories/MouvementStockFactory.php"
    "tests/TestCase.php"
    "tests/Feature/InfrastructureTest.php"
)

echo "=== T11-A Infrastructure Tests ==="
echo ""
echo "Fichiers à commiter :"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
        git add "$file"
    else
        echo "  ✗ $file (absent)"
    fi
done

echo ""
echo "=== Commit ==="
git commit -m "test/T11-A: Configuration infrastructure PHPUnit + factories

- phpunit.xml: activation SQLite in-memory pour tests rapides
- FondFactory: factory complète avec états (miroir/dore/standard/critique)
- OrderFactory: factory commandes avec états (pending/paid/ready/delivered/cancelled)
- OrderItemFactory: factory items avec/sans fond
- MouvementStockFactory: factory mouvements (entree/sortie)
- TestCase: helpers auth personnalisés (adminUser/employeUser/clientUser)
- InfrastructureTest: validation setup et configuration

Setup:
- Environment testing avec SQLite in-memory
- DatabaseRefresh pour isolation des tests
- Factories avec relations et états prédéfinis"

echo ""
git log --oneline -1
echo ""
echo "✅ T11-A COMMITTÉ"
echo "Prochaine tâche: T11-B Tests Dashboard Fonds (21 tests)"
