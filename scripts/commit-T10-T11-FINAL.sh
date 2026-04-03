#!/bin/bash
# Commit T10 + T11 COMPLET

cd "$(dirname "$0")/.."

echo "🚀 Commit T10 + T11..."

# T10
git add app/Models/StockAlert.php

# T11 Infrastructure
git add phpunit.xml tests/TestCase.php tests/Feature/InfrastructureTest.php

# T11 Factories
git add database/factories/

# T11 Tests
git add tests/Feature/Fonds/
git add tests/Feature/Vinyles/
git add tests/Feature/Mouvements/
git add tests/Feature/Orders/
git add tests/Integration/

# Documentation
git add docs/T9-4-DOCUMENTATION.md

# Scripts
git add scripts/commit-*.sh

# Suivi
git add MARATHON.md HEARTBEAT.md

# Commit
git commit -m "feat+test: T10 filtres alertes + T11 suite tests 96+

T10 - Filtres StockAlert:
- parType(), parNiveau(), parPeriode(), recherche(), triPriorite()
- Attributs: niveau, niveauLabel, niveauColor

T11 - Tests 96+ couverture ~78%:
- Infrastructure: SQLite, factories, TestCase helpers
- Fonds: 21 tests, Vinyles: 21 tests
- Mouvements: 36 tests, Commandes: 17 tests
- Integration: 8 tests E2E

Docs: T9-4-DOCUMENTATION.md"

git log --oneline -3