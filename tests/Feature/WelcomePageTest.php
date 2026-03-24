<?php

use App\Models\User;

test('authenticated staff receives a successful response from the home page', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get(route('home'));

    $response->assertSuccessful();
});

test('guests receive a successful response from the home page', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
});
