#!/bin/bash
# Script de commit T7 - Prix d'achat éditable dans Fonds

cd /home/aur-lien/vinyles-stock

echo "🦞 Commit T7 - Prix d'achat Fonds"
echo "================================"

# Vérifie si dans le bon repo
if [ ! -f "artisan" ]; then
    echo "❌ Pas dans un projet Laravel"
    exit 1
fi

# Add les fichiers
git add app/Http/Controllers/FondController.php
git add resources/views/fonds/index.blade.php
git add docs/T7_PRIX_ACHAT_FONDS.md
git add scripts/commit-T7.sh

# Commit
git commit -m "feat(T7): prix d'achat éditable dans Fonds (admin only)

- Controller: validation et update prix_achat optionnel
- Vue: input inline avec style violet/rose, admin seulement
- Sécurité: restriction admin pour modification prix
- Documentation: T7_PRIX_ACHAT_FONDS.md créée"

echo "✅ Commit T7 réalisé"
echo ""
git log --oneline -3
