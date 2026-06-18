<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('theme');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subject_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('body');
            $table->string('change_summary', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('subject_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('body');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('color', 7)->default('')->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_comments');
        Schema::dropIfExists('subject_versions');
        Schema::dropIfExists('subjects');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'color')) {
                    $table->dropColumn('color');
                }
            });
        }
    }
};
