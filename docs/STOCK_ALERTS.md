# 🚨 Stock Alert System - Documentation

## 📋 Description
Système complet de gestion des alertes de stock pour vinyles et fonds.

## 🏗️ Architecture

### Modèle (`StockAlert`)
- Relation polymorphique `alertable` → `Vinyle` ou `Fond`
- Champs : `quantite_actuelle`, `seuil_alerte`, `statut` (actif/resolu)
- Notifications avec `derniere_notification_envoyee`

### Contrôleur (`StockAlertController`)
| Méthode | Route | Description |
|---------|-------|-------------|
| `index()` | GET `/stock-alerts` | Dashboard alertes actives |
| `history()` | GET `/stock-alerts/history` | Alertes résolues |
| `resolve()` | PATCH `/stock-alerts/{id}/resolve` | Marquer résolu |
| `store()` | POST `/stock-alerts` | Création manuelle |

### Commande Artisan
```bash
php artisan stock:check-alerts
```
Vérifie les stocks et crée automatiquement les alertes.

## 🎨 Features UI

### Vue Index (`stock-alerts/index.blade.php`)
- Cartes récapitulatives (rupture/faible/actives)
- Grille visuelle des articles en rupture
- Grille visuelle des stocks faibles
- Tableau paginé de toutes les alertes
- Design violet/rose cohérent

### Vue History (`stock-alerts/history.blade.php`)
- Liste des alertes résolues
- Pagination
- Navigation retour vers actives

## 🔐 Accès
- **Rôles** : Admin et Employé (`role:admin,employe`)
- **Route dans groupe protégé** dans `web.php`

## 🧪 Test
```bash
# Vérifier les routes
php artisan route:list | grep stock-alert

# Créer des alertes
php artisan stock:check-alerts

# Accéder à l'interface
http://localhost:8000/stock-alerts
```

## 📁 Fichiers créés/modifiés
- `app/Http/Controllers/StockAlertController.php`
- `app/Console/Commands/CheckStockAlerts.php`
- `resources/views/stock-alerts/index.blade.php`
- `resources/views/stock-alerts/history.blade.php`
- `docs/STOCK_ALERTS.md` (ce fichier)
