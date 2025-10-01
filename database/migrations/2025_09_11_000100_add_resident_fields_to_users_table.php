<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Auth/provider details
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('resident');
            }
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable()->index();
            $table->string('photo_url')->nullable();

            // Resident profile
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('sex', ['male','female','other'])->nullable();
            $table->enum('civil_status', ['single','married','widowed','separated','other'])->nullable();
            $table->string('occupation')->nullable();

            // Address focused on Purok in Barangay Purisima, Tago, Surigao del Sur, Philippines
            $table->string('purok')->nullable();
            $table->string('barangay')->default('Purisima');
            $table->string('municipality')->default('Tago');
            $table->string('province')->default('Surigao del Sur');
            $table->string('country')->default('Philippines');

            // Completion flag
            $table->timestamp('profile_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'provider', 'provider_id', 'photo_url',
                'first_name', 'middle_name', 'last_name', 'phone', 'birth_date', 'sex', 'civil_status', 'occupation',
                'purok', 'barangay', 'municipality', 'province', 'country',
                'profile_completed_at',
            ]);
        });
    }
};


