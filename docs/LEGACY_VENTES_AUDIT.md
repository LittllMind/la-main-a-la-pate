# Audit : Double Système Ventes (Legacy vs E-commerce)

**Date** : 2026-03-08  
**Auteur** : Picoclaw  
**Statut** : ✅ Audit terminé - Attente décision utilisateur

---

## 1. Vue d'ensemble

Deux systèmes de vente coexistent sans liaison :

| Système | Usage | Actif |
|---------|-------|-------|
| `ventes` + `ligne_ventes` | Vente directe caisse (boutique physique) | ✅ Oui |
| `orders` + `order_items` | E-commerce tunnel complet | ✅ Oui |

---

## 2. Structure comparative

### Tables Legacy (`ventes`)
```php
// Champs : id, date, total, mode_paiement, timestamps
// Relations : hasMany lignes (vinyle_id, quantite, prix_unitaire, total, fond)
```

### Tables E-commerce (`orders`)
```php
// Champs complets : user_id, nom, prenom, email, telephone, adresses,
// total, statut, notes, timestamps validation/préparation/livraison
// Relations : hasMany items (vinyle_id, fond_id, snapshot données)
```

---

## 3. Codes utilisant les deux systèmes

### 3.1 VenteController (Legacy) ⭐ CRITIQUE
**Fichier** : `app/Http/Controllers/VenteController.php`

| Méthode | Route | Usage |
|---------|-------|-------|
| `index()` | `GET /ventes` | Interface caisse admin |
| `create()` | `GET /ventes/create` | Formulaire nouvelle vente |
| `store()` | `POST /ventes` | Enregistrement vente caisse |
| `storeFromKiosque()` | `POST /api/ventes/kiosque` | API vente rapide (sans auth) |
| `destroy()` | `DELETE /ventes/{id}` | Annulation + restock |

**Impact** : Ce contrôleur est **entièrement fonctionnel** et gère :
- Décrémentation stock vinyles
- Décrémentation stock fonds (miroir/doré)
- Calcul suppléments fonds
- Annulation avec restock

### 3.2 OrderController (E-commerce)
**Fichier** : `app/Http/Controllers/OrderController.php`

| Méthode | Usage |
|---------|-------|
| `confirm()` | Transformation panier → commande |
| `show()`, `index()` | Affichage commandes client/admin |
| `updateStatus()` | Workflow préparation/livraison |

---

## 4. Points de frictions identifiés

### ❌ PROBLÈME 1 : Risque stock négatif
**Scénario** :
1. Client A achète vinyle X en ligne (`Order` créée)
2. Client B achète même vinyle X en caisse (`Vente` créée)
3. Les deux décrémentent le stock sans coordination
4. → Stock peut devenir négatif

**Preuve** : Aucune vérification de stock global entre les deux systèmes

### ❌ PROBLÈME 2 : Rapport CA incomplet
**Fichier** : `app/Http/Controllers/StatsController.php`

Les stats dashboard actuelles ne comptabilisent probablement que les `orders`.
Les `ventes` ne sont pas incluses dans les rapports.

### ❌ PROBLEME 3 : Historique client fragmenté
Un client peut avoir :
- Commandes en ligne visibles dans `/mes-commandes`
- Ventes caisse NON visibles (pas liées à `user_id`)

### ❌ PROBLÈME 4 : Données redondantes
Changement de prix vinyle → incohérence entre anciennes ventes et nouvelles

---

## 5. Options de résolution

### Option A : Unification complète (RECOMMANDÉ)
**Plan** :
1. Migrer toutes les `ventes` existantes vers `orders`
2. Ajouter statut `paye_en_caisse` pour différencier
3. Modifier `VenteController` pour créer des `Order` au lieu de `Vente`
4. Supprimer tables `ventes`/`ligne_ventes`

**Avantages** : Un seul système, stats cohérentes, historique complet
**Inconvénients** : Migration données à tester, risque de régression caisse

### Option B : Liaison pont (Quick fix)
**Plan** :
1. Ajouter `vente_id` nullable dans `orders`
2. Lier chaque `Vente` à un `Order` shadow
3. Stats dashboard query les deux tables

**Avantages** : Rapide, low risk
**Inconvénients** : Complexité maintenue, deux sources de vérité

### Option C : Séparation explicite (Status quo documenté)
**Plan** :
1. Documenter que `ventes` = caisse physique, `orders` = e-commerce
2. Dashboard avec deux onglets : "Ventes en ligne" / "Ventes caisse"
3. Vérification stock centralisée avant chaque vente

**Avantages** : Pas de migration, clair pour l'utilisateur
**Inconvénients** : Deux systèmes à maintenir, risque erreur humaine

---

## 6. Recommandation

**Option A (Unification)** si :
- Tu veux une vision unique du business
- Tu acceptes 1-2 jours de travail + tests

**Option C (Séparation)** si :
- Tu as besoin de livrer vite
- Le risque stock négatif est acceptable à court terme

---

## 7. Données à préserver (si migration)

| Table | Enregistrements | Critique |
|-------|-----------------|----------|
| `ventes` | À vérifier | CA historique |
| `ligne_ventes` | À vérifier | Détail ventes |

**Commande vérification** :
```sql
SELECT COUNT(*) FROM ventes;
SELECT MIN(date), MAX(date) FROM ventes;
```

---

## 8. Prochaines étapes

1. **Vérifier données existantes** dans `ventes`
2. **Choisir option** (A, B ou C)
3. **Implémenter** selon choix

---

*Document généré automatiquement par Picoclaw*