#!/bin/bash
# Script pour exécuter les tests de sécurité T13.3

cd ~/vinyles-stock || exit 1

echo "========================================="
echo "T13.3 — Security Tests Execution"
echo "========================================="
echo ""

# Exécuter les tests avec sortie détaillée
php artisan test tests/Feature/Security/SecurityTest.php --no-ansi 2>&1 | tee /tmp/security-test-output.txt

EXIT_CODE=${PIPESTATUS[0]}

echo ""
echo "========================================="
echo "EXIT CODE: $EXIT_CODE"
echo "========================================="

if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ TOUS LES TESTS PASSENT"
else
    echo "❌ DES TESTS ONT ÉCHOUÉ"
    echo ""
    echo "Fichier de sortie: /tmp/security-test-output.txt"
fi

exit $EXIT_CODE
