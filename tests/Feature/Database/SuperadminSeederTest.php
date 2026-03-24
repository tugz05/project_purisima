<?php

use App\Models\User;
use Database\Seeders\SuperadminSeeder;

test('SuperadminSeeder creates a verified superadmin user', function () {
    $this->seed(SuperadminSeeder::class);

    $user = User::query()->where('email', 'superadmin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('superadmin')
        ->and($user->name)->toBe('Superadmin User')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('SuperadminSeeder is idempotent', function () {
    $this->seed(SuperadminSeeder::class);
    $this->seed(SuperadminSeeder::class);

    expect(User::query()->where('email', 'superadmin@example.com')->count())->toBe(1);
});
