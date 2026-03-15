<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter la colonne seuil_alerte à la table vinyles
 * Pour les alertes de stock automatiques
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinyles', function (Blueprint $table) {
            // Vérifier si la colonne n'existe pas déjà
            if (!Schema::hasColumn('vinyles', 'seuil_alerte')) {
                $table->integer('seuil_alerte')->default(3)->comment('Seuil minimum avant alerte stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vinyles', function (Blueprint $table) {
            if (Schema::hasColumn('vinyles', 'seuil_alerte')) {
                $table->dropColumn('seuil_alerte');
            }
        });
    }
};
