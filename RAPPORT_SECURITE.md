# 📊 Rapport de Sécurité - Vinyles Stock

**Date** : 2026-03-05
**Version** : 1.0
**Auteur** : PicoClaw 🦞

---

## ✅ Corrections Appliquées

### 1. 🔧 Middleware CheckUserRole amélioré

**Fichier** : `app/Http/Middleware/CheckUserRole.php`

**Améliorations** :
- ✅ Support de plusieurs rôles (séparés par virgule)
- ✅ Message d'erreur plus clair avec le rôle requis
- ✅ Code 401 pour non-authentifié (au lieu de 403)
- ✅ Documentation ajoutée

**Exemple d'utilisation** :
```php
// Un seul rôle
Route::get('/admin', [Controller::class, 'index'])
    ->middleware('role:admin');

// Plusieurs rôles
Route::get('/kiosque', [Controller::class, 'index'])
    ->middleware('role:employe,admin');
```

---

### 2. 🛡️ Sécurisation des Routes

**Fichier** : `routes/web.php`

**Hiérarchie mise en place** :

| Groupe | Middleware | Routes |
|--------|-----------|--------|
| **Admin** | `auth + role:admin` | `/vinyles/*`, `/stats`, `/fonds/*`, `/ventes/*` |
| **Employé** | `auth + role:employe,admin` | `/kiosque/*` |
| **Client** | `public` ou `auth` | `/cart/*`, `/orders/create` |

**Routes sécurisées** :
```php
// Admin uniquement
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('vinyles', VinyleController::class);
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
    Route::resource('fonds', FondController::class)->only(['index', 'update']);
    Route::resource('ventes', VenteController::class);
});

// Employé ou Admin
Route::middleware(['auth', 'role:employe,admin'])->prefix('kiosque')->name('kiosque.')->group(function () {
    Route::get('/', [VinyleController::class, 'kiosque'])->name('index');
    Route::post('/vendre', [VenteController::class, 'storeFromKiosque'])->name('vendre');
});

// Client (public ou authentifié)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    // ...
});

Route::get('/orders/create', [OrderController::class, 'create'])
    ->middleware('auth')
    ->name('orders.create');
```

---

### 3. 🆕 Helper RoleHelper

**Fichier** : `app/Helpers/RoleHelper.php`

**Méthodes disponibles** :
```php
use App\Helpers\RoleHelper;

// Vérifier un rôle
RoleHelper::hasRole('admin');
RoleHelper::hasRole(['admin', 'employe']);

// Vérifier un rôle spécifique
RoleHelper::isAdmin();           // admin uniquement
RoleHelper::isEmployeOrAdmin();  // employe ou admin
RoleHelper::isClient();          // client uniquement

// Obtenir le rôle actuel
RoleHelper::getCurrentRole();

// Vérifier si un rôle est valide
RoleHelper::isValidRole('admin');

// Lister les rôles disponibles
RoleHelper::getAvailableRoles();
```

---

### 4. 👤 Modèle User amélioré

**Fichier** : `app/Models/User.php`

**Modifications** :
- ✅ Ajout de `role` dans `$fillable`
- ✅ Méthodes de vérification des rôles
- ✅ Méthode pour obtenir le libellé du rôle

**Méthodes ajoutées** :
```php
$user->hasRole('admin');
$user->isAdmin();
$user->isEmployeOrAdmin();
$user->isClient();
$user->getRoleLabel();  // "Administrateur"
User::getAvailableRoles(); // ['admin' => 'Administrateur', ...]
```

---

### 5. 🔄 Migration de mise à jour des rôles

**Fichier** : `database/migrations/2026_03_05_000001_update_user_roles.php`

**Actions** :
- ✅ Met à jour les utilisateurs sans rôle vers `client`
- ✅ Le premier utilisateur devient `admin` (s'il n'y en a pas)
- ✅ Corrige les rôles invalides vers `client`

---

### 6. ⚙️ Commande Artisan de gestion des rôles

**Fichier** : `app/Console/Commands/ManageUserRoles.php`

**Utilisation** :

```bash
# Lister tous les utilisateurs
php artisan user:role list

# Modifier le rôle d'un utilisateur
php artisan user:role set --email=aurelien@example.com --role=admin

# Créer un utilisateur
php artisan user:role create \
  --email=new@example.com \
  --name="Nouvel Utilisateur" \
  --password="password123" \
  --role=employe
```

---

### 7. 📚 Documentation

**Fichiers créés** :
- `SECURITE_ROLES.md` - Documentation complète de la sécurité des rôles
- `RAPPORT_SECURITE.md` - Ce rapport

---

## 🎯 Hiérarchie des Rôles

```
┌─────────────────────────────────────────────┐
│              ADMIN (accès complet)          │
│  • CRUD Vinyles                             │
│  • Stats & Rapports                         │
│  • Gestion des fonds                        │
│  • Gestion des ventes                       │
│  • Accès kiosque                            │
└─────────────────────────────────────────────┘
                    ▲
                    │
┌─────────────────────────────────────────────┐
│           EMPLOYE (accès kiosque)           │
│  • Kiosque de vente                         │
│  • Vente rapide                             │
│  • Consultation catalogue                   │
└─────────────────────────────────────────────┘
                    ▲
                    │
┌─────────────────────────────────────────────┐
│          CLIENT (accès public)              │
│  • Panier public                            │
│  • Consultation catalogue                   │
│  • Commande (authentifié)                   │
└─────────────────────────────────────────────┘
```

---

## 📋 Checklist de Sécurité

### ✅ Routes
- [x] Routes admin protégées par `role:admin`
- [x] Routes employé protégées par `role:employe,admin`
- [x] Routes client accessibles (public ou auth)
- [x] Messages d'erreur clairs

### ✅ Middleware
- [x] CheckUserRole amélioré
- [x] Support de plusieurs rôles
- [x] Messages d'erreur explicites

### ✅ Modèles
- [x] User avec méthodes de vérification
- [x] RoleHelper pour les contrôleurs

### ✅ Base de données
- [x] Migration de mise à jour des rôles
- [x] Valeurs par défaut correctes

### ✅ Documentation
- [x] Guide d'utilisation des rôles
- [x] Exemples de code
- [x] Bonnes pratiques

---

## 🚀 Prochaines étapes recommandées

### 1. Tester la sécurité
```bash
# Exécuter les migrations
php artisan migrate

# Lister les utilisateurs
php artisan user:role list

# Créer un admin si nécessaire
php artisan user:role create \
  --email=admin@example.com \
  --name="Admin" \
  --password="admin123" \
  --role=admin
```

### 2. Ajouter des Policies
Créer des policies pour les modèles sensibles :
```bash
php artisan make:policy VinylePolicy --model=Vinyle
```

### 3. Logger les accès non autorisés
Ajouter des logs dans le middleware :
```php
use Illuminate\Support\Facades\Log;

Log::warning('Accès non autorisé', [
    'user' => auth()->user()?->email,
    'route' => $request->route()->getName(),
    'required_role' => $roles,
]);
```

### 4. Tests unitaires
Créer des tests pour vérifier la sécurité :
```bash
php artisan make:test RoleMiddlewareTest
php artisan make:test UserRoleTest
```

---

## ⚠️ Points d'attention

1. **Ne pas se fier uniquement aux routes** : Toujours vérifier le rôle dans les contrôleurs
2. **Validation côté serveur** : Ne jamais faire confiance aux données du formulaire
3. **Mots de passe forts** : Exiger des mots de passe robustes
4. **HTTPS en production** : Toujours utiliser HTTPS
5. **Logs d'audit** : Envisagez de logger les actions sensibles

---

## 📞 Support

Pour toute question ou problème :
1. Consultez `SECURITE_ROLES.md`
2. Vérifiez les logs Laravel (`storage/logs/laravel.log`)
3. Testez avec la commande `php artisan user:role list`

---

**Statut** : ✅ Sécurité renforcée
**Actions requises** : Exécuter `php artisan migrate` et tester