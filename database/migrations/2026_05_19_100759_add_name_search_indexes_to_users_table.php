<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['last_name', 'first_name'], 'users_last_first_name_index');
            $table->index('middle_name', 'users_middle_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_last_first_name_index');
            $table->dropIndex('users_middle_name_index');
        });
    }
};
