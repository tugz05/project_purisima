<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGatewayInterface
{
    public function send(string $to, string $message): string
    {
        $fakeId = 'LOG-'.strtoupper(substr(md5($to.$message.now()), 0, 16));

        Log::channel('stack')->info('[SMS] Would send via Twilio', [
            'to' => $to,
            'message' => $message,
            'fake_sid' => $fakeId,
        ]);

        return $fakeId;
    }
}
