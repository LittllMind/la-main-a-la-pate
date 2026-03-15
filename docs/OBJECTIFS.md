# 📋 OBJECTIFS - Projet Vinyls Stock

> État après rollback - Commit actuel : `7992941` (Phase 1 complète)
> Date d'audit : 2026-03-07

---

## 🎯 PHASE 1 - ✅ TERMINÉE (Commit 7992941)

### ✅ Kiosque Public (100%)
- Landing page avec design violet/rose
- Page `/kiosque` - Consultation catalogue sans auth
- Page `/about` et `/contact`
- Identité visuelle définie (dégradés, dark mode)

### ✅ Tunnel de Vente (100%)
- Système de panier (session + fusion login)
- CRUD Adresses (livraison/facturation)
- Formulaire commande `/orders/create`
- Récapitulatif paiement `/orders/payment`
- Historique commandes `/orders`

### ✅ Paiement Stripe (100%)
- Checkout Stripe fonctionnel
- Webhook de confirmation
- Vues success/cancel
- Intégration complète dans le workflow

### ✅ RBAC (100%)
- Rôles : Admin, Employé, Client
- Middleware `role:admin,employe`
- Comptes test créés

---

## 🚧 PHASE 2 - PARTIELLEMENT IMPLÉMENTÉE

### 🟡 Modules Présents (Non commités dans Phase 1)

| Module | Fichiers | Statut | Notes |
|--------|----------|--------|-------|
| **Stats** | `StatsController.php`, `stats.blade.php` | 🟡 Existe | Phase 2 démarrée mais non finalisée |
| **Stock Alert** | `StockAlert.php` modèle + migration | 🟡 Existe | Table créée, mais pas de controller/vue |
| **Fonds** | `FondController.php`, `Fond.php` | 🟡 Existe | Gestion des stocks de fonds (miroir/doré) |
| **Vente Legacy** | `VenteController.php`, tables ventes/ligne_ventes | 🟡 Existe | Système parallèle à Orders (marchés) |
| **Photos** | `Vinyle.php` modifié (3 photos) | 🟡 Existe | Collection multiple, mais pas de controller adapté |

### ❌ Modules Manquants (Phase 2 non démarrée)

| Module | Manquant |
|--------|----------|
| **Catégories** | Modèle `Categorie.php`, `CategorieController`, migrations, vues |
| **Mouvements Stock** | `StockController`, `MouvementStock.php`, historique entrées/sorties |
| **Alertes Dashboard** | Widget dashboard, envoi email automatique |
| **Inventaire Physique** | Comptage, écarts, rapports PDF |
| **Système Vente Marchés** | Intégration MouvementStock avec Vente legacy |

---

## 📝 DÉCISIONS CLÉS À PRENDRE

### 1. Système Double Ventes
```
Table A (Legacy)          Table B (Actif)
├── ventes                ├── orders  
└── ligne_ventes          └── order_items
    └── Utilisation: ?        └── Utilisation: Stripe e-commerce
```
**Option retenue historique** : Garder les deux, brancher `Vente` sur `MouvementStock`

### 2. Photos Vinyles
- **Actuellement** : `registerMediaCollections()` avec collection 'photo' (3 max suggéré)
- **Manque** : Validation controller pour 3 photos max, UI adaptation

### 3. Quantité vs Seuils
- ✅ `vinyles.quantite` - Existe
- ✅ `vinyles.seuil_alerte` - Existe  
- ❌ `vinyles.seuil_attention` (jaune) - À ajouter si besoin

---

## 🚀 PHASE 3 - NON DÉMARRÉE

### Tests Automatisés
- PHPUnit configuration
- Tests unitaires Models
- Tests fonctionnels Controllers
- Tests intégration Stripe (mock)
- Laravel Dusk (optionnel)

---

## 📊 PROCHAINES ÉTAPES SUGGÉRÉES

1. **Finaliser Phase 2** ou **simplifier** selon besoins réels
2. **Créer les seeders** pour données de test
3. **Phase 3** - Tests automatisés (haute priorité avant prod)

---

**Fichier à jour après audit rollback** - 2026-03-07
