<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $documentTypeId = $this->route('document_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string', 'max:255'],
            'document_templates' => ['nullable', 'array'],
            'document_templates.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'], // 10MB max
            'processing_steps' => ['nullable', 'array'],
            'processing_steps.*' => ['string', 'max:500'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
            'requires_payment' => ['boolean'],
            'requires_approval' => ['boolean'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fee_amount.min' => 'The fee amount must be at least 0.',
            'processing_days.min' => 'Processing days must be at least 1.',
            'processing_days.max' => 'Processing days cannot exceed 365.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'requires_payment' => $this->boolean('requires_payment', true),
            'requires_approval' => $this->boolean('requires_approval', false),
            'sort_order' => $this->integer('sort_order', 0),
        ]);
    }
}
