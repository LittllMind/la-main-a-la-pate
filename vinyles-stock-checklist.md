# 📋 CHECKLIST VINYLES-STOCK

> Checklist des tâches à réaliser sur le projet.
> Les tâches sont priorisées du haut vers le bas.

---

## 🎯 TÂCHES EN COURS

### T12 — Gestion Users + Rapports 🔄
**Statut** : Infrastructure complète, tests prêts à exécuter
**Priorité** : Haute (suite logique T11/T13)

**Sous-tâches** :
- [ ] T12.1 : UserCrudTest (12 tests) + RolePermissionsTest (12 tests)
- [ ] T12.2 : GlobalStatsTest (12 tests)
- [ ] T12.3 : MonthlyReportTest (8 tests) — Risque PDF
- [ ] T12.4 : StockReportTest (7 tests)
- [ ] T12.5 : ArtistReportTest (8 tests)

**Action requise** : Exécution manuelle des tests par Aurélien
```bash
cd ~/vinyles-stock
php artisan test tests/Feature/User/ tests/Feature/Stats/ tests/Feature/Reports/ --no-ansi
```

---

### T15 — Performance 🔄
**Statut** : Tests créés, optimisations appliquées, en attente validation
**Priorité** : Haute (débloqué par T14)

**Sous-tâches** :
- [ ] T15.1 : KiosquePerformanceTest (3 tests)
- [ ] T15.2 : StatsPerformanceTest (4 tests)
- [ ] T15.3 : VinyleQueriesOptimizationTest (3 tests)

**Optimisations déjà en place** :
- Eager loading `with(['media'])` sur Kiosque et Admin
- Pagination 24/25 éléments
- Cache 5min sur les stats
- Agrégations SQL (pas de boucles)

**Action requise** : Exécution manuelle des tests par Aurélien
```bash
cd ~/vinyles-stock
php artisan test tests/Feature/Performance/ --no-ansi
```

---

### T14 — Mode Marché ✅
**Statut** : **COMPLET** — 10/10 tests verts
**Priorité** : Terminé

**Validation** : Tous les tests ModeMarche passent
- Historique Ventes Jour : 5 tests ✅
- Annulation Vente : 4 tests ✅
- Export Journée : 1 test ✅



---

## 📁 BACKLOG

*À définir selon tes besoins*

---

## ✅ TÂCHES TERMINÉES (à archiver)

- [x] T1: Fix bouton Panier → /cart
- [x] T2: "Mes commandes" client
- [x] T3: Dashboard Stock Vinyles
- [x] T4: Gestion Stock Fonds
- [x] T5: Statistiques Admin
- [x] T6: Stock Alert System
- [x] T7: Prix achat Fonds
- [x] T8: Liste Vinyles - recherche multi-champs
- [x] T9.1: Fix Routes + Style Mouvements Stock
- [x] T9.2: Enregistrement automatique mouvements
- [x] T9.3: Traçage commandes + Documentation
- [x] T9.4: Documentation complète + Tests d'intégration
- [x] T10: Filtres Alertes Stock Avancés
- [x] T11-A: Infrastructure Tests
- [x] T11-B: Tests Dashboard Fonds
- [x] T11-C: Tests Feature Vinyles (créé, en attente commit)
- [x] T11-D: Tests Mouvements (créé, en attente commit)
- [x] T11-E: Tests Commandes (créé, en attente commit)

---

**Dernière mise à jour**: 2026-03-09
