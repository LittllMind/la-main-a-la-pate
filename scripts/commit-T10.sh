#!/bin/bash
# Script de commit T10 : Filtres Alertes Stock Avancés

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

# Configuration git
git config user.name "PicoClaw"
git config user.email "picoclaw@assistant.local"

# Stage fichiers
git add app/Http/Controllers/StockAlertController.php
git add app/Models/StockAlert.php
git add resources/views/stock-alerts/index.blade.php
git add routes/web.php
git add database/migrations/2026_03_09_000001_add_resolved_at_to_stock_alerts.php
git add docs/T10-FILTRES-ALERTES.md
git add scripts/commit-T10.sh

# Commit
git commit -m "feat/T10: Filtres avancés alertes stock - multicritères avec export rapide

- Controller StockAlertController : méthode index() avec filtres multicritères
- Filtres : type (rupture/faible), produit (vinyle/fond), statut, dates, recherche
- Stats temps réel avec breakdown vinyles/fonds/aujourd'hui/cette semaine
- Export CSV avec filtres conservés
- Vue : design violet/rose Fundisc, filtres responsive, badges actifs
- Migration : add resolved_at pour tracking date de résolution
- Documentation T10 complète

Coverage : 6 filtres + recherche + tri + export"

echo "✅ Commit T10 effectué !"
echo ""
echo "Fichiers commités :"
echo "  - app/Http/Controllers/StockAlertController.php"
echo "  - app/Models/StockAlert.php"
echo "  - resources/views/stock-alerts/index.blade.php"
echo "  - database/migrations/2026_03_09_000001_add_resolved_at_to_stock_alerts.php"
echo "  - docs/T10-FILTRES-ALERTES.md"
