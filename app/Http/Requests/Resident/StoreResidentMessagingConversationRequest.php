<?php

namespace App\Http\Requests\Resident;

use App\Http\Requests\Concerns\ValidatesMessagingMessageBody;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreResidentMessagingConversationRequest extends FormRequest
{
    use ValidatesMessagingMessageBody;

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
            'staff_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasMessagingMessageBody()) {
                return;
            }

            $validator->errors()->add('content', 'Please enter a message or attach a file.');
        });
    }
}
