<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];

        // Add resident-specific rules if user is a resident
        if ($this->user()->role === 'resident') {
            $rules = array_merge($rules, [
                'first_name' => ['nullable','string','max:100'],
                'middle_name' => ['nullable','string','max:100'],
                'last_name' => ['nullable','string','max:100'],
                'phone' => ['nullable','string','max:50'],
                'birth_date' => ['nullable','date'],
                'sex' => ['nullable','in:male,female,other'],
                'civil_status' => ['nullable','in:single,married,widowed,separated,other'],
                'occupation' => ['nullable','string','max:150'],
                'purok' => ['required','string','max:100'],
                'photo' => ['sometimes','nullable','image','mimes:jpg,jpeg,png','max:2048'],
            ]);
        }

        return $rules;
    }
}
