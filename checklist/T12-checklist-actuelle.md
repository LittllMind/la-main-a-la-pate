# 📋 CHECKLIST T12 — Gestion Users + Rapports

> Tests Feature/ existants | Workflow TDD : Red → Green → Refactor
> Statut: Tests créés → À valider/exécuter

---

## 📊 Vue d'ensemble des Tests Prêts

| Fichier | Nb Tests | Description |
|---------|----------|-------------|
| `User/UserCrudTest.php` | 12 | CRUD utilisateurs + pagination |
| `User/RolePermissionsTest.php` | 12 | Middleware rôles + permissions |
| `User/UserProfileTest.php` | ? | Profil utilisateur |
| `Stats/GlobalStatsTest.php` | 12 | Dashboard stats globales |
| `Stats/ChartsTest.php` | ? | API graphiques |
| `Reports/MonthlyReportTest.php` | 8 | Rapport mensuel PDF |
| `Reports/StockReportTest.php` | 7 | Rapport stock global |
| `Reports/ArtistReportTest.php` | 8 | Rapport par artiste |
| `Reports/InventoryExportTest.php` | ? | Export inventories PDF |

**Total estimé**: ~60+ tests

---

## ✅ T12.1 Gestion Utilisateurs (CRUD)

### Fichier: `tests/Feature/User/UserCrudTest.php` (12 tests)

| Test | Description | Statut Attendu |
|------|-------------|----------------|
| `admin_can_view_users_list` | Liste paginée des users | ✅ |
| `employe_cannot_view_users_list` | Redirection kiosque | ✅ |
| `client_cannot_view_users_list` | Redirection kiosque | ✅ |
| `guest_is_redirected_to_login` | Route protégée | ✅ |
| `admin_can_create_user` | Création avec validation | ✅ |
| `admin_cannot_create_user_with_invalid_email` | Validation email | ✅ |
| `admin_cannot_create_user_with_duplicate_email` | Email unique | ✅ |
| `admin_can_edit_user` | Formulaire édition | ✅ |
| `admin_can_update_user` | Mise à jour | ✅ |
| `admin_can_delete_user` | Suppression user | ✅ |
| `admin_cannot_delete_themselves` | Protection auto-suppression | ✅ |
| `users_list_is_paginated` | 25 users = pagination | ✅ |

**Routes requises** (définies dans web.php):
```php
Route::resource('admin.users', \App\Http\Controllers\Admin\UserController::class)
    ->middleware(['auth', 'role:admin']);
```

### Fichier: `tests/Feature/User/RolePermissionsTest.php` (12 tests)

| Test | Description | Statut Attendu |
|------|-------------|----------------|
| `admin_can_access_admin_routes` | Routes admin accessibles | ✅ |
| `employe_cannot_access_admin_routes` | Middleware bloque | ✅ |
| `client_cannot_access_admin_routes` | Redirection kiosque | ✅ |
| `admin_can_access_employe_routes` | Héritage permissions | ✅ |
| `employe_can_access_employe_routes` | Accès vinyles fonds | ✅ |
| `client_cannot_access_employe_routes` | Bloqué | ✅ |
| `admin_and_employe_can_access_fonds_index` | Route partagée | ✅ |
| `employe_cannot_update_fond_stock` | PATCH interdit | ✅ |
| `admin_can_update_fond_stock` | PATCH autorisé | ✅ |
| `guest_is_redirected_to_login_for_protected_routes` | Auth obligatoire | ✅ |
| `middleware_allows_multiple_roles` | `role:admin,employe` | ✅ |
| `role_check_is_case_insensitive` | Normalisation rôles | ✅ |

---

## ✅ T12.2 Dashboard Stats & Rapports

### Fichier: `tests/Feature/Stats/GlobalStatsTest.php` (12 tests)

| Test | Description | Variables attendues |
|------|-------------|---------------------|
| `admin_peut_voir_dashboard_stats` | Vue dashboard | `ventesMois`, `commandesEnCours`... |
| `dashboard_affiche_ventes_mois_en_cours` | Calcul ventes mois | `ventesMois` |
| `dashboard_affiche_nombre_commandes_en_cours` | Count en_attente/prete | `commandesEnCours` |
| `dashboard_affiche_valeur_stock_vinyles` | Σ(quantite × prix) | `valeurStockVinyles` |
| `dashboard_affiche_valeur_stock_fonds` | Σ(quantite × prix_vente) | `valeurStockFonds` |
| `dashboard_affiche_total_unites_stock` | Quantités brutes | `totalVinyles`, `totalFonds` |
| `dashboard_affiche_alertes_stock` | Vinyles < seuil | `alertesVinyles` |
| `employe_peut_voir_dashboard_stats` | Accès employé autorisé | ✅ |
| `client_ne_peut_pas_voir_dashboard_stats` | Redirection kiosque | ✅ |
| `dashboard_api_retourne_stats_json` | `/admin/stats` JSON | Structure API |
| `dashboard_affiche_dernieres_commandes` | Liste 5 dernières | `dernieresCommandes` |
| `stats_exclut_commandes_annulees_des_ventes` | WHERE statut != 'annulee' | ✅ |

**Routes définies**:
```php
/admin/dashboard          → DashboardController@index
/admin/stats              → DashboardController@statsApi (JSON)
/admin/stats/charts       → DashboardController@chartsApi (JSON)
```

---

## ✅ T12.3 Rapports Mensuels (PDF)

### Fichier: `tests/Feature/Reports/MonthlyReportTest.php` (8 tests)

| Test | Description | Commentaire |
|------|-------------|-------------|
| `admin_can_generate_monthly_report_pdf` | POST génère PDF | `Content-Type: application/pdf` |
| `monthly_report_contains_sales_data` | Contient "VENTES" + total | ✅ |
| `vendeur_can_generate_monthly_report` | Employé autorisé | ✅ |
| `client_cannot_generate_monthly_report` | Redirection kiosque | ✅ |
| `monthly_report_includes_global_kpis` | Section INVENTAIRE | ✅ |
| `monthly_report_form_is_accessible` | GET formulaire | Vue `admin.reports.monthly-form` |
| `monthly_report_validates_parameters` | Format Y-m requis | Validation date |
| `monthly_report_includes_mouvements` | Section MOUVEMENTS DE STOCK | ✅ |

**⚠️ TODO Important**: 
- Le PDF généré par `ReportController` est un PDF texte brut (pas de librairie)
- Les assertions sur le contenu PDF (`assertStringContainsString`) peuvent échouer si format binaire
- **Action**: Vérifier si on utilise FPDF/dompdf ou PDF "fait maison"

---

## ✅ T12.4 Rapport Stock Global

### Fichier: `tests/Feature/Reports/StockReportTest.php` (7 tests)

| Test | Description | Vue attendue |
|------|-------------|--------------|
| `admin_can_view_stock_report` | Accès rapport stock | `admin.reports.stock` |
| `stock_report_shows_total_value` | Valeur totale calculée | `310` (vinyles + fonds) |
| `stock_report_shows_vinyls_vs_fonds_breakdown` | Répartition | Vinyles/Fonds |
| `employe_can_view_stock_report` | Accès employé | ✅ |
| `client_cannot_view_stock_report` | Bloqué | Redirection kiosque |
| `stock_report_shows_low_stock_alerts` | Alertes stock bas | ✅ |
| `stock_report_shows_breakdown_by_category` | Group by genre | Rock, Jazz... |

**Route définie**:
```php
/admin/reports/stock → ReportController@stock
```

---

## ✅ T12.5 Rapport Par Artiste

### Fichier: `tests/Feature/Reports/ArtistReportTest.php` (8 tests)

| Test | Description | Commentaire |
|------|-------------|-------------|
| `admin_can_view_artist_report` | Accès rapport artistes | `admin.reports.artists` |
| `artist_report_lists_all_artists_with_stock` | Group by artiste | SUM(quantite) |
| `artist_report_sorts_by_stock_value_descending` | ORDER BY valeur DESC | Tri valeur |
| `artist_report_shows_number_of_titles_per_artist` | COUNT(titres) | Par artiste |
| `employe_can_view_artist_report` | Accès employé | ✅ |
| `client_cannot_view_artist_report` | Bloqué | Redirection kiosque |
| `artist_report_can_filter_by_letter` | `?letter=T` | The Beatles, pas Pink Floyd |
| `artist_report_shows_out_of_stock_artists` | quantite = 0 visible | Marqué "Rupture" |

**Route définie**:
```php
/admin/reports/artists → ReportController@artists
```

---

## 🔴 Points de Vérification / Risques identifiés

### 1. Génération PDF (MonthlyReport)
```php
// ReportController utilise PDF "fait maison" (texte brut)
private function generatePdfFromText($title, $content) { ... }
```
**Problème potentiel**: Les tests vérifient `assertStringContainsString('VENTES', $content)` sur une réponse PDF binaire (même texte-based, ce n'est pas HTML).

**Solutions possibles**:
1. Vérifier les headers uniquement (`Content-Type: application/pdf`)
2. Parser le texte du PDF (complexe)
3. Mock la génération PDF pour les tests

### 2. Vues Blade
Les vues suivantes doivent exister:
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/reports/monthly-form.blade.php`
- `resources/views/admin/reports/stock.blade.php`
- `resources/views/admin/reports/artists.blade.php`

### 3. Factory User
Vérifier que la factory supporte les méthodes `admin()`, `employe()`, `client()`:
```php
User::factory()->admin()->create();
User::factory()->employe()->create();
```

---

## 🎯 Prochaines Actions

### À faire immédiatement:
1. **Exécuter tous les tests T12** pour voir l'état réel
2. **Identifier les échecs** vs tests qui passent
3. **Corriger les routes/vues manquantes** si nécessaire

### Commandes à exécuter (pendant que tu valides):
```bash
# Tests T12 - Gestion utilisateurs
php artisan test tests/Feature/User/

# Tests T12 - Stats et rapports
php artisan test tests/Feature/Stats/
php artisan test tests/Feature/Reports/

# Ou tout T12 en une fois
php artisan test tests/Feature/User tests/Feature/Stats tests/Feature/Reports
```

---

## 📈 Objectif Session

| Phase | Cible | Critère |
|-------|-------|---------|
| **T12.1** | 36 tests passants | UserCrud + RolePermissions |
| **T12.2** | 12 tests passants | GlobalStats |
| **T12.3** | 8 tests passants | MonthlyReport (PDF) |
| **T12.4** | 7 tests passants | StockReport |
| **T12.5** | 8 tests passants | ArtistReport |
| **TOTAL** | **~71 tests** | T12 complet |

---

**Résumé**: Infrastructure T12 en place. Besoin d'exécution + corrections sur PDF et éventuelles vues manquantes. 🎧
