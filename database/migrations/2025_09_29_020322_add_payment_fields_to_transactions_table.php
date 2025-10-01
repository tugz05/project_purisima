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
            // Payment status and method
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->after('fee_paid');
            $table->enum('payment_method', ['cash', 'gcash', 'paymaya', 'bank_transfer', 'check'])->nullable()->after('payment_status');

            // Payment details
            $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('amount_paid');
            $table->text('payment_notes')->nullable()->after('payment_reference');

            // Payment timestamps
            $table->timestamp('payment_date')->nullable()->after('payment_notes');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_date');
            $table->unsignedBigInteger('payment_verified_by')->nullable()->after('payment_verified_at');

            // Receipt and proof of payment
            $table->string('receipt_number')->nullable()->after('payment_verified_by');
            $table->json('payment_proof')->nullable()->after('receipt_number'); // Store file paths for payment proof

            // Foreign key for payment verifier
            $table->foreign('payment_verified_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better performance
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['payment_date']);

            $table->dropColumn([
                'payment_status',
                'payment_method',
                'amount_paid',
                'payment_reference',
                'payment_notes',
                'payment_date',
                'payment_verified_at',
                'payment_verified_by',
                'receipt_number',
                'payment_proof'
            ]);
        });
    }
};
