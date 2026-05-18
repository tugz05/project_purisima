<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_outbound_messages', function (Blueprint $table) {
            $table->string('to')->after('id');
            $table->text('message')->after('to');
            $table->string('status')->default('pending')->after('message'); // pending|sent|failed
            $table->string('provider_message_id')->nullable()->after('status');
            $table->text('error_message')->nullable()->after('provider_message_id');
            $table->string('context_type')->nullable()->after('error_message'); // transaction|otp|broadcast|verification
            $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
            $table->unsignedTinyInteger('attempt_number')->default(1)->after('context_id');
            $table->timestamp('sent_at')->nullable()->after('attempt_number');

            $table->index(['to', 'status']);
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sms_outbound_messages', function (Blueprint $table) {
            $table->dropColumn([
                'to', 'message', 'status', 'provider_message_id',
                'error_message', 'context_type', 'context_id',
                'attempt_number', 'sent_at',
            ]);
        });
    }
};
