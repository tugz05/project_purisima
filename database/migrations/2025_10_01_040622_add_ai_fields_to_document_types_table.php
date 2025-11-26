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
        Schema::table('document_types', function (Blueprint $table) {
            // Check which column exists and add after the appropriate one
            $afterColumn = Schema::hasColumn('document_types', 'is_active') 
                ? 'is_active' 
                : (Schema::hasColumn('document_types', 'active') ? 'active' : 'requires_approval');
            
            $table->boolean('use_ai_generation')->default(false)->after($afterColumn);
            $table->text('ai_prompt_template')->nullable()->after('use_ai_generation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['use_ai_generation', 'ai_prompt_template']);
        });
    }
};
