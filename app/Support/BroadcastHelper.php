<?php

namespace App\Support;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class BroadcastHelper
{
    /**
     * Safely broadcast an event. Catches and logs any broadcast failures
     * so Messaging and Notifications continue to work (e.g. via polling).
     */
    public static function safeBroadcast(ShouldBroadcast $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (app continues without real-time updates)', [
                'event' => get_class($event),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
