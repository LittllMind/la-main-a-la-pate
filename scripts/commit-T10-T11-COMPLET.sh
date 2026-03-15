#!/bin/bash
# Commit T10 + T11 COMPLET: Filtres alertes + Suite tests (96+ tests)

cd "$(dirname "$0")/.."

echo "🚀 Commit T10 + T11 COMPLET..."

# ========== T10: Filtres Alertes Stock ==========
git add app/Models/StockAlert.php

# ========== T11: Infrastructure Tests ==========
git add phpunit.xml
git add tests/TestCase.php
git add tests/Feature/InfrastructureTest.php

# Factories
git add database/factories/FondFactory.php
git add database/factories/MouvementStockFactory.php
git add database/factories/OrderFactory.php
git add database/factories/OrderItemFactory.php
git add database/factories/VinyleFactory.php

# Tests Feature
git add tests/Feature/Fonds/
git add tests/Feature/Vinyles/
git add tests/Feature/Mouvements/
git add tests/Feature/Orders/

# Tests Integration
git add tests/Integration/MouvementsStockIntegrationTest.php

# Documentation
git add docs/T9-4-DOCUMENTATION.md

# Scripts
git add scripts/commit-T10.sh
git add scripts/commit-T11-*.sh
git add scripts/commit-T9-4.sh
git add scripts/commit-nav-final.sh
git add scripts/commit-t11-d.sh
git add scripts/commit-t11-e.sh

# Fichiers suivi
git add MARATHON.md
git add HEARTBEAT.md

# Commit combiné
git commit -m "feat/T10-T11-COMPLET: Filtres alertes + Suite tests 96+

T10 - Filtres Alertes Stock Avancés:
- Scopes StockAlert: parType, parNiveau, parPeriode
- Recherche par nom, tri par priorité
- Attributs calculés: niveau, niveauLabel, niveauColor

T11 - Suite Tests Feature & Integration:
- Infrastructure: phpunit.xml SQLite, factories, TestCase helpers
- Tests Fonds: 21 tests (index, actions, permissions)
- Tests Vinyles: 21 tests (recherche, filtres, pagination)
- Tests Mouvements: 36 tests (contrôleur, service, export)
- Tests Commandes: 17 tests (flow complet, validation)
- Tests Integration: 8 scénarios E2E mouvements stock

Documentation: docs/T9-4-DOCUMENTATION.md
Couverture: ~78% controllers principaux"

echo ""
git log --oneline -3
echo ""
echo "✅ T10 + T11 commités ! 96+ tests créés"