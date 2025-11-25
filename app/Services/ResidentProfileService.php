<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class ResidentProfileService
{
    /**
     * Ensure a resident's profile contains required fields.
     * Returns the updated model.
     */
    public function complete(User $user, array $data): User
    {
        // Only allow known fields
        $allowed = [
            'first_name','middle_name','last_name','suffix','phone','birth_date','sex','civil_status','occupation',
            'purok','barangay','municipality','province','country',
        ];

        $payload = Arr::only($data, $allowed);

        // Handle photo upload if present
        if (isset($data['photo']) && $data['photo']) {
            $path = $data['photo']->store('photos', 'public');
            $payload['photo_url'] = '/storage/'.$path;
        }

        // Force address scope
        $payload['barangay'] = 'Purisima';
        $payload['municipality'] = 'Tago';
        $payload['province'] = 'Surigao del Sur';
        $payload['country'] = 'Philippines';

        $user->fill($payload);

        // Mark completion if core fields exist
        if (
            ($user->first_name || $user->last_name) &&
            $user->purok
        ) {
            $user->profile_completed_at = now();
        }

        $user->save();

        return $user;
    }

    /**
     * Update resident account details. Photo optional.
     */
    public function update(User $user, array $data): User
    {
        $allowed = [
            'first_name','middle_name','last_name','suffix','phone','birth_date','sex','civil_status','occupation',
            'purok','barangay','municipality','province','country',
        ];

        $payload = Arr::only($data, $allowed);

        // Force address scope regardless
        $payload['barangay'] = 'Purisima';
        $payload['municipality'] = 'Tago';
        $payload['province'] = 'Surigao del Sur';
        $payload['country'] = 'Philippines';

        if (isset($data['photo']) && $data['photo']) {
            $path = $data['photo']->store('photos', 'public');
            $payload['photo_url'] = '/storage/'.$path;
        }

        $user->fill($payload)->save();

        return $user;
    }
}


