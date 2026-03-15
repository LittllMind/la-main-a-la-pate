# Spécifications : Mode Marché

**Date** : 2026-03-08
**Décision** : GO 2.C - Option C adaptée

## Objectif
Créer un système de vente "marché" qui utilise la structure `orders` (pas le legacy `ventes`) avec une interface optimisée pour vendre sur place (téléphone/tablette).

## Principe
- **Pas de double système** : Tout passe dans `orders`
- **Mode spécifique** : Champ `source` pour différencier web vs marché
- **Interface mobile** : Vue dédiée, responsive, rapide
- **Paiement manuel** : Cash, CB terminal, chèque (pas Stripe)

## Structure technique

### Nouveaux champs dans `orders`
```php
// Migration à créer
$table->enum('source', ['web', 'marche'])->default('web');
$table->enum('mode_paiement_marche', ['cash', 'cb_terminal', 'cheque'])->nullable();
$table->string('vendeur_notes')->nullable(); // QR Code, nom client rapide
```

### Contrôleur
- `MarcheController` ou `ModeMarcheController`
- Routes : `/admin/marche` (interface), `/admin/marche/store` (validation)

### Vue
- `resources/views/admin/marche/index.blade.php`
- Catalogue scrollable (photos miniatures)
- Panier latéral/bas
- Paiement 1-click (cash/CB/cheque)

### Sécurité
- Vérification stock en temps réel (AJAX)
- Bloque si rupture entre deux ventes simultanées

## Avantages vs Legacy `ventes`
- ✅ Un seul tableau `orders` pour tous les rapports
- ✅ Statistiques unifiées (CA, top vinyles)
- ✅ Historique client potentiel (téléphone ou email optionnel)
- ✅ Pas de risque stock négatif (même table utilisée)

## Flux validation
1. Sélection vinyles → Panier JS
2. Clic "Valider vente"
3. Vérification stock serveur
4. Création `Order` (source='marche', statut='payee')
5. Décrémentation stock
6. Affichage ticket/sommaire
