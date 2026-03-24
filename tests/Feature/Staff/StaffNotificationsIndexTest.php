<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff notifications index returns inertia page with correct vue component name', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get('/staff/notifications');

    $response->assertOk();
    $response->assertSee('Staff/Notifications/Index', false);
});
