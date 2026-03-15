#!/bin/bash
# Commit T11 COMPLET : Tous les tests Feature et Integration (A+B+C+D+E)

cd "$(dirname "$0")/.."

echo "🧪 Préparation commit T11 COMPLET (A+B+C+D+E)..."

# Infrastructure
git add phpunit.xml
git add tests/TestCase.php
git add tests/Feature/InfrastructureTest.php

# Factories
git add database/factories/FondFactory.php
git add database/factories/MouvementStockFactory.php
git add database/factories/OrderFactory.php
git add database/factories/OrderItemFactory.php
git add database/factories/VinyleFactory.php

# Tests Fonds (T11-B)
git add tests/Feature/Fonds/FondControllerIndexTest.php
git add tests/Feature/Fonds/FondControllerActionsTest.php

# Tests Vinyles (T11-C)
git add tests/Feature/Vinyles/VinyleControllerIndexTest.php
git add tests/Feature/Vinyles/VinyleControllerActionsTest.php
git add tests/Feature/Vinyles/VinyleControllerShowTest.php

# Tests Mouvements (T11-D)
git add tests/Feature/Mouvements/StockMovementControllerIndexTest.php
git add tests/Feature/Mouvements/StockMovementControllerExportTest.php
git add tests/Feature/Mouvements/StockMovementServiceTest.php

# Tests Commandes (T11-E)
git add tests/Feature/Orders/OrderControllerIntegrationTest.php

# Tests Integration (T9.4)
git add tests/Integration/MouvementsStockIntegrationTest.php

# Documentation
git add docs/T9-4-DOCUMENTATION.md

# Scripts de commit (pour référence)
git add scripts/commit-T11-A.sh
git add scripts/commit-T11-B.sh
git add scripts/commit-T11-C.sh
git add scripts/commit-T11-ABC.sh
git add scripts/commit-t11-d.sh
git add scripts/commit-t11-e.sh
git add scripts/commit-T9-4.sh
git add scripts/commit-nav-final.sh

# Fichiers de suivi
git add MARATHON.md
git add HEARTBEAT.md

# Commit
git commit -m "test/T11-COMPLET: Suite tests feature et integration (95 tests)

Infrastructure:
- phpunit.xml avec SQLite in-memory
- TestCase avec helpers auth (admin/employe/client)
- Factories: Fond, Vinyle, Order, OrderItem, MouvementStock

Tests Feature Fonds (21 tests):
- FondControllerIndexTest: accès, calculs, statuts, permissions
- FondControllerActionsTest: actions +/-, mouvements auto

Tests Feature Vinyles (21 tests):
- VinyleControllerIndexTest: recherche, filtres, pagination
- VinyleControllerActionsTest: statuts, redirections
- VinyleControllerShowTest: affichage détail

Tests Feature Mouvements (36 tests):
- StockMovementControllerIndexTest: accès, filtres, stats
- StockMovementControllerExportTest: export CSV
- StockMovementServiceTest: service, transactions

Tests Integration Commandes (16 tests):
- OrderControllerIntegrationTest: flow complet commande
- Validation, panier, paiement, adresses
- Intégration CartService, fon sélectionnés

Tests Integration (T9.4):
- MouvementsStockIntegrationTest: scénarios E2E

Couverture estimée: ~78% des controllers principaux
Documentation: docs/T9-4-DOCUMENTATION.md"

echo ""
git log --oneline -3
echo ""
echo "✅ T11 COMPLET commité ! 95 tests créés"
echo ""
echo "📊 Résumé:"
echo "  - T11-A: Infrastructure (1 test + factories)"
echo "  - T11-B: Fonds (21 tests)"
echo "  - T11-C: Vinyles (21 tests)"
echo "  - T11-D: Mouvements (36 tests)"
echo "  - T11-E: Commandes (16 tests)"
echo "  - Total: 95 tests feature et integration"