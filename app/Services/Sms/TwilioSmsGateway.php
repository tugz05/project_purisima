<?php

namespace App\Services\Sms;

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
        try {
            $msg = $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return $msg->sid;
        } catch (RestException $e) {
            throw $e;
        }
    }
}
