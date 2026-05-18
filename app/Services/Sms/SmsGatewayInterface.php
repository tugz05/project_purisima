<?php

namespace App\Services\Sms;

interface SmsGatewayInterface
{
    /**
     * Send an SMS message.
     * Phone number must be in E.164 format (+63XXXXXXXXXX).
     * Returns the provider message ID / SID on success.
     *
     * @throws \Throwable on delivery failure
     */
    public function send(string $to, string $message): string;
}
