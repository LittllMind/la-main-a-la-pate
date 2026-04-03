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
        Schema::table('fonds', function (Blueprint $table) {
            $table->decimal('prix_vente', 8, 2)->default(0)->after('prix_achat');
            $table->boolean('actif')->default(true)->after('prix_vente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fonds', function (Blueprint $table) {
            $table->dropColumn(['prix_vente', 'actif']);
        });
    }
};