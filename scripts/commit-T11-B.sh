#!/bin/bash
# Script de commit T11-B : Tests Dashboard Fonds

cd ~/vinyles-stock

echo "📦 Commit T11-B : Tests Dashboard Fonds"
echo ""

# Ajout des fichiers
git add tests/Feature/Fonds/

# Commit
git commit -m "test/T11-B: Tests Dashboard Fonds (index + actions)

- FondControllerIndexTest: accès Admin/Employé, redirections, totaux
- FondControllerActionsTest: +1/-1, permissions, mouvements stock auto
- Validation des calculs (valeur, marges)
- Tests rôles et permissions

Couverture: 12 tests Feature Fonds"

# Vérification
echo ""
echo "✅ Commit T11-B effectué"
git log --oneline -1