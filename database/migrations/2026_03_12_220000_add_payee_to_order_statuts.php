<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute 'payee' aux statuts de commande pour les ventes sur place.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Modifier l'ENUM pour inclure 'payee' (vente marché complète)
            $table->enum('statut', [
                'en_attente',
                'payee',
                'en_preparation',
                'prete',
                'livree',
                'annulee'
            ])->default('en_attente')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // On ne supprime pas en rollback pour éviter la perte de données
        });
    }
};
