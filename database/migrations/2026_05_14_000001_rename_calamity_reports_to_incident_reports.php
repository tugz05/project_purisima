<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename the table
        Schema::rename('calamity_reports', 'incident_reports');

        // Rename the calamity_type column to incident_type and update the enum values
        Schema::table('incident_reports', function (Blueprint $table) {
            // Drop the old enum column
            $table->dropColumn('calamity_type');
        });

        Schema::table('incident_reports', function (Blueprint $table) {
            // Add the new enum column with updated values
            $table->enum('incident_type', [
                'fire',
                'flood',
                'typhoon',
                'earthquake',
                'landslide',
                'medical_emergency',
                'accident',
                'crime',
                'other',
            ])->default('other')->after('location_notes');
        });

        // Drop old indexes (they were renamed with the table but reference old column)
        // Re-create the correct indexes
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->index(['status', 'incident_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table) {
            $table->dropColumn('incident_type');
        });

        Schema::table('incident_reports', function (Blueprint $table) {
            $table->enum('calamity_type', [
                'typhoon',
                'flood',
                'earthquake',
                'fire',
                'landslide',
                'drought',
                'other',
            ])->default('other')->after('location_notes');
        });

        Schema::rename('incident_reports', 'calamity_reports');
    }
};
