<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Clé étrangère vers ventes (nullable car pas toutes les commandes viennent d'une vente kiosque)
            $table->foreignId('vente_id')->nullable()->after('id')->constrained('ventes')->onDelete('set null');
            
            // Index pour performance
            $table->index('vente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['vente_id']);
            $table->dropIndex(['vente_id']);
            $table->dropColumn('vente_id');
        });
    }
};
