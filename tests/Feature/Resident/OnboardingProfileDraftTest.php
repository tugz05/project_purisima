<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

it('passes profile draft from stored resident fields', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
        'first_name' => 'Ana',
        'middle_name' => 'L.',
        'last_name' => 'Reyes',
        'suffix' => '',
        'phone' => '09171234567',
        'birth_date' => '1990-05-15',
        'sex' => 'female',
        'civil_status' => 'single',
        'occupation' => 'Teacher',
        'purok' => 'Purok 2',
    ]);

    $this->actingAs($user)
        ->get(route('resident.onboarding.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('resident/Onboarding')
            ->where('profileDraft.first_name', 'Ana')
            ->where('profileDraft.middle_name', 'L.')
            ->where('profileDraft.last_name', 'Reyes')
            ->where('profileDraft.phone', '09171234567')
            ->where('profileDraft.birth_date', '1990-05-15')
            ->where('profileDraft.sex', 'female')
            ->where('profileDraft.civil_status', 'single')
            ->where('profileDraft.occupation', 'Teacher')
            ->where('profileDraft.purok', 'Purok 2'));
});

it('derives profile draft name parts from stored name when split fields are empty', function () {
    $user = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => null,
    ]);

    DB::table('users')->where('id', $user->id)->update([
        'first_name' => null,
        'middle_name' => null,
        'last_name' => null,
        'name' => 'Maria Clara Reyes',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('resident.onboarding.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('resident/Onboarding')
            ->where('profileDraft.first_name', 'Maria')
            ->where('profileDraft.middle_name', 'Clara')
            ->where('profileDraft.last_name', 'Reyes'));
});
