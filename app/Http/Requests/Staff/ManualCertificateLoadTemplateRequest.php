<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ManualCertificateLoadTemplateRequest extends FormRequest
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
            'template_id' => ['nullable', 'integer', 'exists:certificate_templates,id'],
            'field_values' => ['required', 'array'],
            'field_values.*' => ['nullable', 'string', 'max:50000'],
        ];
    }
}
