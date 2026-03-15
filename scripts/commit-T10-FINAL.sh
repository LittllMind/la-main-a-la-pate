#!/bin/bash
# Commit T10: Filtres Alertes Stock Avancés

cd "$(dirname "$0")/.."

echo "🚀 Commit T10: Filtres Alertes Stock..."

# ========== T10 ==========
git add app/Http/Controllers/StockAlertController.php
git add app/Models/StockAlert.php
git add resources/views/stock-alerts/index.blade.php
git add database/migrations/2026_03_09_000001_add_resolved_at_to_stock_alerts.php
git add routes/web.php
git add docs/T10-FILTRES-ALERTES.md

# Commit
git commit -m "feat/T10: Filtres avancés alertes stock

- 6 filtres multicritères: type, produit, statut, dates, recherche, tri
- Stats temps réel: ruptures/faibles/actives/aujourd'hui/semaine
- Export CSV avec filtres conservés
- Migration resolved_at pour gestion alertes résolues
- UI violet/rose responsive Fundisc avec badges actifs

Fichiers:
- StockAlertController: filtres + export
- StockAlert: scopes + resolved_at
- Vue responsive avec stats cards
- Migration add_resolved_at_to_stock_alerts
- Documentation T10-FILTRES-ALERTES.md"

echo ""
git log --oneline -3
echo ""
echo "✅ T10 commité ! Prochain: T11-A Infrastructure Tests"