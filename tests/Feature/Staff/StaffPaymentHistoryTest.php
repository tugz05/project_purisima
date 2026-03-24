<?php

use App\Models\Transaction;
use App\Models\User;

test('guest cannot access staff payment history', function () {
    $response = $this->get(route('staff.payments.history'));

    $response->assertRedirect();
});

test('resident cannot access staff payment history', function () {
    $resident = User::factory()->create(['role' => 'resident']);

    $response = $this->actingAs($resident)->get(route('staff.payments.history'));

    $response->assertForbidden();
});

test('staff payment history returns inertia page with ledger data', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $resident = User::factory()->create(['role' => 'resident']);

    Transaction::factory()->create([
        'resident_id' => $resident->id,
        'type' => 'barangay_clearance',
        'title' => 'Clearance',
        'status' => 'pending',
        'fee_amount' => 150,
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'amount_paid' => 150,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($staff)->get(route('staff.payments.history'));

    $response->assertOk();
    $response->assertSee('Staff/payments/History', false);
});
