<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('resident can start a conversation with text', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($resident);

    $response = $this->post('/resident/messaging', [
        'staff_id' => $staff->id,
        'content' => 'Hello staff',
    ]);

    $response->assertRedirect();

    $conversation = Conversation::query()
        ->where('resident_id', $resident->id)
        ->where('staff_id', $staff->id)
        ->first();

    expect($conversation)->not->toBeNull();

    $message = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $resident->id)
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('Hello staff');
});

test('resident can start a conversation with image only', function () {
    Storage::fake('public');

    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($resident);

    $file = UploadedFile::fake()->image('photo.jpg');

    $response = $this->post('/resident/messaging', [
        'staff_id' => $staff->id,
        'content' => '',
        'attachments' => [$file],
    ]);

    $response->assertRedirect();

    $conversation = Conversation::query()
        ->where('resident_id', $resident->id)
        ->where('staff_id', $staff->id)
        ->first();

    expect($conversation)->not->toBeNull();

    $message = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $resident->id)
        ->latest('id')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->attachments)->toBeArray()->not->toBeEmpty();
});

test('resident messaging conversation json includes message attachments', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);
    $staff = User::factory()->create(['role' => 'staff']);

    $conversation = Conversation::create([
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
        'subject' => 'Test',
        'is_active' => true,
    ]);

    $attachments = [
        [
            'name' => 'pic.jpg',
            'path' => 'message-attachments/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ],
    ];

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $staff->id,
        'content' => 'See image',
        'type' => 'image',
        'attachments' => $attachments,
        'is_read' => true,
    ]);

    $this->actingAs($resident);

    $response = $this->getJson("/resident/messaging/conversations/{$conversation->id}/json");

    $response->assertSuccessful();
    $response->assertJsonPath('conversation.messages.0.attachments.0.name', 'pic.jpg');
});

test('resident cannot start a conversation with empty message and no attachments', function () {
    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($resident);

    $this->post('/resident/messaging', [
        'staff_id' => $staff->id,
        'content' => '',
    ])->assertSessionHasErrors('content');
});
