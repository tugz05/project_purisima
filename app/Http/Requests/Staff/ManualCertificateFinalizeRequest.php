<?php

namespace App\Http\Requests\Staff;

use App\Models\DocumentType;
use App\Services\ManualCertificateWizardService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ManualCertificateFinalizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'staff';
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['manual_email', 'manual_phone', 'manual_purok', 'manual_address', 'staff_notes', 'title', 'officer_of_the_day'] as $key) {
            if ($this->input($key) === '') {
                $merge[$key] = null;
            }
        }

        if ($this->input('fee_amount') === '' || $this->input('fee_amount') === null) {
            $merge['fee_amount'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $documentTypeId = $this->input('document_type_id');
            if (! is_numeric($documentTypeId)) {
                return;
            }

            $documentType = DocumentType::query()->find((int) $documentTypeId);
            if (! $documentType) {
                return;
            }

            $wizard = app(ManualCertificateWizardService::class);
            $sanitized = $wizard->filterFieldValuesForDocumentType($documentType, $this->input('field_values', []));
            if ($wizard->resolveRequestorDisplayName($sanitized) === '') {
                $validator->errors()->add(
                    'field_values.name',
                    'Enter the requestor name (e.g. Name or Full name field from your document type) before saving.'
                );
            }
        });
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
            'document_content' => ['required', 'string', 'min:1', 'max:500000'],
            'officer_of_the_day' => ['nullable', 'string', 'max:255'],
            'manual_full_name' => ['nullable', 'string', 'max:255'],
            'manual_email' => ['nullable', 'string', 'email', 'max:255'],
            'manual_phone' => ['nullable', 'string', 'max:50'],
            'manual_purok' => ['nullable', 'string', 'max:255'],
            'manual_address' => ['nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'staff_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
