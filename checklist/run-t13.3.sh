#!/bin/bash
# Script de test T13.3 - Security Tests

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

echo "=== T13.3 - Exécution des tests de sécurité ==="
echo "Date: $(date)"
echo ""

# Exécuter les tests avec output détaillé
php artisan test tests/Feature/Security/SecurityTest.php --colors=never 2>&1

exit_code=$?

echo ""
echo "=== Fin des tests ==="
echo "Exit code: $exit_code"

exit $exit_code
