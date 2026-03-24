<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resident_id' => User::factory()->state(['role' => 'resident']),
            'staff_id' => null,
            'document_type_id' => null,
            'type' => 'barangay_clearance',
            'title' => fake()->words(3, true),
            'status' => 'pending',
            'fee_amount' => fake()->randomFloat(2, 50, 500),
            'fee_paid' => false,
            'payment_status' => 'pending',
            'payment_method' => null,
            'amount_paid' => null,
            'submitted_at' => now(),
        ];
    }
}
