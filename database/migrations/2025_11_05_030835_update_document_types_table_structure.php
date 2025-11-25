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
        Schema::table('document_types', function (Blueprint $table) {
            // Check if columns exist and add/rename them
            if (Schema::hasColumn('document_types', 'base_fee') && !Schema::hasColumn('document_types', 'fee_amount')) {
                $table->decimal('fee_amount', 10, 2)->default(0)->after('description');
            }
            
            if (Schema::hasColumn('document_types', 'active') && !Schema::hasColumn('document_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('processing_days');
            }
            
            // Add missing columns - order matters!
            if (!Schema::hasColumn('document_types', 'category')) {
                $table->string('category')->nullable()->after('requires_approval');
            }
            
            if (!Schema::hasColumn('document_types', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('category');
            }
            
            if (!Schema::hasColumn('document_types', 'notes')) {
                $table->text('notes')->nullable()->after('sort_order');
            }
            
            // Add required_documents if not exists (may be called required_information)
            if (!Schema::hasColumn('document_types', 'required_documents')) {
                if (Schema::hasColumn('document_types', 'required_information')) {
                    // Rename required_information to required_documents
                    $table->renameColumn('required_information', 'required_documents');
                } else {
                    $table->json('required_documents')->nullable()->after('fee_amount');
                }
            }
            
            // Add processing_steps if not exists
            if (!Schema::hasColumn('document_types', 'processing_steps')) {
                $table->json('processing_steps')->nullable()->after('required_documents');
            }
        });
        
        // Migrate data from old columns to new columns
        if (Schema::hasColumn('document_types', 'base_fee') && Schema::hasColumn('document_types', 'fee_amount')) {
            DB::statement('UPDATE document_types SET fee_amount = base_fee WHERE fee_amount = 0 AND base_fee IS NOT NULL');
        }
        
        if (Schema::hasColumn('document_types', 'active') && Schema::hasColumn('document_types', 'is_active')) {
            DB::statement('UPDATE document_types SET is_active = active WHERE is_active IS NULL');
        }
        
        // Drop old columns if they exist and new ones are in place
        Schema::table('document_types', function (Blueprint $table) {
            if (Schema::hasColumn('document_types', 'base_fee') && Schema::hasColumn('document_types', 'fee_amount')) {
                $table->dropColumn('base_fee');
            }
            
            if (Schema::hasColumn('document_types', 'active') && Schema::hasColumn('document_types', 'is_active')) {
                $table->dropColumn('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            // Restore old columns if needed
            if (!Schema::hasColumn('document_types', 'base_fee') && Schema::hasColumn('document_types', 'fee_amount')) {
                $table->decimal('base_fee', 10, 2)->default(0)->after('description');
            }
            
            if (!Schema::hasColumn('document_types', 'active') && Schema::hasColumn('document_types', 'is_active')) {
                $table->boolean('active')->default(true)->after('processing_days');
            }
            
            // Drop new columns
            if (Schema::hasColumn('document_types', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            
            if (Schema::hasColumn('document_types', 'category')) {
                $table->dropColumn('category');
            }
            
            if (Schema::hasColumn('document_types', 'notes')) {
                $table->dropColumn('notes');
            }
            
            if (Schema::hasColumn('document_types', 'required_documents') && !Schema::hasColumn('document_types', 'required_information')) {
                $table->renameColumn('required_documents', 'required_information');
            }
            
            if (Schema::hasColumn('document_types', 'processing_steps')) {
                $table->dropColumn('processing_steps');
            }
        });
    }
};
