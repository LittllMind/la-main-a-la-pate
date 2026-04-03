#!/bin/bash
# Script de commit T11-E : Tests Integration Commandes

cd ~/vinyles-stock || exit 1

echo "🧪 T11-E : Tests Integration Commandes"
echo "==================================="
echo ""

# Vérifier que les fichiers existent
echo "📁 Vérification des fichiers..."
[ -f "tests/Feature/Orders/TestOrderStockMovementCommandTest.php" ] && echo "✅ TestOrderStockMovementCommandTest.php"
[ -f "tests/Feature/Orders/OrderControllerIntegrationTest.php" ] && echo "✅ OrderControllerIntegrationTest.php (existant)"

# Vérifier les factories
echo ""
echo "📦 Factories disponibles..."
[ -f "database/factories/OrderFactory.php" ] && echo "✅ OrderFactory"
[ -f "database/factories/OrderItemFactory.php" ] && echo "✅ OrderItemFactory"
[ -f "database/factories/VinyleFactory.php" ] && echo "✅ VinyleFactory"
[ -f "database/factories/FondFactory.php" ] && echo "✅ FondFactory"

# Git status
echo ""
echo "📊 Status Git..."
git status --short

# Créer le commit
echo ""
echo "📝 Création du commit..."
git add tests/Feature/Orders/TestOrderStockMovementCommandTest.php

git commit -m "feat/T11-E: Tests d'intégration Commandes + commande Console

Tests Feature:
- TestOrderStockMovementCommandTest.php (19 tests)
  * Tests existence de la commande
  * Tests création données test (vinyle, fond, order, item)
  * Tests mouvements stock automatiques
  * Tests nettoyage données
  * Tests flow complet et idempotence

Coverage:
- Commande artisan test:order-movement
- Flow création commande → validation → mouvements
- Vérification observer Order → MouvementStock
- Scénarios edge cases

Lecture: feuilles-de-route/T11-architecture-tests.md"

echo ""
echo "✅ Commit T11-E créé !"
echo ""
echo "🎯 Résumé T11 :"
echo "   T11-A ✅ Infrastructure (PHPUnit + factories)"
echo "   T11-B ✅ Tests Dashboard Fonds"
echo "   T11-C ✅ Tests Liste Vinyles"
echo "   T11-D ✅ Tests Mouvements Stock"
echo "   T11-E ✅ Tests Integration Commandes"
echo ""
echo "🚀 Prochaine étape : T11-F CI/CD ou T10 Filtres alertes"