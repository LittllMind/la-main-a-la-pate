<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Soft Delete pour LigneVentes
 *
 * Préserve l'historique des ventes si un vinyle est supprimé.
 * - Change onDelete('cascade') → onDelete('set null') sur vinyle_id
 * - Ajoute colonne 'titre_vinyle' pour snapshot du titre
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Désactiver les contraintes FK temporairement
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // 1. Ajouter colonne snapshot si non existante
            if (Schema::hasTable('ligne_ventes') && !Schema::hasColumn('ligne_ventes', 'titre_vinyle')) {
                Schema::table('ligne_ventes', function (Blueprint $table) {
                    $table->string('titre_vinyle')->nullable()->after('vinyle_id');
                });
            }

            // 2. Modifier la contrainte vinyle_id : CASCADE → SET NULL
            if (Schema::hasTable('ligne_ventes')) {
                // Supprimer contrainte existante
                try {
                    DB::statement('ALTER TABLE ligne_ventes DROP FOREIGN KEY ligne_ventes_vinyle_id_foreign');
                } catch (\Exception $e) {
                    // Essayer avec un autre nom
                    try {
                        DB::statement('ALTER TABLE ligne_ventes DROP FOREIGN KEY ligne_ventes_vinyl_id_foreign');
                    } catch (\Exception $e2) {}
                }

                // Rendre la colonne nullable
                DB::statement('ALTER TABLE ligne_ventes MODIFY COLUMN vinyle_id BIGINT UNSIGNED NULL');

                // Recréer la contrainte avec ON DELETE SET NULL
                DB::statement('
                    ALTER TABLE ligne_ventes
                    ADD CONSTRAINT ligne_ventes_vinyle_id_foreign
                    FOREIGN KEY (vinyle_id) REFERENCES vinyles(id)
                    ON DELETE SET NULL
                ');
            }

            // 3. Backfill : remplir titre_vinyle pour les lignes existantes
            $this->backfillTitres();

        } finally {
            // Réactiver les contraintes FK
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // 1. Remettre contrainte CASCADE
            if (Schema::hasTable('ligne_ventes')) {
                try {
                    DB::statement('ALTER TABLE ligne_ventes DROP FOREIGN KEY ligne_ventes_vinyle_id_foreign');
                } catch (\Exception $e) {}

                // Rendre la colonne NOT NULL (si pas de lignes avec NULL)
                $nullCount = DB::table('ligne_ventes')->whereNull('vinyle_id')->count();
                if ($nullCount === 0) {
                    DB::statement('ALTER TABLE ligne_ventes MODIFY COLUMN vinyle_id BIGINT UNSIGNED NOT NULL');
                }

                // Recréer avec CASCADE
                DB::statement('
                    ALTER TABLE ligne_ventes
                    ADD CONSTRAINT ligne_ventes_vinyle_id_foreign
                    FOREIGN KEY (vinyle_id) REFERENCES vinyles(id)
                    ON DELETE CASCADE
                ');
            }

            // 2. Supprimer colonne titre_vinyle
            if (Schema::hasTable('ligne_ventes') && Schema::hasColumn('ligne_ventes', 'titre_vinyle')) {
                Schema::table('ligne_ventes', function (Blueprint $table) {
                    $table->dropColumn('titre_vinyle');
                });
            }

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Backfill: Remplir titre_vinyle depuis les vinyles existants
     */
    private function backfillTitres(): void
    {
        if (!Schema::hasColumn('ligne_ventes', 'titre_vinyle')) {
            return;
        }

        try {
            // Récupérer les lignes sans titre_vinyle mais avec vinyle_id
            $lignes = DB::table('ligne_ventes')
                ->whereNull('titre_vinyle')
                ->whereNotNull('vinyle_id')
                ->get();

            foreach ($lignes as $ligne) {
                $vinyle = DB::table('vinyles')
                    ->where('id', $ligne->vinyle_id)
                    ->first();

                if ($vinyle) {
                    $titre = $vinyle->nom ?? $vinyle->titre ?? 'Vinyle #' . $vinyle->id;
                    DB::table('ligne_ventes')
                        ->where('id', $ligne->id)
                        ->update(['titre_vinyle' => $titre]);
                }
            }
        } catch (\Exception $e) {
            // Si erreur, on continue silencieusement
        }
    }
};