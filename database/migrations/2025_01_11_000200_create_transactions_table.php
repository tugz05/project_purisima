<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // Custom unique ID
            $table->foreignId('resident_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', [
                'barangay_clearance',
                'residency_certificate',
                'business_permit',
                'indigency_certificate',
                'cedula',
                'death_certificate',
                'birth_certificate',
                'marriage_certificate',
                'other'
            ]);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('staff_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('required_documents')->nullable(); // Array of required documents
            $table->json('submitted_documents')->nullable(); // Array of submitted document paths
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->boolean('fee_paid')->default(false);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
