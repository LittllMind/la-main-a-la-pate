#!/bin/bash
# Script de commit - Session 8 Mars 2026
# T6 StockAlerts + T7 Prix achat fonds + Mode Marché

cd ~/vinyles-stock

echo "📝 Vérification du statut..."
git status --short

echo ""
echo "📦 Ajout des fichiers..."

# Migrations
git add database/migrations/2026_03_07_000001_fix_ligne_ventes_ondelete.php
git add database/migrations/2026_03_08_150045_update_ligne_ventes_soft_delete.php
git add database/migrations/2026_03_08_170000_add_mode_marche_to_orders.php
git add database/migrations/2026_03_08_000001_create_fonds_table.php

# Modèles
git add app/Models/Fond.php
git add app/Models/LigneVente.php
git add app/Models/Order.php

# Controllers
git add app/Http/Controllers/FondController.php
git add app/Http/Controllers/ModeMarcheController.php
git add app/Http/Controllers/StockAlertController.php

# Commands
git add app/Console/Commands/CheckStockAlerts.php

# Vues
git add resources/views/fonds/index.blade.php
git add resources/views/marche/index.blade.php
git add resources/views/stock-alerts/
git add resources/views/dashboard.blade.php
git add resources/views/landing.blade.php
git add resources/views/layouts/app.blade.php

# Routes
git add routes/web.php

# Documentation
git add docs/LEGACY_VENTES_AUDIT.md
git add docs/MODE_MARCHE_SPECS.md
git add docs/STOCK_ALERTS.md
git add docs/T7_PRIX_ACHAT_FONDS.md

# Autres fichiers
git add HEARTBEAT.md

# Retirer les scripts de commit de l'index (pas besoin dans le repo)
git reset HEAD scripts/commit-T6.sh scripts/commit-T7.sh 2>/dev/null || true

echo ""
echo "✅ Fichiers stagés. Message de commit :"
echo ""
echo "feat: T6 StockAlerts + T7 Fonds prix achat + Mode Marché + Landing responsive"
echo ""
echo "- Stock Alerts automatiques (seuils, commande, emails)"
echo "- Dashboard Fonds avec prix achat (2€), prix vente, montants"
echo "- Mode Marché (gestion CASH sans stock)"
echo "- Landing mobile responsive + titre Fundisc"
echo "- Documentation complète (stock alerts, mode marché, T7)"
echo "- Fix migrations ligne_ventes soft delete"
echo ""

# Commit avec message interactif ou automatique
git commit -m "feat: T6 StockAlerts + T7 Fonds prix achat + Mode Marché + Landing responsive

- Stock Alerts automatiques (seuils, commande, emails)
- Dashboard Fonds avec prix achat (2€), prix vente, montants
- Mode Marché (gestion CASH sans stock)
- Landing mobile responsive + titre Fundisc
- Documentation complète (stock alerts, mode marché, T7)
- Fix migrations ligne_ventes soft delete"

echo ""
echo "🎉 Commit créé !"
git log --oneline -1
git status --short
