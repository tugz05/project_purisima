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
        Schema::table('household_members', function (Blueprint $table) {
            $table->foreignId('linked_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('invitation_status', ['pending', 'accepted', 'declined'])
                ->nullable()
                ->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('household_members', function (Blueprint $table) {
            $table->dropForeign(['linked_user_id']);
            $table->dropColumn(['linked_user_id', 'invitation_status']);
        });
    }
};
