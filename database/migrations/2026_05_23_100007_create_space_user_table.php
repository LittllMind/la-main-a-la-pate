<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('space_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('notify')->default(true);
            $table->timestamps();
            $table->unique(['space_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('space_user'); }
};
