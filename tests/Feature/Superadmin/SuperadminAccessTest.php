<?php

use App\Models\User;

test('guest cannot access superadmin dashboard', function () {
    $this->get(route('superadmin.dashboard'))->assertRedirect(route('login'));
});

test('resident cannot access superadmin dashboard', function () {
    $resident = User::factory()->create(['role' => 'resident']);

    $this->actingAs($resident)
        ->get(route('superadmin.dashboard'))
        ->assertForbidden();
});

test('staff cannot access superadmin dashboard', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->get(route('superadmin.dashboard'))
        ->assertForbidden();
});

test('superadmin can access dashboard and users index', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superadmin)
        ->get(route('superadmin.dashboard'))
        ->assertOk();

    $this->actingAs($superadmin)
        ->get(route('superadmin.users.index'))
        ->assertOk();
});

test('superadmin can update another users role', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $target = User::factory()->create([
        'role' => 'resident',
        'name' => 'Target User',
        'email' => 'target-role@example.com',
    ]);

    $this->actingAs($superadmin)
        ->put(route('superadmin.users.update', $target), [
            'name' => 'Target User',
            'email' => 'target-role@example.com',
            'role' => 'staff',
        ])
        ->assertRedirect(route('superadmin.users.index'));

    expect($target->fresh()->role)->toBe('staff');
});

test('cannot demote the only superadmin', function () {
    $only = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($only)
        ->put(route('superadmin.users.update', $only), [
            'name' => $only->name,
            'email' => $only->email,
            'role' => 'staff',
        ])
        ->assertSessionHasErrors('role');
});
