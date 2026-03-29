<?php

namespace App\Http\Requests;

use App\Services\RegistrationGeoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VerifyRegistrationLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->role === 'resident';
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $latitude = (float) $this->input('latitude');
            $longitude = (float) $this->input('longitude');

            $service = app(RegistrationGeoService::class);

            if (! $service->isWithinTagoSurigaoDelSurBounds($latitude, $longitude)) {
                $validator->errors()->add(
                    'location',
                    'Your location is outside Tago, Surigao del Sur. Registration is only available while you are within the municipality.',
                );
            }
        });
    }
}
