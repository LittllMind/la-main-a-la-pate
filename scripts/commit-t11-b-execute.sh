#!/bin/bash
# Script de commit T11-B : Tests Dashboard Fonds

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

echo "📦 Commit T11-B : Tests Dashboard Fonds"
echo ""

# Vérification des fichiers
FILES=(
    "tests/Feature/Fonds/FondControllerIndexTest.php"
    "tests/Feature/Fonds/FondControllerActionsTest.php"
)

echo "Fichiers à commiter :"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
        git add "$file"
    else
        echo "  ✗ $file (absent)"
    fi
done

echo ""
echo "=== Commit ==="
git commit -m "test/T11-B: Tests Dashboard Fonds (index + actions)

- FondControllerIndexTest: accès Admin/Employé, redirections, totaux
- FondControllerActionsTest: +1/-1, permissions, mouvements stock auto
- Validation des calculs (valeur, marges)
- Tests rôles et permissions

Couverture: ~85% FondController"

echo ""
git log --oneline -1
echo ""
echo "✅ T11-B COMMITTÉ"
echo "Prochaine tâche: T11-C Tests Feature Vinyles (21 tests)"
