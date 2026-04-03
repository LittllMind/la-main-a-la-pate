<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            
            // Polymorphique : peut pointer vers Vinyle ou Fond
            $table->string('alertable_type');      // 'App\Models\Vinyle' ou 'App\Models\Fond'
            $table->unsignedBigInteger('alertable_id');
            
            // Données au moment de l'alerte
            $table->integer('quantite_actuelle');
            $table->integer('seuil_alerte');       // 3 pour vinyles, 20 pour fonds
            
            // Gestion du cycle de vie
            $table->enum('statut', ['actif', 'resolu'])->default('actif');
            $table->timestamp('derniere_notification_envoyee')->nullable();
            
            $table->timestamps();
            
            // Index pour performances
            $table->index(['alertable_type', 'alertable_id']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
