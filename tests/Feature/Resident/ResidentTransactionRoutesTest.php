<?php

use App\Models\User;

test('resident with completed profile can open the transactions index', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);

    $this->actingAs($resident)
        ->get(route('resident.transactions.index'))
        ->assertSuccessful();
});

test('resident with completed profile can open the new transaction page', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);

    $this->actingAs($resident)
        ->get(route('resident.transactions.create'))
        ->assertSuccessful();
});

test('resident with completed profile can load the dashboard', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);

    $this->actingAs($resident)
        ->get(route('resident.dashboard'))
        ->assertSuccessful();
});
