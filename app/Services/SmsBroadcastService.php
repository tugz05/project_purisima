<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsBroadcast;
use App\Models\User;

class SmsBroadcastService
{
    public function __construct(private SmsService $smsService) {}

    /**
     * Send an SMS to all residents who have a phone number on file.
     * Each message is dispatched as a separate queued job.
     */
    public function broadcastToResidents(string $title, string $message, User $sender): SmsBroadcast
    {
        $phones = User::where('role', 'resident')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('phone', 'id');

        $normalized = $phones
            ->map(fn ($p) => SmsService::normalizePhone($p))
            ->filter()
            ->unique()
            ->values();

        $broadcast = SmsBroadcast::create([
            'title' => $title,
            'message' => $message,
            'recipients_count' => $normalized->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => 'processing',
            'started_at' => now(),
            'created_by' => $sender->id,
        ]);

        foreach ($normalized as $phone) {
            SendSmsJob::dispatch($phone, $message, 'broadcast', $broadcast->id);
        }

        if ($normalized->isEmpty()) {
            $broadcast->update(['status' => 'completed', 'completed_at' => now()]);
        }

        return $broadcast;
    }
}
