#!/bin/bash
# ═══════════════════════════════════════════════════════════════════════════
# REFRESH COMPLET — Compatible SQLite & MySQL
# ═══════════════════════════════════════════════════════════════════════════

echo "🎵 ==============================================="
echo "🎵  REFRESH COMPLET — vinyles-stock"
echo "🎵 ==============================================="
echo ""

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

# Détection de la base de données (defaut: sqlite)
DB_CONNECTION=$(grep DB_CONNECTION .env 2>/dev/null | cut -d= -f2 | tr -d '"' || echo "sqlite")

echo "💾 Base détectée: ${DB_CONNECTION}"
echo ""

echo "1️⃣  Cache clear..."
php artisan cache:clear 2>/dev/null
php artisan config:clear 2>/dev/null
php artisan view:clear 2>/dev/null

echo ""
echo "2️⃣  Fresh migrate + seed..."
php artisan migrate:fresh --seed --force

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ ERREUR: La migration a échoué. Vérifiez les logs ci-dessus."
    exit 1
fi

echo ""
echo "3️⃣  Résumé de la structure..."
echo "-----------------------------------------------"

count_table() {
    TABLE=$1
    if [ "$DB_CONNECTION" = "mysql" ]; then
        php artisan tinker --execute="echo DB::table('$TABLE')->count();" 2>/dev/null || echo "N/A"
    else
        # SQLite - utilise php artisan tinker pour la portabilité
        php artisan tinker --execute="echo DB::table('$TABLE')->count();" 2>/dev/null || echo "N/A"
    fi
}

echo "Table users:         $(count_table 'users')"
echo "Table vinyles:       $(count_table 'vinyles')"
echo "Table fonds:         $(count_table 'fonds')"
echo "Table ventes:        $(count_table 'ventes')"
echo "Table ligne_ventes:  $(count_table 'ligne_ventes')"
echo "Table mouvements_stock: $(count_table 'mouvements_stock')"

echo "-----------------------------------------------"
echo ""
echo "✅ Terminé !"
echo "🚀 Lance: npm run dev & php artisan serve"
echo "🌐 Puis teste : http://127.0.0.1:8000"
echo ""
echo "═══════════════════════════════════════════════════"
