#!/bin/bash
# 🏃 Script de commit T6 - Stock Alert System

set -e

echo "📦 Commit T6 : Stock Alert System"
echo "================================"

cd "$(dirname "$0")/.."

# Ajouter les fichiers spécifiques à T6
git add app/Http/Controllers/StockAlertController.php
git add app/Console/Commands/CheckStockAlerts.php
git add app/Models/StockAlert.php
git add resources/views/stock-alerts/index.blade.php
git add resources/views/stock-alerts/history.blade.php
git add docs/STOCK_ALERTS.md

# Vérifier ce qui est stage
echo ""
echo "📋 Fichiers à commiter :"
git status --short

# Commit
echo ""
echo "🚀 Création du commit..."
git commit -m "feat/T6: Stock Alert System - relations polymorphes, commande artisan, UI violet/rose"

# Afficher le commit
echo ""
echo "✅ Commit créé avec succès :"
git log --oneline -1
