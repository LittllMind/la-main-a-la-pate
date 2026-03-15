<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration propre pour ligne_ventes
 * - Crée la table avec FK vers ventes (cascade) et vinyles (set null)
 * - Ajoute colonne titre_vinyle pour snapshot historique
 * - Compatible SQLite et MySQL
 */
return new class extends Migration
{
    public function up(): void
    {
        // Supprimer l'ancienne table si elle existe
        Schema::dropIfExists('ligne_ventes');

        Schema::create('ligne_ventes', function (Blueprint $table) {
            $table->id();
            
            // FK vers ventes - CASCADE (si vente supprimée, supprimer les lignes)
            $table->foreignId('vente_id')->constrained('ventes')->onDelete('cascade');
            
            // FK vers vinyles - SET NULL (préserve l'historique si vinyle supprimé)
            // On utilise nullable() pour permettre SET NULL
            $table->foreignId('vinyle_id')->nullable()->constrained('vinyles')->onDelete('set null');
            
            // Snapshot du titre pour historique (même si vinyle supprimé)
            $table->string('titre_vinyle')->nullable();
            
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 8, 2);
            $table->decimal('total', 10, 2);
            $table->string('fond')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_ventes');
    }
};
