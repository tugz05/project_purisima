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

    $response->assertSuccessful()->assertJson(['success' => true]);
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
