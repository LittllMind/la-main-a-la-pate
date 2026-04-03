#!/bin/bash
# 🚀 Execution commit T10 directement

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

echo "Commit T10: Filtres Alertes Stock..."

# T10 Controller
git add app/Http/Controllers/StockAlertController.php

# T10 Model
git add app/Models/StockAlert.php

# T10 Vue
git add resources/views/stock-alerts/index.blade.php

# T10 Migration
git add database/migrations/2026_03_09_000001_add_resolved_at_to_stock_alerts.php

# T10 Routes  
git add routes/web.php

# T10 Doc
git add docs/T10-FILTRES-ALERTES.md

# Commit
git commit -m "feat/T10: Filtres avancés alertes stock

- 6 filtres multicritères: type, produit, statut, dates, recherche, tri
- Stats temps réel: ruptures/faibles/actives/aujourd'hui/semaine  
- Export CSV avec filtres conservés
- Migration resolved_at pour gestion alertes résolues
- UI violet/rose responsive Fundisc avec badges actifs"

echo "✅ T10 commité !"
git log --oneline -3