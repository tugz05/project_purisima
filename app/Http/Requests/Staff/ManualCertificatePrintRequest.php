<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ManualCertificatePrintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'staff';
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'content' => ['required', 'string', 'max:500000'],
            'officer_of_the_day' => ['nullable', 'string', 'max:255'],
        ];
    }
}
