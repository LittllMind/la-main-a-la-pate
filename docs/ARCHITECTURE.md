# Architecture Technique — vinyles-stock

## Vue d'ensemble

Application Laravel 11 + Vue.js 3 pour gestion de stock de vinyles avec mode marché (caisse/emport).

## Stack Technique

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8.3, Laravel 11 |
| Frontend | Vue 3, Inertia.js, Tailwind CSS, Vite |
| Base de données | MySQL 8.0 (prod), SQLite (tests) |
| Cache | Redis (optionnel) |
| File Storage | Local / S3 (configurable) |

## Structure des dossiers

```
vinyles-stock/
├── app/
│   ├── Http/Controllers/      → Contrôleurs API + Web
│   │   ├── Admin/            → AdminVinylController, UserController...
│   │   ├── ModeMarcheController.php  → Caisse/emport
│   │   └── KiosqueController.php       → Affichage public
│   ├── Models/               → Eloquent + Relations
│   │   ├── Vinyle.php
│   │   ├── Order.php
│   │   └── User.php
│   ├── Services/             → Logique métier
│   │   ├── CartService.php
│   │   └── StockService.php
│   ├── Observers/            → Hooks modèles
│   └── Policies/             → Autorisations RBAC
├── database/
│   ├── migrations/
│   ├── factories/            → Fixtures tests
│   └── seeders/
├── resources/
│   ├── views/                → Blade (admin)
│   └── js/                   → Vue + Inertia
├── routes/
│   ├── web.php               → Routes web + admin
│   └── api.php               → API JSON
└── tests/
    ├── Feature/              → Tests HTTP intégration
    └── Unit/                 → Tests unitaires
```

## Flux de données principaux

### 1. Mode Kiosque (Public)
```
Client → KiosqueController::index() → Vinyle::with(['media'])
                                      → paginate(24)
                                      → Vue + Inertia
```

### 2. Mode Marché (Caisse)
```
Employé → ModeMarcheController
    ├── index()    → Panier actif (session)
    ├── store()    → Créer vente + décrémenter stock
    ├── cancel()   → Soft delete + restock
    ├── ventesJour() → Liste JSON (pour affichage/ajax)
    └── export()   → CSV téléchargeable
```

### 3. Administration
```
Admin → AdminVinylController (CRUD vinyles)
     → UserController (CRUD users, RBAC)
     → DashboardController (stats, rapports)
     → ReportController (exports PDF/CSV)
```

## Modèle de sécurité (RBAC)

| Rôle | Permissions |
|------|-------------|
| `admin` | Tout |
| `employe` | Kiosque, Mode Marché, Dashboard |
| `client` | Kiosque uniquement (lecture) |

Middleware : `CheckRole` (app/Http/Middleware/CheckRole.php)
- Redirection `kiosque.index` si accès refusé
- Message flash : `session(['error' => '...'])`

## Points d'API JSON

| Endpoint | Méthode | Auth | Description |
|----------|---------|------|-------------|
| `/kiosque/api/vinyls` | GET | Non | Liste vinyles paginée |
| `/admin/marche/ventes-jour` | GET | Oui (admin/employé) | Ventes JSON |
| `/admin/marche/{order}/cancel` | POST | Oui (admin/employé) | Annulation + restock |
| `/admin/marche/export` | GET | Oui (admin/employé) | Export CSV |
| `/admin/api/dashboard` | GET | Oui (admin/employé) | Stats JSON |

## Events & Listeners

Aucun event custom — logique directe dans :
- Services (CartService, StockService)
- Observers (décrément stock après création Order)

## Gestion des fichiers (Spatie Media Library)

```php
// Upload couverture vinyle
$vinyle->addMedia($file)
       ->toMediaCollection('cover');

// Accès
$vinyle->getFirstMediaUrl('cover');
```

Conversions : `thumb` (300x300), `preview` (600x600)

## Cache (Redis recommandé en prod)

```php
// Cache stats dashboard (TTL: 5min)
Cache::remember('dashboard.stats', 300, function () {
    return calculateStats();
});

// Invalidate
Cache::forget('dashboard.stats');
```

## Queue Jobs

Aucun job async actuellement — tout est synchrone.

Potentiels jobs futurs :
- `GenerateMonthlyReport` (PDF lourd)
- `SendRestockAlert` (notification)

---

*Document généré le 2026-03-13*
