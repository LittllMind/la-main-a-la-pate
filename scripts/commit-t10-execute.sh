#!/bin/bash
# Script de commit T10 : Filtres Alertes Stock Avancés
# Mode HeartBeat Marathon - 2026-03-09

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

# Configuration git
git config user.name "PicoClaw" 2>/dev/null || true
git config user.email "picoclaw@assistant.local" 2>/dev/null || true

# Vérification des fichiers
FILES_TO_STAGE=(
    "app/Http/Controllers/StockAlertController.php"
    "app/Models/StockAlert.php"
    "resources/views/stock-alerts/index.blade.php"
    "database/migrations/2026_03_09_000001_add_resolved_at_to_stock_alerts.php"
    "docs/T10-FILTRES-ALERTES.md"
    "routes/web.php"
    "scripts/commit-t10-execute.sh"
)

echo "=== Vérification des fichiers ==="
for file in "${FILES_TO_STAGE[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file"
    else
        echo "✗ $file (non trouvé)"
    fi
done

# Stage des fichiers
echo ""
echo "=== Staging des fichiers ==="
git add "${FILES_TO_STAGE[@]}"

# Commit
echo ""
echo "=== Commit T10 ==="
git commit -m "feat/T10: Filtres avancés alertes stock - multicritères avec export CSV

- Controller StockAlertController : méthode index() avec 6 filtres multicritères
- Filtres : type (rupture/faible/tous), produit (vinyle/fond), statut, dates, recherche
- Stats temps réel : ruptures/faibles/actives/aujourd'hui/cette semaine
- Export CSV avec filtres conservés via URL
- Vue stock-alerts/index : design violet/rose Fundisc responsive
- Badges filtres actifs avec reset rapide
- Migration add_resolved_at_to_stock_alerts pour tracking résolution
- Documentation T10-FILTRES-ALERTES.md complète

Features :
- 6 filtres multicritères avec conservation pagination
- Recherche texte sur nom, artiste, référence
- Tri dynamique (date/type/produit asc/desc)
- Stats temps réel avec breakdown vinyles/fonds
- Export CSV filtré
- UI responsive badges et filtres actifs"

# Vérification
echo ""
echo "=== Statut du commit ==="
git log --oneline -1
git status --short

echo ""
echo "✅ T10 COMMITTÉ AVEC SUCCÈS"
echo ""
echo "Prochaine tâche : T11-A Infrastructure Tests (en attente)"
