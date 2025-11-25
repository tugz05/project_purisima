<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get valid document type codes from database
        $validTypes = \App\Models\DocumentType::active()->pluck('code')->toArray();

        return [
            'type' => ['required', 'string', 'in:' . implode(',', $validTypes)],
            'title' => ['required', 'string', 'max:255'],
            'required_documents' => ['nullable', 'array'],
            'required_fields' => ['nullable', 'array'],
            'submitted_documents' => ['nullable', 'array'],
            'submitted_documents.*' => ['nullable', 'array'],
            'submitted_documents.*.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'], // 10MB max
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
