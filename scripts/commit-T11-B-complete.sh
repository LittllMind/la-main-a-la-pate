#!/bin/bash
# Script de commit T11-B - Tests Dashboard Fonds
# Mode Marathon : Une tâche par session

set -e

cd ~/vinyles-stock

echo "🏃 Commit T11-B : Tests Dashboard Fonds..."

# Vérifier les fichiers existent
if [ ! -f "tests/Feature/Fonds/FondControllerIndexTest.php" ]; then
    echo "❌ Fichier tests/Feature/Fonds/FondControllerIndexTest.php non trouvé"
    exit 1
fi

if [ ! -f "tests/Feature/Fonds/FondControllerActionsTest.php" ]; then
    echo "❌ Fichier tests/Feature/Fonds/FondControllerActionsTest.php non trouvé"
    exit 1
fi

# Git add
git add tests/Feature/Fonds/
git add database/factories/FondFactory.php

echo "📦 Fichiers ajoutés :"
git status --short | grep -E "(Fonds|FondFactory)" || echo "Aucun fichier trouvé"

# Commit
git commit -m "test/T11-B: Tests Dashboard Fonds complets (21 tests)

- FondControllerIndexTest (9 tests) : accès, totaux, statuts, permissions
- FondControllerActionsTest (12 tests) : +1/-1, mouvements, validation
- Couverture ~85% du FondController
- Factory Fond avec états (miroir/doré/standard, critique)

Tests inclus :
- Accès Admin/Employé, redirections Client/Guest
- Calculs totaux (quantité, montant_investi, valeur_totale)
- Statuts stock (OK/Faible/Rupture)
- Actions +1/-1 avec permissions
- Mouvements automatiques liés
- Update prix (Admin only)"

echo "✅ Commit T11-B effectué !"
git log --oneline -1
