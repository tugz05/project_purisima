<?php

namespace App\Services\Sms;

use App\Services\SmsService;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioSmsGateway implements SmsGatewayInterface
{
    private Client $client;

    private string $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.from');
    }

    public function send(string $to, string $message): string
    {
        // Always normalize to E.164 (+639XXXXXXXXX) before hitting Twilio.
        // This is a last-line-of-defence so "09" numbers never reach the API.
        $normalized = SmsService::normalizePhone($to);

        if ($normalized === null) {
            throw new \InvalidArgumentException("Cannot send SMS: unrecognized phone number format '{$to}'.");
        }

        try {
            $msg = $this->client->messages->create($normalized, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return $msg->sid;
        } catch (RestException $e) {
            throw $e;
        }
    }
}
