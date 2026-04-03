#!/bin/bash
# Commit T9.3 - OrderObserver + Traçage commandes

cd ~/vinyles-stock

git add \
  app/Observers/OrderObserver.php \
  app/Console/Commands/TestOrderStockMovement.php \
  app/Providers/EventServiceProvider.php \
  docs/T9-3-TRACKING.md

git commit -m "feat/T9.3: OrderObserver - traçage automatique des ventes et retours stock"

echo "✅ Commit T9.3 effectué !"
git log --oneline -3