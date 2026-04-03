<?php
// database/migrations/xxxx_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('numero_commande')->unique(); // CMD-2024-0001
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Infos client (même si compte supprimé)
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('telephone');
            $table->text('adresse')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('ville')->nullable();

            // Commande
            $table->decimal('total', 10, 2);
            $table->enum('statut', [
                'en_attente',
                'en_preparation',
                'prete',
                'livree',
                'annulee'
            ])->default('en_attente');

            $table->text('notes')->nullable(); // Notes admin
            $table->text('notes_client')->nullable(); // Message du client

            $table->timestamp('validee_at')->nullable();
            $table->timestamp('preparee_at')->nullable();
            $table->timestamp('prete_at')->nullable();
            $table->timestamp('livree_at')->nullable();
            $table->timestamp('annulee_at')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
