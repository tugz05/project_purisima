<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Services\Sms\SmsGatewayInterface;

class SmsService
{
    public function __construct(private SmsGatewayInterface $gateway) {}

    /**
     * Dispatch a queued SMS (fire-and-forget, non-blocking).
     */
    public function send(string $to, string $message, ?string $contextType = null, ?int $contextId = null): void
    {
        $normalized = self::normalizePhone($to);
        if ($normalized === null) {
            return;
        }

        SendSmsJob::dispatch($normalized, $message, $contextType, $contextId);
    }

    /**
     * Send immediately (synchronous — use only for OTPs where instant delivery matters).
     */
    public function sendNow(string $to, string $message): ?string
    {
        $normalized = self::normalizePhone($to);
        if ($normalized === null) {
            return null;
        }

        return $this->gateway->send($normalized, $message);
    }

    /**
     * Normalize a Philippine phone number to E.164 (+63XXXXXXXXXX).
     * Returns null if the number cannot be recognized.
     */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        // Already in full international format: 639XXXXXXXXX
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        // Local mobile: 09XXXXXXXXX (11 digits)
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        // Local without leading 0: 9XXXXXXXXX (10 digits)
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        // Already prefixed with +
        if (str_starts_with($phone, '+')) {
            return '+'.$digits;
        }

        return null;
    }
}
