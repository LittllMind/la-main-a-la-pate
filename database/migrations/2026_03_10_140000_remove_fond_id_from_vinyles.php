<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supprime fond_id des vinyles - le fond est choisi à l'achat, pas stocké sur le vinyle
     */
    public function up(): void
    {
        Schema::table('vinyles', function (Blueprint $table) {
            $table->dropForeign(['fond_id']);
            $table->dropColumn('fond_id');
        });
    }

    public function down(): void
    {
        Schema::table('vinyles', function (Blueprint $table) {
            $table->foreignId('fond_id')->nullable()->constrained('fonds');
        });
    }
};