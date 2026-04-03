<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->onDelete('cascade');
            $table->unsignedBigInteger('vinyle_id'); // Juste la colonne, pas de FK ici
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
