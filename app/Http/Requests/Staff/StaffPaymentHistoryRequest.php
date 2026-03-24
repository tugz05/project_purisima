<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffPaymentHistoryRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', 'string', Rule::in(['all', 'pending', 'paid', 'failed', 'refunded'])],
            'payment_method' => ['nullable', 'string', Rule::in(['all', 'cash', 'gcash', 'paymaya', 'bank_transfer', 'check'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', 'string', Rule::in(['recorded_at', 'amount_paid', 'fee_amount', 'receipt_number'])],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    /**
     * @return array{search: string, payment_status: string, payment_method: string, date_from: ?string, date_to: ?string, sort: string, direction: string, per_page: int}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => isset($validated['search']) ? trim((string) $validated['search']) : '',
            'payment_status' => $validated['payment_status'] ?? 'all',
            'payment_method' => $validated['payment_method'] ?? 'all',
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'sort' => $validated['sort'] ?? 'recorded_at',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];
    }
}
