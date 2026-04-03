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
            // Ajouter les champs manquants pour le checkout (seulement s'ils n'existent pas)
            if (!Schema::hasColumn('orders', 'shipping_nom')) {
                $table->string('shipping_nom')->nullable()->after('ville');
                $table->string('shipping_prenom')->nullable()->after('shipping_nom');
                $table->string('shipping_email')->nullable()->after('shipping_prenom');
                $table->string('shipping_telephone')->nullable()->after('shipping_email');
                $table->text('shipping_adresse')->nullable()->after('shipping_telephone');
                $table->string('shipping_code_postal')->nullable()->after('shipping_adresse');
                $table->string('shipping_ville')->nullable()->after('shipping_code_postal');
                $table->string('shipping_pays')->nullable()->after('shipping_ville');
                $table->text('shipping_instructions')->nullable()->after('shipping_pays');
            }
            
            // Ajouter champ status (pour compatibilité avec le code)
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('pending')->after('statut');
            }
            
            // Champs facturation
            if (!Schema::hasColumn('orders', 'billing_nom')) {
                $table->string('billing_nom')->nullable()->after('shipping_instructions');
                $table->string('billing_prenom')->nullable()->after('billing_nom');
                $table->string('billing_email')->nullable()->after('billing_prenom');
                $table->string('billing_telephone')->nullable()->after('billing_email');
                $table->text('billing_adresse')->nullable()->after('billing_prenom');
                $table->string('billing_code_postal')->nullable()->after('billing_adresse');
                $table->string('billing_ville')->nullable()->after('billing_code_postal');
                $table->string('billing_pays')->nullable()->after('billing_ville');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
