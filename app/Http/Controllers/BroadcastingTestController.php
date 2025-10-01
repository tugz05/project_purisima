<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BroadcastingTestController extends Controller
{
    /**
     * Test broadcasting setup
     */
    public function testBroadcasting()
    {
        // Get a test conversation
        $conversation = Conversation::first();

        if (!$conversation) {
            return response()->json([
                'error' => 'No conversations found. Create a conversation first.'
            ]);
        }

        // Get current user
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'User not authenticated'
            ]);
        }

        // Test message broadcasting
        $testMessage = new \App\Models\Message([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message for broadcasting',
            'type' => 'text',
            'is_read' => false,
            'is_edited' => false,
        ]);

        // Load sender relationship
        $testMessage->sender = $user;

        // Broadcast the test message
        broadcast(new MessageSent($testMessage, $conversation));

        return response()->json([
            'success' => true,
            'message' => 'Test message broadcasted successfully',
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'channel' => 'conversation.' . $conversation->id
        ]);
    }

    /**
     * Test typing indicator broadcasting
     */
    public function testTyping()
    {
        $conversation = Conversation::first();
        $user = Auth::user();

        if (!$conversation || !$user) {
            return response()->json(['error' => 'Conversation or user not found']);
        }

        // Broadcast typing event
        broadcast(new UserTyping($user, $conversation, true));

        // Stop typing after 2 seconds
        sleep(2);
        broadcast(new UserTyping($user, $conversation, false));

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator test completed',
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
    }
}

