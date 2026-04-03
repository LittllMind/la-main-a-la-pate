# Procédure : Migration Soft Delete pour LigneVentes

## Contexte
Problème : La suppression d'un vinyle avec `onDelete('cascade')` efface l'historique des ventes.  
Solution : Passer à `onDelete('set null')` + ajouter un snapshot du titre du vinyle.

---

## 🎯 Objectifs

- [ ] Créer une migration pour modifier la contrainte (cascade → set null)
- [ ] Ajouter une colonne `titre_vinyle` en snapshot (pour garde-fou si vinyle supprimé)
- [ ] Mettre à jour le modèle `LigneVente` avec le nouveau champ
- [ ] Tester avec 3 scénarios (voir Validation)

---

## 📋 Étapes

### 1. Créer la migration

```bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
php artisan make:migration update_ligne_ventes_vinyle_foreign --table=ligne_ventes
```

### 2. Contenu de la migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            // Étape 1 : Ajouter colonne snapshot (si pas existante)
            if (!Schema::hasColumn('ligne_ventes', 'titre_vinyle')) {
                $table->string('titre_vinyle')->nullable()->after('vinyle_id');
            }
        });

        // Étape 2 : Supprimer puis recréer la contrainte (MySQL/MariaDB)
        // NOTE : Pour SQLite, cette section doit être adaptée
        
        // --- Option A : MySQL/MariaDB ---
        DB::statement('ALTER TABLE ligne_ventes DROP FOREIGN KEY ligne_ventes_vinyle_id_foreign');
        DB::statement('ALTER TABLE ligne_ventes ADD CONSTRAINT ligne_ventes_vinyle_id_foreign 
                      FOREIGN KEY (vinyle_id) REFERENCES vinyles(id) 
                      ON DELETE SET NULL');

        // --- Option B : SQLite (développement local) ---
        // SQLite ne supporte pas ALTER TABLE DROP CONSTRAINT
        // Nécessite recréation complète de la table -> voir section SQLite ci-dessous
    }

    public function down(): void
    {
        // Rollback : revenir à cascade
        DB::statement('ALTER TABLE ligne_ventes DROP FOREIGN KEY ligne_ventes_vinyle_id_foreign');
        DB::statement('ALTER TABLE ligne_ventes ADD CONSTRAINT ligne_ventes_vinyle_id_foreign 
                      FOREIGN KEY (vinyle_id) REFERENCES vinyles(id) 
                      ON DELETE CASCADE');
        
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->dropColumn('titre_vinyle');
        });
    }
};
```

### 3. Option SQLite (pour développement local)

Si tu utilises SQLite (`database/database.sqlite`), la migration doit recréer la table :

```php
<?php
// À insérer dans la migration si SQLite détecté

use Illuminate\Support\Facades\DB;

if (DB::getDriverName() === 'sqlite') {
    // Sauvegarder les données
    $lignes = DB::table('ligne_ventes')->get();
    
    // Supprimer et recréer la table
    Schema::dropIfExists('ligne_ventes');
    
    Schema::create('ligne_ventes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vente_id')->constrained('ventes')->onDelete('cascade');
        $table->foreignId('vinyle_id')->nullable()->constrained('vinyles')->onDelete('set null');
        $table->string('titre_vinyle')->nullable();
        $table->integer('quantite');
        $table->decimal('prix_unitaire', 10, 2);
        $table->decimal('total_ligne', 10, 2);
        $table->timestamps();
    });
    
    // Restaurer les données
    foreach ($lignes as $ligne) {
        DB::table('ligne_ventes')->insert((array) $ligne);
    }
}
```

### 4. Mettre à jour le modèle `LigneVente`

```php
// app/Models/LigneVente.php

protected $fillable = [
    'vente_id',
    'vinyle_id',
    'titre_vinyle',  // ← AJOUTER
    'quantite',
    'prix_unitaire',
    'total_ligne',
];

// Accessor pour afficher le titre (vinyle ou snapshot)
public function getTitreAttribute(): string
{
    return $this->vinyle?->nom ?? $this->titre_vinyle ?? 'Vinyle supprimé';
}
```

### 5. Mettre à jour le controller `VenteController` (si création de vente)

```php
// Dans la méthode store() ou addLigne()
$ligne = LigneVente::create([
    'vente_id' => $vente->id,
    'vinyle_id' => $vinyle->id,
    'titre_vinyle' => $vinyle->nom,  // ← Snapshot au moment de la vente
    'quantite' => $request->quantite,
    'prix_unitaire' => $vinyle->prix,
    'total_ligne' => $request->quantite * $vinyle->prix,
]);
```

---

## ✅ Validation

### Test 1 : Suppression d'un vinyle avec historique

```php
// Dans php artisan tinker
$vinyle = Vinyle::first();
$venteAvant = $vinyle->ventes()->count();

echo "Ventes liées avant suppression : $venteAvant\n";

$vinyle->delete();

$ventesAprès = \App\Models\LigneVente::whereNull('vinyle_id')->count();
echo "Lignes avec vinyle_id NULL après suppression : $ventesAprès\n";
```

**Résultat attendu** : 
- Le vinyle est supprimé
- Les lignes de vente existent toujours (vinyle_id = NULL)
- Le titre est encore visible grâce à `titre_vinyle`

### Test 2 : Affichage dans le dashboard

Sur une page qui liste les ventes, vérifier que :
- [ ] Les anciennes ventes s'affichent avec le titre du vinyle supprimé
- [ ] Le lien vers le vinyle est désactivé (ou masqué) si `vinyle_id` est NULL

### Test 3 : Rollback migration

```bash
php artisan migrate:rollback --step=1
# Vérifier que la contrainte est revenue en CASCADE
```

---

## 🚨 Risques et Précautions

| Risque | Mitigation |
|--------|-----------|
| Perte des données `ligne_ventes` | **SAUVEGARDE** la base avant migration |
| Contrainte mal nommée | Vérifier avec `SHOW CREATE TABLE ligne_ventes;` |
| SQLite différent | Utiliser la méthode de recréation de table |
| Données existantes | Script de backfill pour remplir `titre_vinyle` |

### Script de backfill (optionnel)

```php
// À ajouter dans la migration UP après ajout de la colonne
DB::table('ligne_ventes')
    ->whereNull('titre_vinyle')
    ->whereNotNull('vinyle_id')
    ->update([
        'titre_vinyle' => DB::raw('(SELECT nom FROM vinyles WHERE vinyles.id = ligne_ventes.vinyle_id)')
    ]);
```

---

## ⏸️ Mise en pause

**Statut** : En attente d'exécution
**Date de création** : 2025-03-06
**Priorité** : Moyenne (évite la perte d'historique)
**Estimation** : 20-30 minutes avec tests

---

## Résumé des fichiers à modifier

1. `database/migrations/YYYY_MM_DD_XXXXXX_update_ligne_ventes_vinyle_foreign.php` ← NOUVEAU
2. `app/Models/LigneVente.php` ← MODIFIER fillable + ajouter accessor
3. `app/Http/Controllers/VenteController.php` ← MODIFIER (si existant) pour snapshot
4. `resources/views/ventes/*.blade.php` ← MODIFIER pour gérer vinyle_id NULL

---

*Procédure créée par l'assistant le 2025-03-06*
