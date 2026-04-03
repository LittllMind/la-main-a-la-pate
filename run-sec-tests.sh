#!/bin/bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
php artisan test tests/Feature/Security/SecurityTest.php --colors=always 2>&1
