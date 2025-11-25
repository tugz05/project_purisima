<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResidentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable','string','max:100'],
            'middle_name' => ['nullable','string','max:100'],
            'last_name' => ['nullable','string','max:100'],
            'suffix' => ['nullable','string','max:50'],
            'phone' => ['nullable','string','max:50'],
            'birth_date' => ['nullable','date'],
            'sex' => ['nullable','in:male,female,other'],
            'civil_status' => ['nullable','in:single,married,widowed,separated,other'],
            'occupation' => ['nullable','string','max:150'],
            'purok' => ['required','string','max:100'],
            'photo' => ['required','image','mimes:jpg,jpeg,png','max:2048'],
        ];
    }
}


