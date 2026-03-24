<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('message sent from resident broadcasts to shared staff inbox and conversation channels', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);
    $conversation = Conversation::create([
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
        'subject' => 'Test',
        'is_active' => true,
        'resident_has_unread' => false,
        'staff_has_unread' => false,
    ]);
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $resident->id,
        'content' => 'Hello',
        'type' => 'text',
        'is_read' => false,
    ]);
    $message->load('sender');
    $event = new MessageSent($message, $conversation->fresh());
    $names = array_map(static fn ($ch) => $ch->name, $event->broadcastOn());

    expect($names)->toContain('private-messaging.staff')
        ->and($names)->toContain('private-conversation.'.$conversation->id);
});

test('message sent from staff uses resident user channel not staff inbox', function () {
    $resident = User::factory()->create(['role' => 'resident']);
    $staff = User::factory()->create(['role' => 'staff']);
    $conversation = Conversation::create([
        'resident_id' => $resident->id,
        'staff_id' => $staff->id,
        'subject' => 'Test',
        'is_active' => true,
        'resident_has_unread' => false,
        'staff_has_unread' => false,
    ]);
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $staff->id,
        'content' => 'Reply',
        'type' => 'text',
        'is_read' => false,
    ]);
    $message->load('sender');
    $event = new MessageSent($message, $conversation->fresh());
    $names = array_map(static fn ($ch) => $ch->name, $event->broadcastOn());

    expect($names)->not->toContain('private-messaging.staff')
        ->and($names)->toContain('private-App.Models.User.'.$resident->id)
        ->and($names)->toContain('private-conversation.'.$conversation->id);
});

test('message sent broadcast payload includes staff unread total when resident sends', function () {
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
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $resident->id,
        'content' => 'Hello',
        'type' => 'text',
        'is_read' => false,
    ]);
    $message->load('sender');
    $payload = (new MessageSent($message, $conversation->fresh()))->broadcastWith();

    expect($payload)->toHaveKey('staff_messaging_unread_total')
        ->and($payload['staff_messaging_unread_total'])->toBeInt();
});
