#!/bin/bash
# COMMIT FINAL MARATHON T7/T8/T9
# À exécuter manuellement

cd ~/vinyles-stock

echo "=== COMMIT MARATHON FINAL ==="

# Ajouter les nouveaux fichiers
git add app/Console/Commands/TestOrderStockMovement.php app/Observers/OrderObserver.php app/Observers/
git add docs/T9-3-TRACKING.md
git add scripts/commit-marathon-final.sh

# Commit
git commit -m "feat: T7 dashboard fonds, T8 liste vinyles, T9 mouvements stock

- T7: Dashboard /fonds avec types miroir/doré, visuels, calculs montants
- T7: Actions +1/-1 avec intégration mouvements stock
- T8: Route /vinyles avec liste complète, colonnes, badges statut
- T8: Pagination et recherche rapide
- T9: Migration mouvements_stock avec enum types
- T9: Modèle MouvementStock avec scopes
- T9: Service StockMovementService pour cohérence
- T9: Controller + Vue historique avec filtres
- T9: Export CSV des mouvements
- T9: Intégration sidebar dashboard"

echo "✅ Commit effectué"
git log --oneline -3
