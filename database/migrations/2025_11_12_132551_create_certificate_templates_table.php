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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('name'); // Template name (e.g., "Standard Certification Template")
            $table->text('description')->nullable(); // Template description
            $table->longText('template_content'); // HTML/text content with tags like {{name}}, {{date}}, etc.
            $table->json('available_tags')->nullable(); // Array of available tags with descriptions
            $table->json('required_fields')->nullable(); // Array of required field definitions for the form
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Only one default per document_type
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Ensure only one default template per document type
            $table->unique(['document_type_id', 'is_default'], 'unique_default_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
