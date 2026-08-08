<?php

use App\Models\VisibilityLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subject_documents', function (Blueprint $table) {
            $table->string('visibility', 20)->default(VisibilityLevel::Working->value)->after('subject_id');
            $table->index('visibility');
        });

        // Tous les documents existants reçoivent explicitement la visibilité la plus restrictive.
        DB::table('subject_documents')->update(['visibility' => VisibilityLevel::Working->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_documents', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
