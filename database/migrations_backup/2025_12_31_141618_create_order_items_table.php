<?php
// database/migrations/xxxx_create_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('vinyle_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('fond_id')->nullable()->constrained('fonds')->onDelete('set null');
            
            // Snapshot des données au moment de la commande
            $table->string('titre_vinyle');
            $table->string('artiste_vinyle')->nullable();
            $table->string('reference_vinyle')->nullable();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 8, 2);
            $table->decimal('total', 10, 2); // quantite * prix_unitaire
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
