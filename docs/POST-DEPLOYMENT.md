# Post-Déploiement Checklist — vinyles-stock

Cette checklist doit être complétée manuellement après chaque déploiement en production.

---

## ☑️ Pré-déploiement

- [ ] **Backup SQL** créé: `mysqldump -u user -p db > backup-$(date +%Y%m%d).sql`
- [ ] **.env** sauvegardé: `cp .env .env.backup`
- [ ] Branch checkout: `git status` → on est sur `main`
- [ ] Pas de fichiers non commités: `git diff --cached --quiet`

---

## ☑️ Déploiement Code

- [ ] `git pull origin main` → pas de conflits
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build` → pas d'erreur build

---

## ☑️ Base de données

- [ ] `php artisan migrate --force` → migrations OK, pas d'erreur
- [ ] Tables vérifiées: `php artisan db:show`
- [ ] Seeders si nécessaire: `php artisan db:seed --class=X`

---

## ☑️ Cache & Optimisations

- [ ] `php artisan cache:clear`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`  
- [ ] `php artisan view:cache`
- [ ] `php artisan event:cache`

---

## ☑️ Permissions

- [ ] `chown -R www-data:www-data storage bootstrap/cache`
- [ ] `chmod -R 775 storage bootstrap/cache`
- [ ] `php artisan storage:link` (vérifier: `ls -la public/storage`)

---

## ☑️ Tests Post-Déploiement

### Smoke Tests

- [ ] **Homepage** (`/`) → charge sans erreur 500
- [ ] **Kiosque** (`/kiosque`) → vinyles affichés
- [ ] **Login** (`/login`) → page accessible
- [ ] **Admin** (`/admin`) → redirect login (non auth)

### API Health

```bash
curl -s http://localhost/health | jq .
# Attendu: {"status":"ok","db":"connected","cache":"connected"}
```

- [ ] Endpoint health check répond 200
- [ ] `db` = `connected`
- [ ] `cache` = `connected`

### Fonctionnel

- [ ] **Login** → redirection admin OK
- [ ] **Dashboard** → stats affichées
- [ ] **Kiosque** → images chargent (pas de 404)
- [ ] **Mode Marché** → ajout panier fonctionne
- [ ] **Export CSV** → téléchargement OK

---

## ☑️ Sécurité

- [ ] HTTPS forcé (vérifier: pas de HTTP accessible)
- [ ] Headers de sécurité présents:
  ```bash
  curl -I https://site.com | grep -E "X-Frame|X-Content|X-XSS"
  ```
- [ ] `APP_DEBUG=false` (vérifier `.env`)
- [ ] `APP_ENV=production` (vérifier `.env`)

---

## ☑️ Performance

- [ ] Pages se chargent en < 3s (test: Chrome DevTools)
- [ ] Pas d'erreur 504/timeout sur routes lentes
- [ ] Pagination fonctionne (pas de `get()` sans `paginate()`)

---

## ☑️ Monitoring

- [ ] Logs Laravel pas d'erreurs récentes: `tail -50 storage/logs/laravel.log`
- [ ] Logs Nginx pas d'erreurs 5xx: `tail -50 /var/log/nginx/error.log`
- [ ] Workers queue si utilisés: `systemctl status vinylstock-worker`

---

## ☑️ Rollback (si nécessaire)

Si problème critique → **STOP** et executer:

```bash
# 1. Restore DB
mysql -u user -p db < backup-YYYYMMDD.sql

# 2. Revert code
git reset --hard HEAD~1
git pull origin main

# 3. Clear caches
php artisan cache:clear
php artisan config:cache

# 4. Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

---

## ☑️ Sign-off

| Rôle | Nom | Signature | Date |
|------|-----|-----------|------|
| Déploiement | | | |
| Validation QA | | | |
| Go-live | | | |

---

## Notes

*Déploiement du: ___________*

*Problèmes rencontrés:*

*Actions post-déploiement:*

---

*Template version: 2026-03-13*
