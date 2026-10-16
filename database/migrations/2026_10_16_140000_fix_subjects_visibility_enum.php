<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Étape 1 : relâcher l'enum legacy pour permettre la conversion des valeurs.
        DB::statement("
            ALTER TABLE subjects
            MODIFY COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'citizen'
        ");

        // Étape 2 : mapper les anciennes valeurs vers l'enum App\Models\VisibilityLevel.
        DB::statement("
            UPDATE subjects
            SET visibility = CASE visibility
                WHEN 'citoyen' THEN 'citizen'
                WHEN 'admin' THEN 'working'
                WHEN 'public' THEN 'public'
                ELSE 'citizen'
            END
        ");

        // Étape 3 : rétablir le type enum avec les valeurs canoniques.
        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('visibility', ['working', 'citizen', 'public'])
                ->default('citizen')
                ->change();
        });
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE subjects
            MODIFY COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'citoyen'
        ");

        DB::statement("
            UPDATE subjects
            SET visibility = CASE visibility
                WHEN 'citizen' THEN 'citoyen'
                WHEN 'working' THEN 'admin'
                WHEN 'public' THEN 'public'
                ELSE 'citoyen'
            END
        ");

        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('visibility', ['public', 'citoyen', 'admin'])
                ->default('citoyen')
                ->change();
        });
    }
};
