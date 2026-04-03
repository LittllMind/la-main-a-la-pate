#!/bin/bash
# Script de commit des corrections de tests T11

cd ~/vinyles-stock

echo "=== Correction des tests pour matcher le code actuel ==="
echo ""
echo "Modifications effectuées :"
echo "- factory MouvementStock corrigée (matching migration)"
echo "- VinyleControllerShowTest : supprimé tests route inexistante show"
echo "- Corrigé redirections client pour middleware role:admin,employe"
echo "- Corrigé route orders.my-orders -> orders.my"
echo ""

# Ajout des fichiers
git add database/factories/MouvementStockFactory.php
git add tests/Feature/Vinyles/VinyleControllerIndexTest.php
git add tests/Feature/Vinyles/VinyleControllerShowTest.php
git add tests/Feature/Vinyles/VinyleControllerActionsTest.php
git add tests/Feature/Fonds/FondControllerIndexTest.php
git add tests/Feature/Orders/OrderControllerIntegrationTest.php

# Commit
git commit -m "fix: corrections tests T11 pour matcher le code actuel

- factory MouvementStock alignée avec migration (produit_type, produit_id)
- VinyleControllerShowTest : simplifié (pas de route show)
- Redirections client corrigées pour middleware role
- Route orders corrigée : my-orders -> my
- FondControllerIndexTest : assertion redirection corrigée"

echo ""
echo "✅ Commit effectué !"
echo ""
git log --oneline -3