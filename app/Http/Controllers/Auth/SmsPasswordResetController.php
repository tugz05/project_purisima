<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsOtpService;
use App\Services\SmsService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class SmsPasswordResetController extends Controller
{
    public function __construct(private SmsOtpService $otpService) {}

    /**
     * Show the current step of the SMS password reset flow.
     * The step is tracked in the session: phone → otp → password.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('auth/ForgotPasswordPhone', [
            'step' => $request->session()->get('sms_reset_step', 'phone'),
        ]);
    }

    /**
     * Send an OTP to the provided phone number.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $normalized = SmsService::normalizePhone($request->phone);

        if (! $normalized) {
            return back()->withErrors(['phone' => 'Invalid phone number. Use format: 09XXXXXXXXX or +63XXXXXXXXX.']);
        }

        // Find the user — search common stored formats
        $user = $this->findUserByPhone($normalized);

        // Send OTP only if the user exists; don't reveal either way
        if ($user) {
            $this->otpService->send($normalized, 'password_reset');
        }

        $request->session()->put('sms_reset_phone', $normalized);
        $request->session()->put('sms_reset_step', 'otp');
        $request->session()->forget('sms_reset_verified');

        return redirect()->route('password.sms.show');
    }

    /**
     * Verify the submitted OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $phone = $request->session()->get('sms_reset_phone');

        if (! $phone) {
            return redirect()->route('password.sms.show')
                ->withErrors(['code' => 'Session expired. Please start again.']);
        }

        if (! $this->otpService->verify($phone, $request->code, 'password_reset')) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please try again.']);
        }

        $request->session()->put('sms_reset_step', 'password');
        $request->session()->put('sms_reset_verified', true);

        return redirect()->route('password.sms.show');
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $phone    = $request->session()->get('sms_reset_phone');
        $verified = $request->session()->get('sms_reset_verified');

        if (! $phone || ! $verified) {
            return redirect()->route('password.sms.show');
        }

        $user = $this->findUserByPhone($phone);

        if (! $user) {
            // Clear session and redirect — no account to reset
            $request->session()->forget(['sms_reset_step', 'sms_reset_phone', 'sms_reset_verified']);

            return redirect()->route('login')
                ->with('status', 'Password has been reset successfully.');
        }

        $user->forceFill([
            'password'       => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget(['sms_reset_step', 'sms_reset_phone', 'sms_reset_verified']);

        return redirect()->route('login')
            ->with('status', 'Password reset successfully. You can now log in.');
    }

    /**
     * Look up a user by phone number, trying E.164 and local variants.
     */
    private function findUserByPhone(string $normalized): ?User
    {
        // Build search variants: +639XXXXXXXXX, 09XXXXXXXXX, 9XXXXXXXXX, 639XXXXXXXXX
        $variants = [$normalized];

        if (str_starts_with($normalized, '+63') && strlen($normalized) === 13) {
            $local = '0'.substr($normalized, 3);   // 09XXXXXXXXX
            $short = substr($normalized, 3);        // 9XXXXXXXXX
            $bare  = substr($normalized, 1);        // 639XXXXXXXXX

            $variants[] = $local;
            $variants[] = $short;
            $variants[] = $bare;
        }

        return User::whereIn('phone', $variants)->first();
    }
}
