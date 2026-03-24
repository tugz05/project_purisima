<?php

namespace App\Http\Requests\Messaging;

use App\Http\Requests\Concerns\ValidatesMessagingMessageBody;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'content' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:text,image,file'],
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
