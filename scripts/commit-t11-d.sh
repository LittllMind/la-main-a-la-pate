#!/bin/bash
# Commit T11-D: Tests Feature Mouvements Stock

cd "$(dirname "$0")/.."

echo "🔧 Préparation commit T11-D..."

# Ajouter les fichiers de tests
git add tests/Feature/Mouvements/
git add database/factories/MouvementStockFactory.php
git add scripts/commit-t11-d.sh
git add docs/T9-4-DOCUMENTATION.md 2>/dev/null || true

# Commit
git commit -m "test/T11-D: Tests Feature Mouvements Stock

- StockMovementControllerIndexTest (14 tests)
  * Accès admin/employé/client
  * Calculs des stats
  * Filtres type/produit/dates/recherche
  * Tests pagination et tri
  * Tests filtres multiples

- StockMovementControllerExportTest (8 tests)
  * Export CSV accessible admin/employé
  * Interdit pour client
  * Headers corrects
  * Contenu avec filtres

- StockMovementServiceTest (14 tests)
  * Transactions DB
  * Création mouvements entree/sortie
  * Traçage automatique ventes/achats
  * Gestion erreurs et rollback

Coverage: accès, filtres, pagination, export CSV"

echo ""
git log --oneline -3
echo ""
echo "✅ T11-D commité !"
