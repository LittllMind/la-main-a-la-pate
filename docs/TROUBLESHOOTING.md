# Troubleshooting Guide — vinyles-stock

Guide rapide pour diagnostiquer et résoudre les problèmes courants.

---

## Erreurs HTTP

### 500 — Internal Server Error

**Symptômes:** Page blanche avec "500" ou message générique

**Diagnostic:**
```bash
tail -n 50 storage/logs/laravel.log
```

**Causes courantes:**

| Cause | Log indique | Solution |
|-------|-------------|----------|
| Permission storage | "Failed to open stream: Permission denied" | `chmod -R 775 storage bootstrap/cache` |
| APP_KEY manquant | "No application encryption key has been specified" | `php artisan key:generate` |
| Migration manquante | "SQLSTATE[42S02]: Base table not found" | `php artisan migrate` |
| Config non cachée | — | `php artisan config:cache` |
| Memory limit | "Allowed memory size exhausted" | Augmenter `memory_limit` dans php.ini |

**Fix rapide:**
```bash
chmod -R 775 storage bootstrap/cache
php artisan cache:clear
php artisan config:cache
```

---

### 404 — Page Not Found

**Symptômes:** Route existe mais retourne 404

**Diagnostic:**
```bash
# Vérifier route existe
php artisan route:list | grep "votre-route"

# Vérifier URL rewrite
ls -la public/.htaccess  # Apache
nginx -t                 # Nginx
```

**Causes courantes:**

| Cause | Vérifier | Solution |
|-------|----------|----------|
| Route absente | `php artisan route:list` | Ajouter route web.php |
| mod_rewrite off | `apachectl -M 2>/dev/null \| grep rewrite` | Activer mod_rewrite |
| .htaccess manquant | `cat public/.htaccess` | Copier depuis .htaccess.example |
| Nginx config | `nginx -t` | Vérifier `try_files` |

**Nginx — try_files correct:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

### 403 — Forbidden

**Symptômes:** Accès refusé, souvent après upload

**Diagnostic:**
```bash
ls -la storage/app/public/
ls -la public/storage/
php artisan route:list --middleware | grep votre-route
```

**Causes courantes:**

| Cause | Solution |
|-------|----------|
| Rôle insuffisant | Vérifier middleware CheckRole + rôle user |
| Storage non linké | `php artisan storage:link` |
| Permissions dossier | `chown -R www-data:www-data storage` |
| CSRF token manquant | Ajouter `@csrf` dans formulaire |

---

### 419 — Page Expired (CSRF)

**Symptômes:** Formulaires échouent après inactivité

**Solution:** Rafraîchir la page ou ajouter dans `.env`:
```env
SESSION_LIFETIME=120  # minutes
```

Puis:
```bash
php artisan config:cache
```

---

## Base de données

### Connection refused

**Error:** `SQLSTATE[HY000] [2002] Connection refused`

**Diagnostic:**
```bash
# Vérifier si MySQL tourne
sudo systemctl status mysql
# ou
sudo systemctl status mariadb

# Tester connexion
mysql -u USERNAME -p -h HOSTNAME
```

**Solutions:**
1. MySQL arrêté: `sudo systemctl start mysql`
2. Mauvais host: Vérifier `DB_HOST` dans `.env` (localhost vs 127.0.0.1)
3. Port différent: Vérifier `DB_PORT`
4. Socket: Essayer `DB_SOCKET=/var/run/mysqld/mysqld.sock`

### Table not found

**Error:** `SQLSTATE[42S02]: Base table or view not found`

**Solution:**
```bash
php artisan migrate
# ou si migrations déjà passées
php artisan migrate:rollback --step=1
php artisan migrate
```

### Lock wait timeout

**Error:** `SQLSTATE[HY000]: General error: 1205 Lock wait timeout`

**Cause:** Transaction longue ou deadlock

**Solution:**
```bash
# Vérifier processus MySQL
mysql -u root -p -e "SHOW PROCESSLIST;"

# Kill process si nécessaire
mysql -u root -p -e "KILL {id};"
```

---

## Cache

### Changes not reflecting

**Symptômes:** Modifs code non visibles

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Puis re-cacher
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Cache not working

**Diagnostic:**
```bash
php artisan tinker
>>> cache()->put('test', 'value', 60);
>>> cache()->get('test');
```

**Si null:** Vérifier `CACHE_DRIVER` dans `.env`

**Redis:**
```bash
redis-cli ping
# PONG attendu

redis-cli
> KEYS "*"
> FLUSHALL  # Vider cache
```

---

## Performance

### Pages lentes (> 3s)

**Diagnostic:**
```bash
# Activer debugbar (dev uniquement)
composer require barryvdh/laravel-debugbar
```

Ou ajouter dans contrôleur:
```php\ndd(DB::getQueryLog()); // Dernière requête
```

**Causes courantes:**

| Symptôme | Cause | Solution |
|----------|-------|----------|
| Requêtes N+1 | `foreach` sans `with()` | Eager loading: `Vinyle::with(['media'])->get()` |
| Pas de pagination | `->get()` sur grande table | `->paginate(24)` |
| Images lentes | Conversion on-the-fly | Générer conversions: `php artisan media:regenerate` |
| Pas de cache | Requêtes répétées | `Cache::remember('key', 300, fn() => ...)` |

**Optimisation query:**
```php
// AVANT (N+1)
$vinyles = Vinyle::all();
foreach ($vinyles as $v) {
    echo $v->media->first()->url; // 1 query par vinyle
}

// APRÈS (eager loading)
$vinyles = Vinyle::with(['media'])->paginate(24);
```

---

## Authentification

### Login loop (redirection infinie)

**Symptômes:** Après login, retourne sur login

**Diagnostic:**
```bash
# Vérifier session
laravel debugbar → Session tab

# Vérifier cookies
curl -I http://site.com/login | grep Set-Cookie
```

**Solutions:**
1. **Domaine mismatch:** `SESSION_DOMAIN` doit matcher `APP_URL`
2. **Secure cookies en HTTP:** `SESSION_SECURE_COOKIE=false` en dev
3. **SameSite:** `SESSION_SAME_SITE=lax` par défaut

**Check .env:**
```env
APP_URL=http://localhost
SESSION_DOMAIN=.localhost
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

### Not authorized (403 après login)

**Diagnostic:**
```bash
php artisan tinker
>>> Auth::user()->role
>>> Auth::user()->can('view admin')
```

**Si rôle = null ou = 'client':**
```bash
# Attribuer rôle
php artisan tinker
>>> \App\Models\User::where('email', 'user@example.com')->update(['role' => 'admin']);
```

---

## Uploads / Media

### Images 404

**Diagnostic:**
```bash
ls -la public/storage/
ls -la storage/app/public/
```

**Solutions:**
1. **Lien symbolique manquant:** `php artisan storage:link`
2. **Permissions:** `chmod -R 755 storage/app/public`
3. **Nginx root:** Vérifier `root` pointe bien sur `/public`

### Upload fails

**Diagnostic log:**
```bash
tail storage/logs/laravel.log | grep -i "upload\|media"
```

**Causes:**

| Erreur | Cause | Solution |
|--------|-------|----------|
| `The cover failed to upload` | `post_max_size` / `upload_max_filesize` | Augmenter dans php.ini |
| `File not found` | Dossier temporaire | `chmod 777 /tmp` temporairement |
| `Disk [public] does not have a configured driver` | Config filesystem | Vérifier `config/filesystems.php` |

---

## Email (si configuré)

### Emails not sending

**Diagnostic:**
```bash
# Queue
tail storage/logs/laravel.log | grep -i "mail\|smtp"

# Test tinker
php artisan tinker
>>> Mail::raw('test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

**Check .env:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxx
MAIL_PASSWORD=yyy
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votresite.com
MAIL_FROM_NAME="Vinyl Stock"
```

---

## Ressources utiles

### Commandes de diagnostic rapide

```bash
# Health check complet
./scripts/health-check.sh --verbose

# Vérifier tout
php artisan route:clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear
composer dump-autoload

# Re-cacher
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Info système
php artisan about
php artisan route:list
```

### Logs à surveiller

```bash
# Laravel
tail -f storage/logs/laravel.log

# Nginx
sudo tail -f /var/log/nginx/error.log

# PHP-FPM
sudo tail -f /var/log/php*-fpm.log

# MySQL
sudo tail -f /var/log/mysql/error.log
```

### Support

- Documentation: `/docs/ARCHITECTURE.md`, `/docs/API.md`
- Issues GitHub: [littllmind/vinyles-stock/issues](https://github.com/littllmind/vinyles-stock/issues)
- Laravel Docs: [laravel.com/docs/11.x](https://laravel.com/docs/11.x)

---

*Dernière mise à jour: 2026-03-13*
