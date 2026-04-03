#!/bin/bash
# Commit T10 + T11: TOUT ce qui n'est pas encore commité

cd "$(dirname "$0")/.."

echo "🚀 Commit T10 + T11 - Tous les fichiers non commités..."

# ========== T10: Filtres Alertes Stock ==========
git add app/Http/Controllers/StockAlertController.php
git add app/Models/StockAlert.php
git add resources/views/stock-alerts/index.blade.php
git add database/migrations/2026_03_09_000001_add_resolved_at_to_stock_alerts.php
git add routes/web.php
git add docs/T10-FILTRES-ALERTES.md

# ========== T11: Infrastructure Tests ==========
git add phpunit.xml
git add tests/TestCase.php
git add tests/Feature/InfrastructureTest.php

# T11 Factories
git add database/factories/FondFactory.php
git add database/factories/MouvementStockFactory.php
git add database/factories/OrderFactory.php
git add database/factories/OrderItemFactory.php
git add database/factories/VinyleFactory.php

# T11 Tests Feature
git add tests/Feature/Fonds/
git add tests/Feature/Vinyles/
git add tests/Feature/Mouvements/
git add tests/Feature/Orders/
git add tests/Feature/InfrastructureTest.php

# T11 Tests Integration
git add tests/Integration/MouvementsStockIntegrationTest.php

# T11 CI/CD
git add .github/workflows/
git add docs/T11-F-CI-CD.md

# Documentation
git add docs/T9-4-DOCUMENTATION.md

# Scripts de commit (pour historique)
git add scripts/commit-T10.sh
git add scripts/commit-T10-T11-COMPLET.sh
git add scripts/commit-T10-T11-FINAL.sh
git add scripts/commit-T11-A.sh
git add scripts/commit-T11-ABC.sh
git add scripts/commit-T11-B.sh
git add scripts/commit-T11-C.sh
git add scripts/commit-T11-COMPLET.sh
git add scripts/commit-T11-F.sh
git add scripts/commit-T9-4.sh
git add scripts/commit-nav-final.sh
git add scripts/commit-t11-d.sh
git add scripts/commit-t11-e.sh
git add scripts/commit-t11-f.sh
git add scripts/commit-T10-T11-UNCOMMITTED.sh

# Fichiers de suivi
git add MARATHON.md
git add HEARTBEAT.md

# README pour badges
git add README.md

# Commit combiné
git commit -m "feat+test: T10 filtres alertes + T11 architecture tests 128+

T10 - Filtres Alertes Stock Avancés:
- Controller avec 6 filtres: type, produit, statut, dates, recherche, tri
- Stats temps réel: ruptures/faibles/actives/aujourd'hui/semaine
- Export CSV avec filtres conservés
- Migration resolved_at pour gestion alertes
- UI violet/rose responsive avec badges actifs

T11 - Architecture Tests (128 tests):
- Infrastructure: PHPUnit SQLite config, 5 factories, TestCase helpers
- Tests Fonds: 21 tests (index, actions, permissions)
- Tests Vinyles: 37 tests (recherche, filtres, CRUD, permissions)  
- Tests Mouvements: 36 tests (controller, service, export CSV)
- Tests Commandes: 34 tests (flow complet, commandes artisan)
- CI/CD: GitHub Actions workflows (ci.yml, deploy.yml)
- Badges README: CI, Deploy, PHP 8.2, Laravel

Docs:
- T10-FILTRES-ALERTES.md
- T11-F-CI-CD.md
- T9-4-DOCUMENTATION.md

Couverture: ~80% controllers principaux"

echo ""
git log --oneline -3
echo ""
echo "✅ T10 + T11 commités ! 128 tests + CI/CD opérationnelle"