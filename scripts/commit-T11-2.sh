#!/bin/bash
# Script de commit T11.2 - Tests FondsController adaptés au code existant

cd ~/workspace/vinyles-stock || exit 1

echo "📦 Commit T11.2 - Tests FondsController"
echo "========================================"

git add tests/Feature/Fonds/FondControllerActionsTest.php

git commit -m "test/T11.2: Tests FondController adaptés au code existant

- FondControllerActionsTest: 12 tests complets
- Actions stock: increment, decrement, set
- Permissions: admin only, employe denied, client redirected
- Mouvements stock automatiques vérifiés
- Validations: stock insuffisant, action invalide, quantite negative
- Adaptés au code existant sans modification source
- Route updatePrix inexistante → tests non créés (fonctionnalité absente)

Refs: T11.X"

echo ""
echo "✅ Commit T11.2 effectué"
