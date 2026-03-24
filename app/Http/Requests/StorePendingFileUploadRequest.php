<?php

namespace App\Http\Requests;

use App\Services\PendingFileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePendingFileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $purpose = $this->input('purpose');

        $fileRules = match ($purpose) {
            PendingFileUploadService::PURPOSE_PAYMENT_PROOF => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            default => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240',
            ],
        };

        return [
            'purpose' => [
                'required',
                'string',
                Rule::in([
                    PendingFileUploadService::PURPOSE_TRANSACTION_SUBMISSION,
                    PendingFileUploadService::PURPOSE_PAYMENT_PROOF,
                ]),
            ],
            'file' => $fileRules,
        ];
    }
}
