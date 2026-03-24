<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
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
        $avatarUrl = $this->normalizeProviderAvatarUrl($socialUser);

        $user = User::where('email', $socialUser->getEmail())->first();
        $profileHints = $this->oauthProfileHintsFromSocialUser($socialUser);

        if (! $user) {
            $attributes = [
                'email' => $socialUser->getEmail(),
                'password' => Str::random(40),
                'role' => 'resident',
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'photo_url' => $avatarUrl,
                'email_verified_at' => now(),
            ];

            if (filled($profileHints['first_name'])) {
                $attributes['first_name'] = $profileHints['first_name'];
            }
            if (filled($profileHints['middle_name'])) {
                $attributes['middle_name'] = $profileHints['middle_name'];
            }
            if (filled($profileHints['last_name'])) {
                $attributes['last_name'] = $profileHints['last_name'];
            }
            if (filled($profileHints['suffix'])) {
                $attributes['suffix'] = $profileHints['suffix'];
            }
            if (filled($profileHints['sex'])) {
                $attributes['sex'] = $profileHints['sex'];
            }

            if (! filled($profileHints['first_name']) && ! filled($profileHints['last_name'])) {
                $attributes['name'] = $socialUser->getName() ?: $socialUser->getNickname() ?: 'User';
            }

            $user = User::create($attributes);
        } else {
            $this->syncOAuthProfile($user, $provider, $socialUser, $avatarUrl);
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

    protected function normalizeProviderAvatarUrl(SocialiteUserContract $socialUser): ?string
    {
        $url = $socialUser->getAvatar();
        if (! is_string($url)) {
            return null;
        }
        $url = trim($url);

        return $url === '' ? null : $url;
    }

    /**
     * Link OAuth metadata and refresh remote avatars without replacing uploaded (/storage) photos.
     */
    protected function syncOAuthProfile(User $user, string $provider, SocialiteUserContract $socialUser, ?string $avatarUrl): void
    {
        $updates = [];
        $profileHints = $this->oauthProfileHintsFromSocialUser($socialUser);

        if ($user->provider === null && $user->provider_id === null) {
            $updates['provider'] = $provider;
            $updates['provider_id'] = $socialUser->getId();
        }

        if ($avatarUrl !== null && $this->shouldApplyProviderAvatar($user->photo_url)) {
            $updates['photo_url'] = $avatarUrl;
        }

        if ($this->shouldBackfillResidentNameFromOAuth($user, $profileHints)) {
            if (filled($profileHints['first_name'])) {
                $updates['first_name'] = $profileHints['first_name'];
            }
            if (filled($profileHints['middle_name'])) {
                $updates['middle_name'] = $profileHints['middle_name'];
            }
            if (filled($profileHints['last_name'])) {
                $updates['last_name'] = $profileHints['last_name'];
            }
            if (filled($profileHints['suffix'])) {
                $updates['suffix'] = $profileHints['suffix'];
            }
        }

        if ($this->shouldBackfillSexFromOAuth($user, $profileHints) && filled($profileHints['sex'])) {
            $updates['sex'] = $profileHints['sex'];
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function oauthRawPayload(SocialiteUserContract $socialUser): array
    {
        if (! method_exists($socialUser, 'getRaw')) {
            return [];
        }

        $raw = $socialUser->getRaw();

        return is_array($raw) ? $raw : [];
    }

    /**
     * Basic profile hints from the provider payload (Google given_name/family_name, Facebook gender, etc.).
     *
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string, suffix: ?string, sex: ?string}
     */
    protected function oauthProfileHintsFromSocialUser(SocialiteUserContract $socialUser): array
    {
        $raw = $this->oauthRawPayload($socialUser);
        $first = null;
        $middle = null;
        $last = null;
        $suffix = null;
        $sex = null;

        $given = isset($raw['given_name']) ? trim((string) $raw['given_name']) : '';
        $family = isset($raw['family_name']) ? trim((string) $raw['family_name']) : '';

        if ($given !== '') {
            $first = $given;
        }
        if ($family !== '') {
            $last = $family;
        }

        if ($first === null && isset($raw['first_name'])) {
            $t = trim((string) $raw['first_name']);
            $first = $t !== '' ? $t : null;
        }
        if ($last === null && isset($raw['last_name'])) {
            $t = trim((string) $raw['last_name']);
            $last = $t !== '' ? $t : null;
        }

        if ($first === null && $last === null) {
            [$first, $middle, $last] = $this->splitDisplayName($socialUser->getName() ?: $socialUser->getNickname());
        }

        if (isset($raw['gender'])) {
            $mappedSex = $this->mapProviderGenderToSex((string) $raw['gender']);
            if ($mappedSex !== null) {
                $sex = $mappedSex;
            }
        }

        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,
            'sex' => $sex,
        ];
    }

    /**
     * @param  array{first_name: ?string, middle_name: ?string, last_name: ?string, suffix: ?string, sex: ?string}  $hints
     */
    protected function shouldBackfillResidentNameFromOAuth(User $user, array $hints): bool
    {
        if ($user->role !== 'resident') {
            return false;
        }

        if (filled($user->first_name) || filled($user->last_name)) {
            return false;
        }

        return filled($hints['first_name']) || filled($hints['last_name']);
    }

    /**
     * @param  array{first_name: ?string, middle_name: ?string, last_name: ?string, suffix: ?string, sex: ?string}  $hints
     */
    protected function shouldBackfillSexFromOAuth(User $user, array $hints): bool
    {
        if ($user->role !== 'resident') {
            return false;
        }

        if (filled($user->sex)) {
            return false;
        }

        return filled($hints['sex']);
    }

    protected function mapProviderGenderToSex(string $gender): ?string
    {
        return match (strtolower(trim($gender))) {
            'male' => 'male',
            'female' => 'female',
            default => null,
        };
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    protected function splitDisplayName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return [null, null, null];
        }

        $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return [null, null, null];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, null];
        }

        if (count($parts) === 2) {
            return [$parts[0], null, $parts[1]];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = implode(' ', $parts);

        return [$first, $middle !== '' ? $middle : null, $last];
    }

    protected function shouldApplyProviderAvatar(?string $current): bool
    {
        if ($current === null || $current === '') {
            return true;
        }

        $current = trim($current);

        return str_starts_with($current, 'http://') || str_starts_with($current, 'https://');
    }
}
