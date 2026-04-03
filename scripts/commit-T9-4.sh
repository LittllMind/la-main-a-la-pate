#!/bin/bash

# Script de commit T9.4 - Documentation + Tests

cd ~/vinyles-stock

echo "🏃 Commit T9.4 - Documentation complète + Tests intégration"
echo "============================================================"

# Ajouter fichiers
git add docs/T9-4-DOCUMENTATION.md
git add tests/Integration/MouvementsStockIntegrationTest.php

# Commit
git commit -m "docs/T9.4: Documentation système mouvements stock + tests intégration

- Guide complet d'architecture (T9-4-DOCUMENTATION.md)
- Tests E2E pour Vinyle/Fond/Order observers
- Scénarios: création, modification, suppression, commandes
- Couverture: entrees, sorties, annulations, retours

Refs: T9.4"

echo "✅ Commit T9.4 effectué"
echo ""
git log --oneline -3
