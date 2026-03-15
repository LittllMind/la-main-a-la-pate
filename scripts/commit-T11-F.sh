#!/bin/bash
# Script de commit T11-F : GitHub Actions CI/CD

cd /home/aur-lien/vinyles-stock

# Créer le dossier .github/workflows
mkdir -p .github/workflows

# Configuration git
git config user.name "PicoClaw"
git config user.email "picoclaw@assistant.local"

# Stage uniquement les fichiers pertinents
git add .github/workflows/ci.yml
git add .github/workflows/deploy.yml
git add README.md
git add docs/T11-F-CI-CD.md

# Commit
git commit -m "feat: T11-F - GitHub Actions CI/CD

- Workflow CI: tests MySQL, coverage, lint
- Workflow Deploy: SSH + notifications Slack
- Badges README: CI + Deploy
- Documentation T11-F avec structure des tests
- 128 tests au total (T11-A à T11-E)"

echo "✅ Commit T11-F effectué avec succès !"
echo ""
echo "Fichiers ajoutés:"
echo "  - .github/workflows/ci.yml"
echo "  - .github/workflows/deploy.yml"
echo "  - docs/T11-F-CI-CD.md (README mis à jour)"
