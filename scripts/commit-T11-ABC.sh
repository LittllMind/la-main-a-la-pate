#!/bin/bash
# Commit T11-A/B/C : Infrastructure + Tests Fonds + Tests Vinyles

cd /home/aur-lien/vinyles-stock

git add -A

git commit -m "T11-A/B/C: Infrastructure tests + tests Fonds + tests Vinyles

- Configuration PHPUnit avec SQLite in-memory
- Factories: Fond, Vinyle, Order, OrderItem, MouvementStock
- Helpers TestCase: adminUser, employeUser, clientUser, actingAsUser
- Tests Fonds: permissions, actions +/-, calculs montants
- Tests Vinyles: index (pagination, filtres, recherche), actions CRUD, sécurité
- InfrastructureTest: validation du setup

Note: Tests nécessitent extension SQLite PHP pour exécution"

echo "✅ Commit T11-A/B/C effectué !"
git log --oneline -1
