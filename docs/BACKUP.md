# 📦 Database Backup

Documentation complète pour le système de sauvegarde automatique Vinyls Stock.

---

## 🚀 Mise en place rapide

### Option 1 : Commande Artisan (Recommandée)

```bash
# Backup simple
php artisan backup:database

# Backup compressé (gzip)
php artisan backup:database --compress

# Conserver 14 jours d'historique
php artisan backup:database --keep=14

# Combinaison complète
php artisan backup:database --compress --keep=14
```

Les backups sont stockés dans : `storage/app/backups/`

---

### Option 2 : Script Bash

```bash
# Depuis la racine du projet
./scripts/backup-db.sh

# Conserver 30 jours
./scripts/backup-db.sh 30
```

Les backups sont stockés dans : `~/backups/vinyls-stock/`

---

## ⏰ Automatisation avec Cron

### Editer la crontab :
```bash
crontab -e
```

### Ajouter cette ligne (backup quotidien à 2h du matin) :
```
# Vinyls Stock - Backup quotidien à 2h00
0 2 * * * cd /chemin/vers/vinyles-stock && php artisan backup:database --compress --keep=7 >> /var/log/vinyls-backup.log 2>&1
```

Ou avec le script bash :
```
0 2 * * * /chemin/vers/vinyles-stock/scripts/backup-db.sh 7 >> /var/log/vinyls-backup.log 2>&1
```

---

## 📊 Fonctionnalités

| Feature | Artisan | Script Bash |
|---------|---------|-------------|
| Backup MySQL | ✅ | ✅ |
| Compression gzip | ✅ `--compress` | ✅ automatique |
| Cleanup auto | ✅ `--keep=N` | ✅ paramètre |
| Logs colorés | ✅ | ✅ |
| Multi-db support | MySQL uniquement | MySQL uniquement |
| Configuration .env | Auto-détéctée | Auto-détéctée |
| Stockage | `storage/app/backups/` | `~/backups/vinyls-stock/` |

---

## 🔒 Restauration d'un backup

```bash
# Décompresser si gzip
gunzip vinyls_stock_20260306_093045.sql.gz

# Restaurer
mysql -u root -p vinyls_stock < vinyls_stock_20260306_093045.sql
```

⚠️ **Attention** : La restauration écrase les données actuelles !

---

## 📁 Structure des backups

```
# Format de nommage
vinyls_stock_YYYYMMDD_HHMMSS.sql.gz

# Exemple
vinyls_stock_20260306_093045.sql.gz
```

---

## 🐛 Dépannage

### "Backup failed - check database credentials"
- Vérifier le fichier `.env`
- S'assurer que DB_USERNAME et DB_PASSWORD sont corrects

### Permission denied sur le script
```bash
chmod +x scripts/backup-db.sh
```

### Espace disque insuffisant
```bash
# Vérifier l'espace
df -h

# Nettoyer manuellement les vieux backups
find storage/app/backups -name "vinyls_stock_*.sql*" -mtime +30 -delete
```

---

## ✅ Vérification du backup

```bash
# Vérifier le contenu sans restaurer
zcat vinyls_stock_20260306_093045.sql.gz | head -20

# Ou si non compressé
head -20 vinyls_stock_20260306_093045.sql
```

---

**Créé le** : 2026-03-06
**Amélioration** : #1 - Daily autonomous sprint
