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
        Schema::create('bougies', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('parfum');           // was: artiste
            $table->string('nom');              // was: titre
            $table->string('collection')->nullable();  // was: album
            $table->string('format')->nullable();      // was: annee (120g/200g/300g)
            $table->string('type_cire')->nullable();   // was: genre (soja/paraffine)
            $table->integer('temps_brulure')->nullable(); // minutes
            $table->text('notes')->nullable();   // notes olfactives
            $table->decimal('prix', 10, 2);
            $table->integer('quantite')->default(0);
            $table->integer('seuil_alerte')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bougies');
    }
};
