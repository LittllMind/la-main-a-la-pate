# Guide de Déploiement — vinyles-stock

## Environnement Requis

| Service | Version | Notes |
|---------|---------|-------|
| PHP | 8.3+ | extensions: mbstring, xml, mysql, gd, redis |
| MySQL | 8.0+ | ou MariaDB 10.6+ |
| Node.js | 20+ | Pour build assets |
| Composer | 2.x | Latest |
| Redis | 7+ | Optionnel mais recommandé |
| Nginx | 1.24+ | ou Apache |

## Checklist Déploiement

### 1. Préparation serveur

```bash
# Créer utilisateur dédié
sudo useradd -m -s /bin/bash vinylstock
sudo usermod -aG www-data vinylstock

# Structurer dossiers
sudo -u vinylstock mkdir -p /var/www/vinylstock
cd /var/www/vinylstock
```

### 2. Déploiement code

```bash
# Cloner repo
sudo -u vinylstock git clone https://github.com/littllmind/vinyles-stock.git .
cd vinyles-stock

# Installer dépendances
sudo -u vinylstock composer install --no-dev --optimize-autoloader
sudo -u vinylstock npm ci
sudo -u vinylstock npm run build
```

### 3. Configuration environnement

```bash
# Copier env
cp .env.example .env

# Générer clé
sudo -u vinylstock php artisan key:generate

# Configurer .env
APP_NAME="Vinyl Stock"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vinyles.votredomaine.com

DB_HOST=localhost
DB_DATABASE=vinylstock
DB_USERNAME=vinylstock_user
DB_PASSWORD=<STRONG_PASSWORD>

# Cache & Session (recommandé Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

# Filesystem
FILESYSTEM_DISK=local
# ou AWS S3:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=...
# AWS_SECRET_ACCESS_KEY=...
# AWS_DEFAULT_REGION=eu-west-3
# AWS_BUCKET=vinylstock-media
```

### 4. Base de données

```bash
# Créer DB et user
mysql -u root -p
CREATE DATABASE vinylstock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vinylstock_user'@'localhost' IDENTIFIED BY '<STRONG_PASSWORD>';
GRANT ALL PRIVILEGES ON vinylstock.* TO 'vinylstock_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Migrer
sudo -u vinylstock php artisan migrate --force

# Seed initial (optionnel)
sudo -u vinylstock php artisan db:seed --class=AdminUserSeeder
```

### 5. Permissions

```bash
cd /var/www/vinylstock/vinyles-stock

# Ownership
sudo chown -R vinylstock:www-data .

# Dossiers écriture web
sudo chown -R www-data:www-data storage/bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Media uploads
sudo mkdir -p storage/app/public/media
sudo chown -R www-data:www-data storage/app/public
sudo chmod -R 775 storage/app/public

# Lien symbolique
sudo -u vinylstock php artisan storage:link
```

### 6. Configuration Nginx

```nginx
# /etc/nginx/sites-available/vinylstock
server {
    listen 80;
    server_name vinyles.votredomaine.com;
    root /var/www/vinylstock/vinyles-stock/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache assets statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/vinylstock /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Let's Encrypt (HTTPS)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d vinyles.votredomaine.com
```

### 8. Queue Worker (optionnel)

```bash
# Si jobs async ajoutés:
sudo nano /etc/systemd/system/vinylstock-worker.service

[Unit]
Description=VinylStock Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/vinylstock/vinyles-stock/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target

# Activer
sudo systemctl enable vinylstock-worker
sudo systemctl start vinylstock-worker
```

### 9. Cron (scheduled tasks)

```bash
sudo crontab -u www-data -e

# Rapport mensuel automatique (1er du mois à 9h)
0 9 1 * * cd /var/www/vinylstock/vinyles-stock && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 10. Tests post-déploiement

```bash
# Vérifier routes
sudo -u vinylstock php artisan route:cache

# Optimiser
sudo -u vinylstock php artisan config:cache
sudo -u vinylstock php artisan view:cache
sudo -u vinylstock php artisan event:cache

# Health check
curl -I https://vinyles.votredomaine.com
# HTTP 200 OK attendu
```

## Mise à jour (déploiement continu)

```bash
#!/bin/bash
# deploy.sh — À adapter

cd /var/www/vinylstock/vinyles-stock

# Backup DB
mysqldump -u vinylstock_user -p vinylstock > backup-$(date +%Y%m%d).sql

# Pull
git pull origin main

# Dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Migrations
php artisan migrate --force

# Clear & recache caches
php artisan cache:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache

# Permissions
chown -R www-data:www-data storage bootstrap/cache

# Restart queue si utilisé
# sudo systemctl restart vinylstock-worker

echo "✅ Déploiement terminé"
```

## Rollback

```bash
cd /var/www/vinylstock/vinyles-stock

# Restaurer DB
mysql -u vinylstock_user -p vinylstock < backup-YYYYMMDD.sql

# Revert code
git reset --hard HEAD~1
git pull origin main

# Clear caches
php artisan cache:clear
php artisan config:cache
```

## Monitoring

### Logs
```bash
# Laravel
tail -f storage/logs/laravel.log

# Nginx
sudo tail -f /var/log/nginx/vinylstock-error.log

# PHP-FPM
sudo tail -f /var/log/php8.3-fpm.log
```

### Health Check Endpoint
```bash
# À créer si besoin
GET /health
→ {"status":"ok","db":"connected","cache":"connected"}
```

### Alertes recommandées
- Espace disque > 80%
- MySQL connections > 80%
- 5xx errors > 10/min

---

*Dernière mise à jour: 2026-03-13*
