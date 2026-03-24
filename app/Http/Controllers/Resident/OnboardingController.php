<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResidentProfileRequest;
use App\Models\User;
use App\Services\ResidentProfileService;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->refresh();

        $existingPhotoUrl = $user !== null && is_string($user->photo_url) && trim($user->photo_url) !== ''
            ? trim($user->photo_url)
            : null;

        return Inertia::render('resident/Onboarding', [
            'existingPhotoUrl' => $existingPhotoUrl,
            'profileDraft' => $user instanceof User ? $this->residentProfileDraft($user) : null,
        ]);
    }

    public function store(ResidentProfileRequest $request, ResidentProfileService $service): RedirectResponse
    {
        $service->complete($request->user(), $request->validated());

        return redirect()->route('resident.dashboard');
    }

    /**
     * @return array{
     *     first_name: string,
     *     middle_name: string,
     *     last_name: string,
     *     suffix: string,
     *     phone: string,
     *     birth_date: string,
     *     sex: string,
     *     civil_status: string,
     *     occupation: string,
     *     purok: string,
     * }
     */
    protected function residentProfileDraft(User $user): array
    {
        $first = (string) ($user->first_name ?? '');
        $middle = (string) ($user->middle_name ?? '');
        $last = (string) ($user->last_name ?? '');

        if ($first === '' && $last === '') {
            $storedName = $user->getRawOriginal('name');
            if (is_string($storedName) && trim($storedName) !== '') {
                [$parsedFirst, $parsedMiddle, $parsedLast] = $this->splitDisplayNameForDraft($storedName);
                $first = $parsedFirst ?? '';
                $middle = $parsedMiddle ?? '';
                $last = $parsedLast ?? '';
            }
        }

        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => (string) ($user->suffix ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'birth_date' => $this->formatDateForHtmlInput($user->birth_date),
            'sex' => (string) ($user->sex ?? ''),
            'civil_status' => (string) ($user->civil_status ?? ''),
            'occupation' => (string) ($user->occupation ?? ''),
            'purok' => (string) ($user->purok ?? ''),
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    protected function splitDisplayNameForDraft(string $fullName): array
    {
        $fullName = trim($fullName);
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

    protected function formatDateForHtmlInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
}
