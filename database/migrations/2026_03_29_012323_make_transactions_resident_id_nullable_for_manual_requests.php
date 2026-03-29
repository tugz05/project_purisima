<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['resident_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('resident_id')->nullable()->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('resident_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['resident_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('resident_id')->nullable(false)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('resident_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
