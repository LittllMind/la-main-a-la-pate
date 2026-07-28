<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->fullText(['title', 'body']);
        });

        Schema::table('subject_documents', function (Blueprint $table) {
            $table->fullText(['filename', 'description']);
        });
    }

    public function down(): void
    {
        Schema::table('subject_documents', function (Blueprint $table) {
            $table->dropFullText(['filename', 'description']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropFullText(['title', 'body']);
        });
    }
};
