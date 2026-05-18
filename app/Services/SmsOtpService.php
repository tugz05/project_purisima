<?php

namespace App\Services;

use App\Models\SmsOtp;
use Illuminate\Support\Facades\Hash;

class SmsOtpService
{
    public function __construct(private SmsService $smsService) {}

    /**
     * Generate and send an OTP to the given phone number.
     * Returns the plain-text code (store nowhere; only the hash is persisted).
     */
    public function send(string $phone, string $purpose = 'verification'): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidate any existing OTPs for this phone/purpose
        SmsOtp::where('phone', $phone)->where('purpose', $purpose)->delete();

        SmsOtp::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
        ]);

        $appName = config('app.name', 'Barangay Purisima');

        $this->smsService->sendNow(
            $phone,
            "[{$appName}] Your verification code is {$code}. Valid for 10 minutes. Do not share this code."
        );

        return $code;
    }

    /**
     * Verify an OTP. Returns true and marks the OTP as used on success.
     */
    public function verify(string $phone, string $code, string $purpose = 'verification'): bool
    {
        $otp = SmsOtp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp || ! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }

    /**
     * Invalidate all unused OTPs for a phone/purpose (e.g. after successful login).
     */
    public function invalidate(string $phone, string $purpose = 'verification'): void
    {
        SmsOtp::where('phone', $phone)->where('purpose', $purpose)->delete();
    }
}
