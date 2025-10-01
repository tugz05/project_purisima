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
        Schema::create('household_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('household_head_name');
            $table->string('household_head_relationship')->default('self'); // self, spouse, parent, etc.
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->enum('income_source', ['employment', 'business', 'pension', 'remittance', 'other'])->nullable();
            $table->text('income_source_details')->nullable();
            $table->integer('total_family_members')->default(1);
            $table->integer('working_members')->default(0);
            $table->integer('dependent_members')->default(0);
            $table->enum('housing_type', ['owned', 'rented', 'borrowed', 'other'])->nullable();
            $table->text('housing_details')->nullable();
            $table->boolean('has_vehicle')->default(false);
            $table->text('vehicle_details')->nullable();
            $table->boolean('has_livestock')->default(false);
            $table->text('livestock_details')->nullable();
            $table->text('additional_notes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_profiles');
    }
};
