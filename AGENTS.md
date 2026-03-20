# AGENTS.md - Règles Techniques Bougies-Stock

## 🛠️ Stack Technique

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8.3 + Laravel 11 |
| Frontend | Vue.js 3 + Tailwind CSS |
| Base de données | MySQL 8.0 |
| Tests | PHPUnit |
| Build | Vite (npm run dev) |
| Versioning | Git |

## 📁 Structure Projet

```
~/workspace/bougies-stock/
├── app/
│   ├── Models/           # Bougie, Fond, User, etc.
│   ├── Http/
│   │   ├── Controllers/  # BougieController, etc.
│   │   └── Middleware/
│   └── Observers/
├── database/
│   ├── migrations/       # Création tables
│   ├── factories/        # Données test
│   └── seeders/        # Données initiales
├── resources/
│   └── views/            # Blade templates
├── routes/
│   └── web.php           # Routes application
├── tests/
│   └── Feature/          # Tests fonctionnels
└── .env                  # Config locale
```

## 🔄 Commandes Essentielles

### Laravel
```bash
php artisan serve                    # Serveur local http://127.0.0.1:8000
php artisan migrate:fresh --seed     # Reset BDD + seeders
php artisan test                     # Lancer tous les tests
php artisan test --filter=NomTest    # Test spécifique
php artisan make:migration nom_migration
php artisan make:model NomModel
php artisan make:controller NomController --resource
php artisan make:test NomTest
```

### Git
```bash
git checkout -b feature/T-X.Y-nom    # Nouvelle branche
git add .
git commit -m "T-X.Y: Description"
git checkout main
git merge feature/T-X.Y-nom
git push origin main
git branch -d feature/T-X.Y-nom      # Supprimer branche locale
```

### Frontend
```bash
npm run dev                          # Dev server + hot reload
npm run build                        # Build production
```

## 🧪 TDD - Règles

### 1. Test d'abord
```php
// Test d'abord
public function test_peut_creer_bougie()
{
    $response = $this->post('/bougies', [
        'reference' => 'BOUG-001',
        'parfum' => 'Vanille',
        // ...
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('bougies', ['reference' => 'BOUG-001']);
}
```

### 2. Code minimal
```php
// Puis code minimal pour faire passer le test
public function store(Request $request)
{
    $validated = $request->validate([
        'reference' => 'required|unique:bougies',
        'parfum' => 'required',
        // ...
    ]);
    
    Bougie::create($validated);
    return response()->json(null, 201);
}
```

### 3. Refactor si nécessaire
Une fois test vert, améliorer le code si besoin.

## 🗄️ Modèle de Données

### Table `bougies`
```php
Schema::create('bougies', function (Blueprint $table) {
    $table->id();
    $table->string('reference')->unique();
    $table->string('parfum');           // was: artiste
    $table->string('nom');              // was: titre
    $table->string('collection')->nullable();  // was: album
    $table->string('format')->nullable();      // was: annee (120g/200g/300g)
    $table->string('type_cire')->nullable();   // was: genre (soja/paraffine)
    $table->integer('temps_brulure')->nullable(); // minutes
    $table->text('notes')->nullable();   // notes olfactives
    $table->decimal('prix', 10, 2);
    $table->integer('quantite')->default(0);
    $table->integer('seuil_alerte')->default(5);
    $table->timestamps();
});
```

### Relations
- `Bougie` hasMany `LigneVente`
- `Bougie` hasMany `CartItem`
- `Bougie` hasMany `OrderItem`
- `Bougie` morphMany `MouvementStock`
- `Bougie` morphMany `StockAlert`

## 🎨 Charte Graphique

| Élément | Valeur |
|---------|--------|
| Primaire | `#D4AF37` (Or chaud) |
| Secondaire | `#F5F5DC` (Blanc cassé) |
| Accent | `#228B22` (Vert nature) |
| Texte | `#333333` (Gris anthracite) |

## 🚫 Anti-patterns à éviter

- ❌ Pas de logique dans les views (Blade)
- ❌ Pas de requêtes N+1 (utiliser eager loading)
- ❌ Pas de validation inline (FormRequest)
- ❌ Pas de référence à d'autres projets (même en commentaire)
- ❌ Pas de sous-agent pour les tests

## ✅ Bonnes pratiques

- ✅ Un controller = une ressource
- ✅ Un test = une assertion principale
- ✅ Des noms explicites (pas `test1`, `test2`)
- ✅ Des commits atomiques (une chose = un commit)
- ✅ Des messages de commit clairs

## 🔧 Debug

```bash
# Logs
tail -f storage/logs/laravel.log

# Tinker (console interactive)
php artisan tinker
>>> Bougie::first();
>>> Bougie::count();

# Route list
php artisan route:list | grep bougie

# Cache clear (si problème config)
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## 📊 Validation Tests

Avant de dire "tests verts", vérifier:
- [ ] `php artisan test` retourne 0 failures
- [ ] Pas d'erreurs PHP (warnings OK si justifié)
- [ ] Routes accessibles (si applicable)
- [ ] BDD cohérente (migrations tournent)

---
*Référence technique pour Da*
*Dernière mise à jour: 2026-03-20*
