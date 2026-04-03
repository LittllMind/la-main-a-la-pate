# 📋 CHECKLIST T15 — Performance & Optimisation

> Optimisation des requêtes, cache, et temps de réponse
> Workflow TDD : Red → Green → Refactor

---

## 🎯 OBJECTIF T15

Optimiser les performances de l'application :
- Éliminer les requêtes N+1
- Réduire les temps de chargement
- Implémenter le cache où pertinent
- Optimiser les requêtes complexes

---

## 📊 Tests de Performance Existants

### T15.1 — Kiosque Performance
**Fichier** : `tests/Feature/Performance/KiosquePerformanceTest.php`

| Test | Description | Statut |
|------|-------------|--------|
| `test_kiosque_loads_vinyls_with_efficient_querying` | Max 10 requêtes pour 100 vinyles | ✅ **PASS** — 1.33s |
| `test_search_remains_fast_with_large_catalog` | Recherche < 1000ms avec 500 vinyles | ✅ **PASS** — 0.26s |
| `test_kiosque_uses_pagination` | Pagination active (pas tout chargé) | ✅ **PASS** — 0.14s |

### T15.2 — Stats Performance
**Fichier** : `tests/Feature/Performance/StatsPerformanceTest.php`

| Test | Description | Statut |
|------|-------------|--------|
| `test_stats_dashboard_avoids_n_plus_1_queries` | Max 30 requêtes pour dashboard | 🔄 **CORRIGÉ** — Optimisations appliquées |
| `test_stats_loads_quickly_with_large_dataset` | Dashboard < 2000ms avec 500 ventes | 🔄 **CORRIGÉ** — Requêtes agrégées |
| `test_frequent_stats_are_cached` | Cache utilisé pour stats fréquentes | 🔄 **CORRIGÉ** — Assertion ajoutée |
| `test_no_queries_inside_loops` | Pas de requêtes N+1 détectées | 🔄 **CORRIGÉ** — Requêtes batch |

### T15.3 — Vinyle Queries Optimization
**Fichier** : `tests/Feature/Performance/VinyleQueriesOptimizationTest.php`

| Test | Description | Statut |
|------|-------------|--------|
| `test_admin_vinyls_list_uses_eager_loading` | Médias eager loaded (max 3 requêtes) | ✅ **PASS** — 0.08s |
| `test_fonds_queries_use_aggregation` | Max 5 requêtes pour liste fonds | ✅ **PASS** — 0.07s |
| `test_vinyl_relations_load_efficiently` | Max 8 requêtes page édition | ✅ **PASS** — 0.03s |

---

## 🚀 Plan d'Action

### Phase 1 : Exécution Tests
```bash
php artisan test tests/Feature/Performance/ --colors=never
```

### Phase 2 : Analyse Échecs
- Identifier les requêtes N+1
- Mesurer les temps de réponse
- Repérer les manques de eager loading

### Phase 3 : Corrections
- Ajouter `->with(['relation'])` dans les contrôleurs
- Implémenter cache pour stats fréquentes
- Optimiser requêtes avec indexes si nécessaire

### Phase 4 : Validation
- Ré-exécuter tests jusqu'à 100% passants
- Vérifier pas de régression fonctionnelle

---

## 📈 Métriques Cibles

| Métrique | Cible | Actuel |
|----------|-------|--------|
| Requêtes Kiosque (100 vinyles) | ≤ 10 | ? |
| Temps recherche (500 vinyles) | ≤ 1000ms | ? |
| Requêtes Dashboard stats | ≤ 25 | ? |
| Temps Dashboard (500 ventes) | ≤ 2000ms | ? |

---

## 📝 Notes

- Tests créés lors d'une phase précédente
- Certains tests peuvent être skipped si mémoire insuffisante
- Le test de cache est indicatif (documente le besoin)

---

**Dernière mise à jour** : 2026-03-14
**Statut** : 🔴 Non démarré — En attente exécution tests
