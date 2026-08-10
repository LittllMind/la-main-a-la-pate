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
        Schema::table('subject_versions', function (Blueprint $table) {
            $table->longText('citizen_body')->nullable()->after('body');
            $table->longText('public_body')->nullable()->after('citizen_body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_versions', function (Blueprint $table) {
            $table->dropColumn(['citizen_body', 'public_body']);
        });
    }
};
