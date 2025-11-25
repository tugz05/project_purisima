<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Convert type enum to string if it's still an enum
        // Check if type column exists and what type it is
        $typeColumn = DB::select("SHOW COLUMNS FROM transactions WHERE Field = 'type'");
        
        if (!empty($typeColumn) && str_contains($typeColumn[0]->Type, 'enum')) {
            // Type is still enum, convert to string
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('type', 100)->nullable()->change();
            });
        } else {
            // Type is already string, just ensure it's nullable and has proper length
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->string('type', 100)->nullable()->change();
                });
            } catch (\Exception $e) {
                // Column might already be the correct type, continue
            }
        }

        // Step 2: Add document_type_id column (nullable first, will populate before adding constraint)
        if (!Schema::hasColumn('transactions', 'document_type_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('document_type_id')->nullable()->after('staff_id');
            });
        }

        // Step 3: Populate document_type_id based on type code
        // Match existing type codes to document_types.code
        $documentTypes = DB::table('document_types')->get()->keyBy('code');
        
        if ($documentTypes->isNotEmpty()) {
            DB::table('transactions')->whereNull('document_type_id')->chunkById(100, function ($transactions) use ($documentTypes) {
                foreach ($transactions as $transaction) {
                    if ($transaction->type && isset($documentTypes[$transaction->type])) {
                        DB::table('transactions')
                            ->where('id', $transaction->id)
                            ->update(['document_type_id' => $documentTypes[$transaction->type]->id]);
                    }
                }
            });
        }

        // Step 4: Add foreign key constraint after populating data
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('document_type_id')
                    ->references('id')
                    ->on('document_types')
                    ->onDelete('restrict');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist, continue
        }

        // Step 5: Remove description column if it exists
        if (Schema::hasColumn('transactions', 'description')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        // Step 6: Add index for document_type_id for better query performance
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('document_type_id');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop document_type_id foreign key and index
        if (Schema::hasColumn('transactions', 'document_type_id')) {
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropForeign(['document_type_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropIndex(['document_type_id']);
                });
            } catch (\Exception $e) {
                // Index might not exist
            }
            
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('document_type_id');
            });
        }

        // Step 2: Re-add description column if it doesn't exist
        if (!Schema::hasColumn('transactions', 'description')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }

        // Step 3: Convert type back to enum (if needed)
        // Note: This is a simplified version - in production, you might want to restore exact enum values
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->enum('type', [
                    'barangay-clearance',
                    'residency-certificate',
                    'business-permit',
                    'indigency-certificate',
                    'cedula',
                    'death-certificate',
                    'birth-certificate',
                    'marriage-certificate',
                    'other'
                ])->nullable()->change();
            });
        } catch (\Exception $e) {
            // Type might already be enum or conversion might fail
        }
    }
};
