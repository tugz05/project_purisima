<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastingController extends Controller
{
    /**
     * Authenticate the request for channel access.
     */
    public function authenticate(Request $request)
    {
        // Ensure user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // For private channels, we need to authenticate the user
        if ($request->has('channel_name')) {
            $channelName = $request->input('channel_name');

            // Log the authentication attempt
            \Log::info('Broadcasting auth attempt', [
                'user_id' => auth()->id(),
                'channel_name' => $channelName,
                'socket_id' => $request->input('socket_id')
            ]);

            // Handle conversation channels
            if (str_starts_with($channelName, 'private-conversation.')) {
                $conversationId = str_replace('private-conversation.', '', $channelName);
                $conversation = \App\Models\Conversation::find($conversationId);

                if ($conversation && $conversation->isParticipant(auth()->user())) {
                    \Log::info('Conversation channel authorized', [
                        'conversation_id' => $conversationId,
                        'user_id' => auth()->id()
                    ]);
                    return Broadcast::auth($request);
                } else {
                    \Log::warning('Conversation channel denied', [
                        'conversation_id' => $conversationId,
                        'user_id' => auth()->id(),
                        'conversation_exists' => $conversation ? 'yes' : 'no',
                        'is_participant' => $conversation ? $conversation->isParticipant(auth()->user()) : 'no'
                    ]);
                }
            }

            // Handle other private channels
            if (str_starts_with($channelName, 'private-App.Models.User.')) {
                $userId = str_replace('private-App.Models.User.', '', $channelName);
                if (auth()->id() == $userId) {
                    \Log::info('User channel authorized', [
                        'user_id' => auth()->id()
                    ]);
                    return Broadcast::auth($request);
                }
            }
        }

        \Log::warning('Broadcasting auth denied', [
            'user_id' => auth()->id(),
            'channel_name' => $request->input('channel_name', 'none')
        ]);

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}


