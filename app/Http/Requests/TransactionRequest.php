<?php

namespace App\Http\Requests;

use App\Services\PendingFileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        // If no valid types found, provide a fallback to prevent validation errors
        if (empty($validTypes)) {
            $validTypes = ['barangay_clearance', 'residency_certificate', 'business_permit', 'indigency_certificate', 'cedula', 'other'];
        }

        return [
            'type' => ['required', 'string', 'in:'.implode(',', $validTypes)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'required_documents' => ['nullable', 'array'],
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => ['nullable', 'string', 'max:5000'],
            'submitted_documents' => ['nullable', 'array'],
            'submitted_documents.*' => ['nullable', 'array'],
            'submitted_documents.*.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'], // 10MB max
            'submitted_document_upload_ids' => ['nullable', 'array'],
            'submitted_document_upload_ids.*' => ['nullable', 'array'],
            'submitted_document_upload_ids.*.*' => [
                'required',
                'string',
                'ulid',
                Rule::exists('pending_file_uploads', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())
                        ->where('purpose', PendingFileUploadService::PURPOSE_TRANSACTION_SUBMISSION)
                        ->where('expires_at', '>', now());
                }),
            ],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select a document type.',
            'type.in' => 'The selected document type is invalid. Please select a valid document type.',
            'title.required' => 'Please provide a title for your request.',
        ];
    }
}
