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
        // First, set invalid dates to null
        DB::table('barangay_officials')->where('term_start', '1970-01-01')->update(['term_start' => null]);
        DB::table('barangay_officials')->where('term_end', '1970-01-01')->update(['term_end' => null]);

        // Then change the column types
        Schema::table('barangay_officials', function (Blueprint $table) {
            $table->integer('term_start')->nullable()->change();
            $table->integer('term_end')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangay_officials', function (Blueprint $table) {
            $table->date('term_start')->nullable()->change();
            $table->date('term_end')->nullable()->change();
        });
    }
};
