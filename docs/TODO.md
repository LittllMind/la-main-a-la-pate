# TODO - Liste des tâches et points de blocage

> Document centralisant toutes les demandes à formuler à l'opérateur et les points nécessitant une intervention manuelle.

---

## 🔴 Points de blocage critiques (à traiter en priorité)

*Aucun blocage actif - max_tool_iterations corrigé ✅*

---

## 🟡 Demandes en attente de décision

### 2. Soft Delete LigneVentes (PROCÉDURE DOCUMENTÉE)
**Statut** : En attente d'exécution (système actif)
**Documentation** : `docs/PROCEDURE_SOFT_DELETE_VINYLES.md`
**Objectif** : Préserver l'historique des ventes si vinyle supprimé
**Changements prévus** :
- Remplacer `ON DELETE CASCADE` par `SET NULL` sur `ligne_ventes.vinyle_id`
- Ajouter colonne `titre_vinyle` (snapshot) pour conservation historique

**Quand exécuter ?** : 
- [ ] Pendant une maintenance (système à l'arrêt)
- [ ] Faire une backup BDD avant
- [ ] Exécuter manuellement avec précaution

---

### 3. Définition des priorités Phase 2
**Statut** : À discuter et valider
**Options proposées** :

| Priorité | Fonctionnalité | Impact métier | Effort estimé |
|----------|---------------|-------------|---------------|
| 1 | **Gestion de stock** | Haut - Évite ruptures/surstock | Moyen |
| 2 | **Dashboard admin** | Haut - Pilotage activité | Moyen |
| 3 | **Emails transactionnels** | Moyen - Communication clients | Faible |
| 4 | **Réservation vinyles** | Moyen - Attente produits | Fort |
| 5 | **Programme fidélité** | Faible - Rétention clients | Moyen |
| 6 | **Déploiement production** | Critique - Mise en ligne | Fort |

**Question pour l'opérateur** : Quelles priorités Phase 2 sont les plus importantes pour toi ?

---

## 🟢 Vérifications à effectuer

### 4. Test du paiement Stripe en production
**Statut** : À tester quand prêt
**Prérequis** :
- [ ] Passer les clés API en mode "live" dans `.env`
- [ ] Avoir un compte Stripe validé
- [ ] Tester un vrai paiement (petit montant)

---

### 5. Test des comptes RBAC
**Statut** : Créés, à vérifier régulièrement
**Comptes** : Voir `docs/COMPTES_TEST.md`
- `admin@example.com` / `password`
- `employe@example.com` / `password`  
- `client@example.com` / `password`

---

## 📝 Notes diverses

### Photo simplification - TERMINÉ ✅
- ~~Passer de 3 photos à 1 seule~~
- ~~Corriger affichage `/vinyles`~~

### Bugs corrigés ce matin ✅
- RBAC CRUD : `role:admin,employe`
- Fix Alpine.js sur `/vinyles`

---

## 📊 Résumé visuel

```
🔴 Bloquant :  1 (config max_tool_iterations)
🟡 En attente : 2 (Soft Delete + Phase 2)
🟢 À tester :   2 (Stripe live + RBAC)
```

---

**Dernière mise à jour** : 2026-03-06 09:25
**Prochaine review** : À définir avec l'opérateur
