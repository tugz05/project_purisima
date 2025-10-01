<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, we need to modify the column to allow the new enum values
        // Since MySQL doesn't support modifying enum values directly, we need to:
        // 1. Add a temporary column
        // 2. Copy data with mapping
        // 3. Drop the old column
        // 4. Rename the new column

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type_new', [
                'barangay-clearance',
                'residency-certificate',
                'business-permit',
                'indigency-certificate',
                'cedula',
                'death-certificate',
                'birth-certificate',
                'marriage-certificate',
                'other'
            ])->nullable()->after('type');
        });

        // Map old enum values to new ones
        $mapping = [
            'barangay_clearance' => 'barangay-clearance',
            'residency_certificate' => 'residency-certificate',
            'business_permit' => 'business-permit',
            'indigency_certificate' => 'indigency-certificate',
            'cedula' => 'cedula',
            'death_certificate' => 'death-certificate',
            'birth_certificate' => 'birth-certificate',
            'marriage_certificate' => 'marriage-certificate',
            'other' => 'other'
        ];

        // Update the new column with mapped values
        foreach ($mapping as $oldValue => $newValue) {
            DB::table('transactions')
                ->where('type', $oldValue)
                ->update(['type_new' => $newValue]);
        }

        // Drop the old column
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Rename the new column
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('type_new', 'type');
        });
    }

    public function down(): void
    {
        // Reverse the process
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type_old', [
                'barangay_clearance',
                'residency_certificate',
                'business_permit',
                'indigency_certificate',
                'cedula',
                'death_certificate',
                'birth_certificate',
                'marriage_certificate',
                'other'
            ])->nullable()->after('type');
        });

        // Reverse mapping
        $reverseMapping = [
            'barangay-clearance' => 'barangay_clearance',
            'residency-certificate' => 'residency_certificate',
            'business-permit' => 'business_permit',
            'indigency-certificate' => 'indigency_certificate',
            'cedula' => 'cedula',
            'death-certificate' => 'death_certificate',
            'birth-certificate' => 'birth_certificate',
            'marriage-certificate' => 'marriage_certificate',
            'other' => 'other'
        ];

        // Update the old column with reverse mapped values
        foreach ($reverseMapping as $newValue => $oldValue) {
            DB::table('transactions')
                ->where('type', $newValue)
                ->update(['type_old' => $oldValue]);
        }

        // Drop the new column
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Rename the old column back
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('type_old', 'type');
        });
    }
};
