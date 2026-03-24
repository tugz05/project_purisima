<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class SearchStaffMessagingUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array{q: string}
     */
    public function searchQuery(): array
    {
        $validated = $this->validated();

        return [
            'q' => trim($validated['q']),
        ];
    }
}
