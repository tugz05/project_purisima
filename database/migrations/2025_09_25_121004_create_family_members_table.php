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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_profile_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('relationship_to_head'); // spouse, child, parent, sibling, other
            $table->date('birth_date')->nullable();
            $table->enum('sex', ['male', 'female', 'other'])->nullable();
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated', 'other'])->nullable();
            $table->enum('educational_attainment', ['none', 'elementary', 'high_school', 'college', 'graduate', 'other'])->nullable();
            $table->string('occupation')->nullable();
            $table->enum('employment_status', ['employed', 'unemployed', 'student', 'retired', 'housewife', 'other'])->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->boolean('is_working')->default(false);
            $table->boolean('is_student')->default(false);
            $table->boolean('is_senior_citizen')->default(false);
            $table->boolean('is_pwd')->default(false); // Person with Disability
            $table->text('disability_details')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();

            $table->index(['household_profile_id', 'relationship_to_head']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
