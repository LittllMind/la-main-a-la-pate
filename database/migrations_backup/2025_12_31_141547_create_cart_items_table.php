<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('vinyle_id')
                ->constrained()
                ->cascadeOnDelete();

            // lien vers la table fonds (nullable car standard n'a pas de fond)
            $table->foreignId('fond_id')
                ->nullable()
                ->constrained('fonds')
                ->nullOnDelete();

            $table->integer('quantite');
            $table->decimal('prix_unitaire', 8, 2);
            $table->timestamps();

            // ✅ Unicité : même vinyle + même fond dans le même panier
            $table->unique(
                ['cart_id', 'vinyle_id', 'fond_id'],
                'unique_cart_vinyle'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
