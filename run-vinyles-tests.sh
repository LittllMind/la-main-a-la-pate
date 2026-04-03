#!/bin/bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
php artisan test tests/Feature/Vinyles/ --colors=never 2>&1
