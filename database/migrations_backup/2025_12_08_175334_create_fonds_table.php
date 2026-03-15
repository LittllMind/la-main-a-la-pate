<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonds', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();   // 'standard' (optionnel), 'miroir', 'dore'
            $table->integer('quantite')->default(0);
            $table->decimal('prix_achat', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonds');
    }
};
