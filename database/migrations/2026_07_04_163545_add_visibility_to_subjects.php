<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Ajoute colonne visibility après status
            $table->enum('visibility', ['public', 'citoyen', 'admin'])
                ->default('citoyen')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
