# 📚 T9.4 - Documentation Système Mouvements Stock

> Guide complet d'architecture et d'utilisation du système de traçabilité stock

---

## 🏗️ Architecture Globale

```
┌─────────────────────────────────────────────────────────────────┐
│                    MOUVEMENTS DE STOCK                          │
├─────────────────────────────────────────────────────────────────┤
│  Vues                           Controllers                     │
│  ─────                          ──────────                     │
│  mouvements/index.blade.php    StockMovementController          │
│  mouvements/show.blade.php      ├─ index()   : Liste + filtres │
│                                  └─ export() : CSV            │
├─────────────────────────────────────────────────────────────────┤
│  Services                        Observers                      │
│  ────────                        ─────────                      │
│  StockMovementService            VinyleObserver                 │
│  ├─ entree()                      ├─ created()                  │
│  ├─ sortie()                      ├─ updated()                │
│  ├─ traceVinyleCreated()          └─ deleted()                  │
│  ├─ traceVinyleStockChanged()                                  │
│  ├─ traceFondStockChanged()      FondObserver                   │
│  └─ traceCommandeValidee()        ├─ saving()                   │
│                                   ├─ saved()                   │
│  OrderObserver                    └─ deleted()                  │
│  ├─ updating()                                                  │
│  └─ updatingCanceled()                                          │
├─────────────────────────────────────────────────────────────────┤
│                         Modèle                                  │
│  MouvementStock                                                 │
│  ├─ Scopes : entrees(), sorties(), parPeriode()               │
│  ├─ Relations : user(), produitable()                           │
│  └─ Méthodes : enregistrer(), statsPeriode()                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📋 Types de Mouvements

| Type | Déclencheur | Exemple |
|------|-------------|---------|
| **entree** | Création vinyle | `+10` (stock initial) |
| **entree** | Mise à jour stock (ajout) | `+5` (ajout manuel) |
| **sortie** | Commande validée | `-1` (vente) |
| **sortie** | Mise à jour stock (retrait) | `-2` (correction) |
| **entree** | Annulation commande | `+1` (retour stock) |

---

## 🔌 Points d'intégration

### 1. VinyleObserver

```php
// Déclenchement automatique
created(Vinyle $vinyle)  → Entrée avec stock initial
updated(Vinyle $vinyle)  → Entrée/Sortie si stock modifié
deleted(Vinyle $vinyle)  → Sortie avec stock restant
```

### 2. FondObserver

```php
// Traçage par type (miroir/doré/standard)
saving(Fond $fond)  → Détection changement avant sauvegarde
saved(Fond $fond)  → Enregistrement mouvement si diff détectée
deleted(Fond $fond) → Sortie totale
```

### 3. OrderObserver

```php
// Sur changement de statut
updating($order) → Sortie si passage à 'validee/prete/livree'
updatingCanceled($order) → Entrée si annulation après validation
```

---

## 🛠️ StockMovementService - Référence API

### Méthodes statiques principales

```php
// Enregistrer une entrée
MouvementStock $mvt = StockMovementService::entree(
    string $produitType,    // 'vinyle', 'miroir', 'dore', 'pochette'
    int $produitId,         // ID du produit
    int $quantite,          // Quantité positive
    ?string $reference,     // Référence interne (ex: CMD-000001)
    ?string $notes          // Description
);

// Enregistrer une sortie
MouvementStock $mvt = StockMovementService::sortie(
    string $produitType,
    int $produitId,
    int $quantite,          // Négative automatiquement
    ?string $reference,
    ?string $notes
);
```

### Méthodes de traçage automatique

```php
// Traçage création vinyle
StockMovementService::traceVinyleCreated(Vinyle $vinyle);

// Traçage changement stock
StockMovementService::traceVinyleStockChanged(
    Vinyle $vinyle,
    int $oldStock,
    int $newStock
);

// Traçage fond
StockMovementService::traceFondStockChanged(
    Fond $fond,
    string $typeField,      // 'miroir', 'dore', 'standard'
    int $oldQty,
    int $newQty
);

// Traçage commande validée
StockMovementService::traceCommandeValidee(Order $order);
```

---

## 🧪 Tests d'intégration

### Commandes de test disponibles

```bash
# Tester les mouvements auto (T9.2)
php artisan test:stock-movement

# Tester les mouvements commande (T9.3)
php artisan test:order-movement
```

### Scénarios de test manuel

```php
// Test 1 : Création vinyle → Entrée auto
$vinyle = Vinyle::create([
    'titre' => 'Test Album',
    'stock' => 10
]);
// Vérifier : mouvement entrée +10 dans la table

// Test 2 : Modification stock
$vinyle->stock = 15; // +5
$vinyle->save();
// Vérifier : mouvement entrée +5

// Test 3 : Commande validée
$order->statut = 'validee';
$order->save();
// Vérifier : mouvements sortie pour chaque ligne
```

---

## 📊 Accès Interface Web

| Chemin | Description | Rôles |
|--------|-------------|-------|
| `/mouvements` | Liste des mouvements | admin, employe |
| `/mouvements?export=1` | Export CSV | admin, employe |

### Filtres disponibles

- **Type** : entree | sortie
- **Produit** : vinyle | miroir | dore | pochette
- **Date** : Sélection par période
- **Recherche** : Référence ou notes

---

## 🔐 Sécurité

### Contrôles d'accès
- Middleware `auth` requis
- Rôles : `admin` et `employe` uniquement
- Les clients n'ont pas accès aux mouvements

### Traçabilité
- `user_id` enregistré à chaque mouvement
- `created_by` depuis `Auth::id()` ou fallback admin (1)
- `reference` pour lien vers commande/origine

---

## 📈 Monitoring

### Requêtes SQL utiles

```sql
-- Mouvements du jour
SELECT type, produit_type, SUM(quantite) as total
FROM mouvements_stock
WHERE DATE(created_at) = CURDATE()
GROUP BY type, produit_type;

-- Top produits mouvementés (30j)
SELECT produit_type, produit_id, COUNT(*) as nb_mouvements
FROM mouvements_stock
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY produit_type, produit_id
ORDER BY nb_mouvements DESC
LIMIT 10;
```

---

## ⚠️ Gestion des Erreurs

| Cas | Comportement |
|-----|--------------|
| Stock négatif | Autorisé (le système ne bloque pas) |
| Quantité 0 | Ignoré (pas de mouvement créé) |
| Produit supprimé | Relation polymorphique gérée (null) |
| User non connecté | Fallback user_id = 1 (admin) |

---

## ✅ Checklist Maintenance

- [ ] Vérifier cohérence stocks vs mouvements
- [ ] Exporter historique mensuel
- [ ] Archiver mouvements > 1 an (optionnel)
- [ ] Tester observers après mise à jour Laravel

---

**Version** : T9.4 | **Date** : 2026-03-09
**Responsable** : Système autonome
