<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResidentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'sex' => ['nullable', 'in:male,female,other'],
            'civil_status' => ['nullable', 'in:single,married,widowed,separated,other'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'purok' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('photo')) {
                return;
            }

            $user = $this->user();
            $hasExistingPhoto = $user !== null
                && is_string($user->photo_url)
                && trim($user->photo_url) !== '';

            $hasUpload = $this->hasFile('photo');

            if (! $hasExistingPhoto && ! $hasUpload) {
                $validator->errors()->add(
                    'photo',
                    'A profile photo is required unless you already have one (for example from Google sign-in).',
                );
            }
        });
    }
}
