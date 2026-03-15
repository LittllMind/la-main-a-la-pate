#!/bin/bash
# Script de commit T9.2 - Service + Observers

cd $(dirname "$0")/..

git add app/Services/StockMovementService.php
git add app/Observers/VinyleObserver.php
git add app/Observers/FondObserver.php
git add app/Providers/EventServiceProvider.php
git add app/Console/Commands/TestStockMovement.php
git add HEARTBEAT.md MARATHON.md

git commit -m "T9.2: StockMovementService + Observers Vinyle/Fond + commande test"

echo "✅ Commit effectué !"
git log --oneline -3
