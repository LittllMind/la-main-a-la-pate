#!/bin/bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
php artisan test tests/Feature/Security/SecurityTest.php --colors=never 2>&1 > /home/aur-lien/.picoclaw/workspace/vinyles-storage-test-results.txt
echo "Exit code: $?" >> /home/aur-lien/.picoclaw/workspace/vinyles-storage-test-results.txt
