<?php

use App\Models\User;

it('stores registration geo and redirects new residents to onboarding', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'registration_geo_verified_at' => null,
        'profile_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('registration.verify-location.store'), [
            'latitude' => 9.0211,
            'longitude' => 126.2318,
        ])
        ->assertRedirect(route('resident.onboarding.show', absolute: false));

    $user->refresh();
    expect($user->registration_geo_verified_at)->not->toBeNull()
        ->and((float) $user->latitude)->toBeGreaterThan(9.0)
        ->and($user->location_shared)->toBeTrue();
});

it('rejects coordinates outside Tago', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'registration_geo_verified_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('registration.verify-location.store'), [
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ])
        ->assertSessionHasErrors('location');

    $user->refresh();
    expect($user->registration_geo_verified_at)->toBeNull();
});

it('redirects verified residents with completed profile to dashboard', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'registration_geo_verified_at' => null,
        'profile_completed_at' => now(),
        'purok' => 'Purok Calamansi',
    ]);

    $this->actingAs($user)
        ->post(route('registration.verify-location.store'), [
            'latitude' => 9.0211,
            'longitude' => 126.2318,
        ])
        ->assertRedirect(route('resident.dashboard', absolute: false));
});
