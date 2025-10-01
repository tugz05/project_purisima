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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Unique identifier (e.g., 'barangay_clearance')
            $table->string('name'); // Display name (e.g., 'Barangay Clearance')
            $table->text('description')->nullable(); // Detailed description
            $table->decimal('fee_amount', 10, 2)->default(0); // Processing fee
            $table->json('required_documents')->nullable(); // Array of required documents
            $table->json('processing_steps')->nullable(); // Array of processing steps
            $table->integer('processing_days')->default(1); // Expected processing time in days
            $table->boolean('is_active')->default(true); // Whether this type is available
            $table->boolean('requires_payment')->default(true); // Whether payment is required
            $table->boolean('requires_approval')->default(false); // Whether requires additional approval
            $table->string('category')->nullable(); // Category grouping (e.g., 'certificates', 'permits')
            $table->integer('sort_order')->default(0); // Display order
            $table->text('notes')->nullable(); // Internal notes for staff
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
