<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bug #4: Ajouter onDelete('cascade') sur toutes les FK liées aux ventes
     * NOTE: Migration désactivée - suppression de ligne_ventes échoue
     */
    public function up(): void
    {
        // Migration disabled due to constraint removal failure
        // Legacy 'ventes' and 'ligne_ventes' tables kept as-is
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Les contraintes restent, on ne les supprime pas en down
        // pour éviter les erreurs si les tables ont des données
    }
};
