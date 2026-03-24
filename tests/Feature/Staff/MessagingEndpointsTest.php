<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('staff can mark conversation as read', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);

    $conversation = Conversation::create([
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
        'subject' => 'Test',
        'is_active' => true,
        'resident_has_unread' => false,
        'staff_has_unread' => true,
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $resident->id,
        'content' => 'Hello',
        'type' => 'text',
        'is_read' => false,
    ]);

    $this->actingAs($staff);

    $response = $this->postJson("/staff/messaging/conversations/{$conversation->id}/mark-read");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'unread_total' => 0,
        ])
        ->assertJsonPath('conversation.id', $conversation->id)
        ->assertJsonPath('conversation.staff_has_unread', false);
});

test('staff can start and stop typing', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);

    $conversation = Conversation::create([
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
        'subject' => 'Test',
        'is_active' => true,
    ]);

    $this->actingAs($staff);

    $this->postJson("/staff/messaging/conversations/{$conversation->id}/typing/start")
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->postJson("/staff/messaging/conversations/{$conversation->id}/typing/stop")
        ->assertSuccessful()
        ->assertJson(['success' => true]);
});

test('staff can send a message as json', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);

    $conversation = Conversation::create([
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
        'subject' => 'Test',
        'is_active' => true,
    ]);

    $this->actingAs($staff);

    $response = $this->postJson("/staff/messaging/conversations/{$conversation->id}/messages", [
        'content' => 'Reply from staff',
    ]);

    $response->assertSuccessful()->assertJsonPath('success', true);
});

test('staff can search residents for messaging', function () {
    User::factory()->create([
        'role' => 'resident',
        'name' => 'Unique Resident Search',
        'email' => 'unique.resident.messaging@test.com',
    ]);
    User::factory()->create(['role' => 'staff', 'name' => 'Staff Person']);

    $this->actingAs(User::factory()->create(['role' => 'staff']));

    $response = $this->getJson('/staff/messaging/search-users?q=Unique%20Resident');

    $response->assertSuccessful()
        ->assertJsonPath('users.0.email', 'unique.resident.messaging@test.com');
});

test('staff search users requires query of at least two characters', function () {
    $this->actingAs(User::factory()->create(['role' => 'staff']));

    $this->getJson('/staff/messaging/search-users?q=a')
        ->assertUnprocessable();
});

test('staff can start a conversation with a resident', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff);

    $response = $this->postJson('/staff/messaging/conversations/start', [
        'resident_id' => $resident->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('conversation.resident_id', $resident->id)
        ->assertJsonPath('conversation.staff_id', $staff->id);

    $this->assertDatabaseHas('conversations', [
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
    ]);
});

test('staff can start a conversation with an optional first message', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff);

    $response = $this->postJson('/staff/messaging/conversations/start', [
        'resident_id' => $resident->id,
        'content' => 'Hello from staff',
    ]);

    $response->assertSuccessful()->assertJsonPath('success', true);

    $this->assertDatabaseHas('messages', [
        'content' => 'Hello from staff',
        'sender_id' => $staff->id,
    ]);
});

test('staff cannot start a conversation with a non-resident user', function () {
    $otherStaff = User::factory()->create(['role' => 'staff']);

    $this->actingAs(User::factory()->create(['role' => 'staff']));

    $this->postJson('/staff/messaging/conversations/start', [
        'resident_id' => $otherStaff->id,
    ])->assertUnprocessable();
});
