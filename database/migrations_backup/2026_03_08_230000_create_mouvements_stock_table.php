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
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entree', 'sortie']);
            $table->enum('produit_type', ['vinyle', 'miroir', 'dore', 'pochette']);
            $table->unsignedBigInteger('produit_id');
            $table->integer('quantite');
            $table->timestamp('date_mouvement');
            $table->foreignId('user_id')->constrained('users');
            $table->string('reference')->nullable(); // Lien commande/fournisseur
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['produit_type', 'produit_id']);
            $table->index('date_mouvement');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};