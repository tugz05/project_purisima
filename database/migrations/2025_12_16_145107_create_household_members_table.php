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
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('relationship'); // self, spouse, child, parent, sibling, grandparent, grandchild, other
            $table->date('birth_date')->nullable();
            $table->enum('sex', ['male', 'female', 'other'])->nullable();
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated', 'divorced', 'other'])->nullable();
            $table->string('occupation')->nullable();
            $table->string('educational_attainment')->nullable();
            $table->boolean('is_working')->default(false);
            $table->boolean('is_student')->default(false);
            $table->boolean('is_senior_citizen')->default(false);
            $table->boolean('is_pwd')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'relationship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
