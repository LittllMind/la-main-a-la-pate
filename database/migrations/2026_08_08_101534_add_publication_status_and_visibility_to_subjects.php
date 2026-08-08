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
        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('citizen_status', ['draft', 'published', 'hidden'])->default('draft')->after('citizen_body');
            $table->enum('public_status', ['draft', 'published', 'hidden'])->default('draft')->after('public_body');
            $table->timestamp('citizen_published_at')->nullable()->after('citizen_status');
            $table->timestamp('public_published_at')->nullable()->after('public_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('citizen_status');
            $table->dropColumn('public_status');
            $table->dropColumn('citizen_published_at');
            $table->dropColumn('public_published_at');
        });
    }
};
