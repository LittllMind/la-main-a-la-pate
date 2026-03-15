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
            // Source de la commande : web ou marche
            if (!Schema::hasColumn('orders', 'source')) {
                $table->enum('source', ['web', 'marche'])
                    ->default('web')
                    ->after('statut')
                    ->comment('Origine de la commande : web (tunnel e-commerce) ou marche (vente sur place)');
            }

            // Mode de paiement pour les ventes marché (pas Stripe)
            if (!Schema::hasColumn('orders', 'mode_paiement_marche')) {
                $table->enum('mode_paiement_marche', ['cash', 'cb_terminal', 'cheque', 'virement'])
                    ->nullable()
                    ->after('source')
                    ->comment('Mode de paiement pour les ventes sur place (hors Stripe)');
            }

            // Notes spécifiques vendeur (ex: "Client habitué", "Réduction accordée")
            if (!Schema::hasColumn('orders', 'notes_vendeur')) {
                $table->text('notes_vendeur')
                    ->nullable()
                    ->after('mode_paiement_marche')
                    ->comment('Notes internes pour les ventes sur place');
            }

            // Numéro de commande simplifié pour le marché (optionnel, affichage client)
            if (!Schema::hasColumn('orders', 'affichage_client')) {
                $table->string('affichage_client')
                    ->nullable()
                    ->after('notes_vendeur')
                    ->comment("Nom ou identifiant pour reconnaissance client (ex: 'M. Dupont' ou 'Table 3')");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'mode_paiement_marche', 'notes_vendeur', 'affichage_client']);
        });
    }
};
