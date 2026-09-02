<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 40);
            $table->string('visitor_key', 64);
            $table->string('path', 500);
            $table->string('document_key', 300)->nullable();
            $table->string('referrer_host', 200)->nullable();
            $table->string('user_agent_family', 40)->nullable();
            $table->string('device_family', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['event_type', 'created_at']);
            $table->index(['visitor_key', 'created_at']);
            $table->index(['document_key', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
