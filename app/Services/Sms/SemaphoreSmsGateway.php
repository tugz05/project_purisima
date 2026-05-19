<?php

namespace App\Services\Sms;

use App\Services\SmsService;
use Illuminate\Support\Facades\Http;

class SemaphoreSmsGateway implements SmsGatewayInterface
{
    private string $apiKey;

    private string $senderName;

    public function __construct()
    {
        $this->apiKey = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name', 'SEMAPHORE');
    }

    public function send(string $to, string $message): string
    {
        $normalized = SmsService::normalizePhone($to);

        if ($normalized === null) {
            throw new \InvalidArgumentException("Cannot send SMS: unrecognized phone number format '{$to}'.");
        }

        // Semaphore accepts 09XXXXXXXXX (local PH format)
        // normalizePhone returns +639XXXXXXXXX → strip +63, prepend 0
        $local = '0'.substr($normalized, 3);

        $response = Http::asForm()->timeout(30)->post('https://api.semaphore.co/api/v4/messages', [
            'apikey'     => $this->apiKey,
            'number'     => $local,
            'message'    => $message,
            'sendername' => $this->senderName,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Semaphore API error ('.$response->status().'): '.$response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data) || empty($data)) {
            throw new \RuntimeException(
                'Semaphore API returned unexpected response: '.$response->body()
            );
        }

        $messageId = $data[0]['message_id'] ?? null;

        if ($messageId === null) {
            throw new \RuntimeException(
                'Semaphore API response missing message_id: '.$response->body()
            );
        }

        return (string) $messageId;
    }
}
