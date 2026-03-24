<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('requires a profile photo when the resident has no existing photo_url', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
        'photo_url' => null,
    ]);

    $this->actingAs($user)
        ->post('/resident/onboarding', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'purok' => 'Purok 1',
        ])
        ->assertSessionHasErrors('photo');
});

it('allows onboarding without upload when the resident already has a photo_url', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
        'photo_url' => 'https://lh3.googleusercontent.com/a/example',
    ]);

    $this->actingAs($user)
        ->post('/resident/onboarding', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'purok' => 'Purok 1',
        ])
        ->assertRedirect(route('resident.dashboard', absolute: false));

    $user->refresh();
    expect($user->profile_completed_at)->not->toBeNull();
    expect($user->photo_url)->toBe('https://lh3.googleusercontent.com/a/example');
});

it('allows onboarding with a new photo when none existed', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
        'photo_url' => null,
    ]);

    $file = UploadedFile::fake()->image('profile.jpg', 100, 100);

    $this->actingAs($user)
        ->post('/resident/onboarding', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'purok' => 'Purok 1',
            'photo' => $file,
        ])
        ->assertRedirect(route('resident.dashboard', absolute: false));

    $user->refresh();
    expect($user->photo_url)->toStartWith('/storage/photos/');
});
