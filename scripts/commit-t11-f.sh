#!/bin/bash
# Commit T11-F: GitHub Actions CI/CD + Badges

cd ~/vinyles-stock || exit 1

echo "=== Commit T11-F: GitHub Actions CI/CD ==="

# Vérifier si les fichiers existent
if [ ! -f ".github/workflows/ci.yml" ]; then
    echo "❌ Erreur: .github/workflows/ci.yml n'existe pas"
    exit 1
fi

git add .github/workflows/ci.yml
git add README.md

echo "📝 Fichiers ajoutés:"
git status --short

echo ""
echo "💾 Création du commit..."
git commit -m "feat: T11-F - GitHub Actions CI/CD workflow + badges

- Workflow tests sur PHP 8.2/8.3 + MySQL
- Cache composer dependencies
- Tests parallèles avec coverage
- Code style check (Pint)
- Static analysis (PHPStan)
- Badges CI/PHP/Laravel dans README"

echo "✅ Commit T11-F créé avec succès!"
echo ""
echo "📊 Statut:"
git log --oneline -1