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
        Schema::create('calamity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('users')->onDelete('set null');

            // Location data
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('address')->nullable();
            $table->text('location_notes')->nullable();

            // Calamity information
            $table->enum('calamity_type', ['typhoon', 'flood', 'earthquake', 'fire', 'landslide', 'drought', 'other'])->default('other');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description')->nullable();

            // Needs/Requirements
            $table->json('needs')->nullable(); // Array of needs like ['food', 'water', 'medicine', 'shelter', 'clothing', 'evacuation']
            $table->text('specific_needs')->nullable(); // Free text for specific needs
            $table->integer('number_of_people')->default(1);
            $table->boolean('has_elderly')->default(false);
            $table->boolean('has_children')->default(false);
            $table->boolean('has_pwd')->default(false);
            $table->boolean('has_pregnant')->default(false);
            $table->text('medical_conditions')->nullable();

            // Status and response
            $table->enum('status', ['pending', 'acknowledged', 'in_progress', 'assisted', 'resolved'])->default('pending');
            $table->text('staff_notes')->nullable();
            $table->text('assistance_provided')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('assisted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Location sharing settings
            $table->boolean('location_shared')->default(true);
            $table->timestamp('location_updated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['resident_id', 'status']);
            $table->index(['status', 'calamity_type']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calamity_reports');
    }
};
