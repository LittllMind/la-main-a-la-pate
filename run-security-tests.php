<?php
// Script d'exécution des tests pour T13.3
echo "=== EXÉCUTION TESTS SECURITY ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Exécuter les tests sous phpunit
$command = 'cd ' . __DIR__ . ' && php artisan test tests/Feature/Security/SecurityTest.php 2>&1';
echo "Commande: $command\n\n";
echo "Sortie:\n";
echo shell_exec($command);
echo "\n=== FIN ===\n";
