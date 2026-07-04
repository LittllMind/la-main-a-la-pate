<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('filename');                                // Nom original du fichier
            $table->string('stored_filename');                        // Nom stocké (unique)
            $table->string('path');                                    // Chemin relatif au disk
            $table->string('mime_type');                               // Type MIME
            $table->unsignedBigInteger('size')->nullable();           // Taille en octets
            $table->string('title')->nullable();                     // Titre affiché (ex: "Acte de décès")
            $table->text('description')->nullable();                  // Description courte
            $table->enum('category', ['source', 'annexe', 'ocr', 'audio', 'autre'])->default('source');
            $table->integer('position')->default(0);                  // Ordre d'affichage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_documents');
    }
};
