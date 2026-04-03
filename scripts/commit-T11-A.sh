#!/bin/bash
# Script de commit T11-A : Configuration PHPUnit

cd ~/vinyles-stock

echo "📦 Commit T11-A : Configuration infrastructure tests"
echo ""

# Ajout des fichiers
git add phpunit.xml
git add database/factories/FondFactory.php
git add database/factories/OrderFactory.php
git add database/factories/OrderItemFactory.php
git add database/factories/MouvementStockFactory.php
git add tests/TestCase.php
git add tests/Feature/InfrastructureTest.php

# Commit
git commit -m "test/T11-A: Configuration infrastructure PHPUnit + factories

- phpunit.xml: activation SQLite in-memory
- FondFactory + OrderFactory + OrderItemFactory + MouvementStockFactory
- TestCase: helpers auth (admin/client/employe)
- InfrastructureTest: validation setup"

# Vérification
echo ""
echo "✅ Commit T11-A effectué"
git log --oneline -1
git status