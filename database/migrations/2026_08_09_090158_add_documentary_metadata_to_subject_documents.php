<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_documents', function (Blueprint $table) {
            $table->date('document_date')->nullable()->after('description');
            $table->string('document_type', 80)->nullable()->after('document_date');
            $table->string('author', 255)->nullable()->after('document_type');
            $table->string('recipient', 255)->nullable()->after('author');
            $table->text('source_reference')->nullable()->after('recipient');
            $table->string('representation_type', 20)->nullable()->after('source_reference');
            $table->boolean('redacted')->default(false)->after('representation_type');
            $table->text('establishes')->nullable()->after('redacted');
            $table->text('limitations')->nullable()->after('establishes');
        });
    }

    public function down(): void
    {
        Schema::table('subject_documents', function (Blueprint $table) {
            $table->dropColumn([
                'document_date',
                'document_type',
                'author',
                'recipient',
                'source_reference',
                'representation_type',
                'redacted',
                'establishes',
                'limitations',
            ]);
        });
    }
};
