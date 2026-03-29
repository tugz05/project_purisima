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
            'purok' => 'Purok Calamansi',
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
            'purok' => 'Purok Calamansi',
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
            'purok' => 'Purok Calamansi',
            'photo' => $file,
        ])
        ->assertRedirect(route('resident.dashboard', absolute: false));

    $user->refresh();
    expect($user->photo_url)->toStartWith('/storage/photos/');
});

it('rejects a profile photo larger than 5 MB', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
        'photo_url' => null,
    ]);

    $file = UploadedFile::fake()->image('large.jpg', 100, 100)->size(5121);

    $this->actingAs($user)
        ->post('/resident/onboarding', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'purok' => 'Purok Calamansi',
            'photo' => $file,
        ])
        ->assertSessionHasErrors('photo');

    expect(session('errors')->get('photo')[0] ?? '')
        ->toContain('5 MB');
});

it('returns a redirect with session errors for inertia onboarding validation failures', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
        'photo_url' => 'https://lh3.googleusercontent.com/a/example',
    ]);

    $response = $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->withHeader('X-Inertia-Version', 'test')
        ->post('/resident/onboarding', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'purok' => '',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('purok');
    expect($response->headers->get('Content-Type'))->not->toStartWith('application/json');
});
