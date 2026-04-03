# 📋 FEUILLE DE ROUTE - Bougies-Stock

## ✅ T1.1 - Configuration Projet
**Statut:** ✅ TERMINÉE | **Date:** 2026-03-20 | **Commit:** 62addf0

---

## ✅ T2.1 - Installation Bootstrap + Vue.js
**Statut:** ✅ TERMINÉE  
**Date:** 2026-03-21  
**Branche:** feature/T2.1-install-bootstrap-vue

### Sous-tâches complétées:
- [x] 2.1.1 Bootstrap installé via npm (`bootstrap @popperjs/core`)
- [x] 2.1.2 Bootstrap CSS importé dans `resources/css/app.css`
- [x] 2.1.3 Vue.js 3 installé via npm (`vue@^3.4.0`)
- [x] 2.1.4 Vite configuré avec plugin Vue (`@vitejs/plugin-vue`)
- [x] 2.1.5 Test component créé (`TestComponent.vue`)
- [x] 2.1.6 BootstrapVueTest: 5/5 passés (100%)

### Validation
- ✅ Tests fonctionnels: `test_bootstrap_css_est_charge`, `test_vue_js_est_installe`, `test_vite_plugin_vue_est_configure`, `test_component_vue_test_existe`, `test_app_js_monte_application_vue`

### Notes
En attente de validation pour merge sur master (main)

---

## 📊 Historique

| Tâche | Statut | Date | Commit |
|-------|--------|------|--------|
| T1.1 Configuration | ✅ | 2026-03-20 | 62addf0 |

---
*Dernière mise à jour: 2026-03-20*  
*Prochaine action: Installer Bootstrap + Vue.js*


## 🎯 Tâche en cours: T2.2 - Migration Modèle Bougie
**Statut:** 🟢 EN COURS  
**Priorité:** Haute  
**Branche:** feature/T2.2-migration-modele-bougie

### Objectif
Créer la migration et le modèle pour les bougies.

### Sous-tâches
- [ ] 2.2.1 Créer la migration `create_bougies_table`
- [ ] 2.2.2 Définir les colonnes (référence, parfum, nom, collection, format, type_cire, temps_brulure, notes, prix, quantite, seuil_alerte)
- [ ] 2.2.3 Créer le modèle `Bougie.php`
- [ ] 2.2.4 Créer la factory pour les tests
- [ ] 2.2.5 Créer le seeder avec données de test
- [ ] 2.2.6 Écrire les tests pour la migration
- [ ] 2.2.7 Lancer migrations et tests

### Validation
- [ ] Table `bougies` existe en BDD
- [ ] Modèle `Bougie` fonctionnel
- [ ] Tests passent

### Notes
Dépendance: T2.1 terminée
