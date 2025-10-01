<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffTransactionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,in_progress,approved,rejected,completed'],
            'staff_notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ];
    }
}
