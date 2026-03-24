<?php

use App\Models\Transaction;
use App\Models\User;

test('staff inertia page includes pending transactions count', function () {
    Transaction::factory()->count(2)->create(['status' => 'pending']);
    Transaction::factory()->create(['status' => 'completed']);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get(route('staff.dashboard'));

    $response->assertOk();
    $page = $response->viewData('page');

    expect($page)->toBeArray();
    expect(data_get($page, 'props.staffPendingTransactionsCount'))->toBe(2);
});

test('resident inertia page does not expose staff pending transactions count', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);

    $response = $this->actingAs($resident)->get(route('resident.dashboard'));

    $response->assertOk();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.staffPendingTransactionsCount'))->toBeNull();
});
