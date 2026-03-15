# Analyse des échecs T14 — ModeMarcheTest

## Résultat brut
11/11 tests en FAIL

## Diagnostic préliminaire

### Tests historique ventes (T14.1)
1. `employe_can_view_sales_history_for_today` — Route existe mais retourne 401/302 ?
2. `sales_history_shows_correct_total_amount` — IDEM
3. `sales_history_only_shows_today_sales` — IDEM
4. `unauthenticated_user_cannot_access_sales_history` — Attend 401, retourne probablement 302 (redirect login)
5. `client_cannot_access_sales_history` — Devrait être OK

### Tests annulation (T14.2)
6. `employe_can_cancel_sale_within_time_limit` — Vérifier méthode cancel
7. `cancelled_sale_triggers_restock` — Vérifier restock
8. `client_cannot_cancel_sale` — Permission
9. `cannot_cancel_already_cancelled_sale` — Garde-fou statut
10. `cannot_cancel_non_marche_order` — Garde-fou source

### Tests export (T14.3)
11. `employe_can_export_daily_sales_as_csv` — **Route inexistante jusqu'à maintenant**

## Actions effectuées
✅ Ajout route `/admin/marche/export` dans web.php
✅ Ajout méthode `export()` dans ModeMarcheController.php

## Prochaine étape
Re-exécuter les tests pour identifier les erreurs précises
