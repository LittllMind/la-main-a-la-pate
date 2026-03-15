#!/bin/bash
# Script de commit T11-C: Tests Feature Vinyles

cd /home/aur-lien/vinyles-stock

echo "🧪 T11-C: Tests Feature Vinyles"
echo "================================"

# Exécution des tests
echo "🏃 Exécution des tests..."
./vendor/bin/phpunit tests/Feature/Vinyles/ --testdox

if [ $? -ne 0 ]; then
    echo "❌ Tests échoués - Commit annulé"
    exit 1
fi

echo "✅ Tous les tests passent !"
echo ""

# Commit
git add tests/Feature/Vinyles/
git add database/factories/VinyleFactory.php
git add scripts/commit-T11-C.sh

git commit -m "test/T11-C: Tests Feature Vinyles - index avec recherche/filtres et affichage détail"

echo ""
echo "✅ Commit effectué avec succès !"
git log --oneline -1
