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
            'officer_of_the_day' => ['nullable', 'string', 'max:255'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
            'document_content' => ['nullable', 'string'],
        ];
    }

    /**
     * Get validated data including null values
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // ALWAYS include officer_of_the_day if it exists in the request (even if null/empty)
        // Use all() instead of has() to check if the key exists, even with null value
        if (array_key_exists('officer_of_the_day', $this->all())) {
            $validated['officer_of_the_day'] = $this->input('officer_of_the_day');
        }
        
        return $validated;
    }
}
