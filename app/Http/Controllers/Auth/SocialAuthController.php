<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the OAuth Provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);
        $driver = Socialite::driver($provider);
        /** @var AbstractProvider $driver */
        $driver = $driver->stateless();
        return $driver->redirect();
    }

    /**
     * Obtain the user information from provider and log them in / register.
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        $driver = Socialite::driver($provider);
        /** @var AbstractProvider $driver */
        $driver = $driver->stateless();
        $socialUser = $driver->user();

        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
                'email' => $socialUser->getEmail(),
                // Store a random password; the user will use the provider to sign in
                'password' => Str::random(40),
                'role' => 'resident',
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);

            // Consider email verified if provided by provider
            if (method_exists($user, 'forceFill')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            } else {
                $user->email_verified_at = now();
                $user->save();
            }
        }

        Auth::login($user, remember: true);

        // If resident profile not completed, send to onboarding
        if ($user->role === 'resident' && empty($user->profile_completed_at)) {
            return redirect()->route('resident.onboarding.show');
        }

        $intended = match ($user->role) {
            'admin' => route('admin.dashboard', absolute: false),
            'staff' => route('staff.dashboard', absolute: false),
            'enforcer' => route('enforcer.dashboard', absolute: false),
            'resident' => route('resident.dashboard', absolute: false),
            default => route('dashboard', absolute: false),
        };

        return redirect()->intended($intended);
    }

    protected function ensureSupportedProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }
    }
}


