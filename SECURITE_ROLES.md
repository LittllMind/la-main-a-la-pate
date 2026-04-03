# Sécurité des Rôles - Vinyles Stock

## 📊 Hiérarchie des Rôles

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

## 🔐 Protection des Routes

### Routes Admin (Rôle: `admin`)
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('vinyles', VinyleController::class);
    Route::get('/stats', [StatsController::class, 'index']);
    Route::resource('fonds', FondController::class);
    Route::resource('ventes', VenteController::class);
});
```

### Routes Employé (Rôle: `employe` ou `admin`)
```php
Route::middleware(['auth', 'role:employe,admin'])->group(function () {
    Route::prefix('kiosque')->group(function () {
        Route::get('/', [VinyleController::class, 'kiosque']);
        Route::post('/vendre', [VenteController::class, 'storeFromKiosque']);
    });
});
```

### Routes Client (Public ou Authentifié)
```php
// Public
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'add']);
    // ...
});

// Authentifié
Route::get('/orders/create', [OrderController::class, 'create'])
    ->middleware('auth');
```

## 🛡️ Middleware CheckUserRole

Le middleware supporte maintenant **plusieurs rôles** :

```php
// Un seul rôle
Route::get('/admin', [Controller::class, 'index'])
    ->middleware('role:admin');

// Plusieurs rôles
Route::get('/kiosque', [Controller::class, 'index'])
    ->middleware('role:employe,admin');
```

## 🔧 Helper RoleHelper

Utilisez le helper pour vérifier les rôles dans vos contrôleurs :

```php
use App\Helpers\RoleHelper;

// Vérifier si admin
if (RoleHelper::isAdmin()) {
    // Code admin
}

// Vérifier si employé ou admin
if (RoleHelper::isEmployeOrAdmin()) {
    // Code employé
}

// Vérifier un rôle spécifique
if (RoleHelper::hasRole(['admin', 'employe'])) {
    // Code autorisé
}

// Obtenir le rôle actuel
$role = RoleHelper::getCurrentRole();
```

## 📋 Rôles Disponibles

| Rôle | Description | Accès |
|------|-------------|-------|
| `admin` | Administrateur | Tout |
| `employe` | Employé | Kiosque + Catalogue |
| `client` | Client | Panier public + Commandes |

## ⚠️ Bonnes Pratiques

### 1. Toujours vérifier le rôle dans les contrôleurs
```php
public function destroy($id)
{
    if (!RoleHelper::isAdmin()) {
        abort(403, 'Accès réservé aux administrateurs');
    }

    // Code de suppression
}
```

### 2. Utiliser les policies pour les modèles
```php
// app/Policies/VinylePolicy.php
class VinylePolicy
{
    public function update(User $user)
    {
        return RoleHelper::isAdmin();
    }

    public function delete(User $user)
    {
        return RoleHelper::isAdmin();
    }
}
```

### 3. Protéger les formulaires côté serveur
```php
// Dans le contrôleur
public function store(Request $request)
{
    if (!RoleHelper::isAdmin()) {
        abort(403);
    }

    // Validation et création
}
```

## 🚨 Points d'Attention

1. **Ne pas se fier uniquement aux routes** : Toujours vérifier le rôle dans les contrôleurs
2. **Messages d'erreur clairs** : Le middleware renvoie un message 403 avec le rôle requis
3. **Validation côté serveur** : Ne jamais faire confiance aux données du formulaire
4. **Logs d'accès** : Envisagez de logger les accès non autorisés

## 📝 Migration des Utilisateurs

Pour assigner un rôle à un utilisateur :

```php
use App\Models\User;

// Créer un admin
User::create([
    'name' => 'Aurélien',
    'email' => 'aurelien@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
]);

// Créer un employé
User::create([
    'name' => 'Employé',
    'email' => 'employe@example.com',
    'password' => bcrypt('password'),
    'role' => 'employe',
]);

// Créer un client
User::create([
    'name' => 'Client',
    'email' => 'client@example.com',
    'password' => bcrypt('password'),
    'role' => 'client',
]);
```

## 🔍 Tests de Sécurité

### Test 1 : Accès admin
```bash
# Connexion admin
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Accès aux vinyles (devrait fonctionner)
curl http://localhost/vinyles -H "Authorization: Bearer TOKEN"
```

### Test 2 : Accès employé
```bash
# Connexion employé
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"email":"employe@example.com","password":"password"}'

# Accès aux vinyles (devrait échouer 403)
curl http://localhost/vinyles -H "Authorization: Bearer TOKEN"

# Accès au kiosque (devrait fonctionner)
curl http://localhost/kiosque -H "Authorization: Bearer TOKEN"
```

### Test 3 : Accès client
```bash
# Accès au panier (devrait fonctionner sans auth)
curl http://localhost/cart

# Accès aux vinyles (devrait échouer 401)
curl http://localhost/vinyles
```

---

**Mise à jour** : 2026-03-05
**Version** : 1.0
**Auteur** : PicoClaw 🦞