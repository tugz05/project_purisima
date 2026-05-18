<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_broadcasts', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('message')->after('title');
            $table->unsignedInteger('recipients_count')->default(0)->after('message');
            $table->unsignedInteger('sent_count')->default(0)->after('recipients_count');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
            $table->string('status')->default('pending')->after('failed_count'); // pending|processing|completed|failed
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('sms_broadcasts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'title', 'message', 'recipients_count', 'sent_count',
                'failed_count', 'status', 'scheduled_at', 'started_at',
                'completed_at', 'created_by',
            ]);
        });
    }
};
