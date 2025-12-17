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
        Schema::table('transactions', function (Blueprint $table) {
            // Add generated_document_data column if it doesn't exist
            if (!Schema::hasColumn('transactions', 'generated_document_data')) {
                $table->json('generated_document_data')->nullable()->after('ai_generated_at');
            }
            
            // Add generated_document_path column if it doesn't exist
            if (!Schema::hasColumn('transactions', 'generated_document_path')) {
                $table->string('generated_document_path')->nullable()->after('generated_document_data');
            }
            
            // Add document_generated_at column if it doesn't exist
            if (!Schema::hasColumn('transactions', 'document_generated_at')) {
                $table->timestamp('document_generated_at')->nullable()->after('generated_document_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'document_generated_at')) {
                $table->dropColumn('document_generated_at');
            }
            if (Schema::hasColumn('transactions', 'generated_document_path')) {
                $table->dropColumn('generated_document_path');
            }
            if (Schema::hasColumn('transactions', 'generated_document_data')) {
                $table->dropColumn('generated_document_data');
            }
        });
    }
};
