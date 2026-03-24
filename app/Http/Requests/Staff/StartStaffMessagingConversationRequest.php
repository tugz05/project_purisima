<?php

namespace App\Http\Requests\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StartStaffMessagingConversationRequest extends FormRequest
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
            'resident_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $residentId = $this->integer('resident_id');
            if ($residentId === 0) {
                return;
            }

            $user = User::query()->find($residentId);
            if ($user && $user->role !== 'resident') {
                $validator->errors()->add(
                    'resident_id',
                    'You can only start a conversation with a resident account.'
                );
            }
        });
    }
}
