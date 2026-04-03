#!/bin/bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
php artisan test tests/Feature/ModeMarche/ --testdox --colors=never 2>&1