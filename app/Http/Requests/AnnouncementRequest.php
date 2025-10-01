<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:general,urgent,event,notice',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date|after_or_equal:today',
            'expires_at' => 'nullable|date|after:published_at',
            'author_name' => 'nullable|string|max:255',
            'author_position' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:10240', // 10MB max
        ];

        // For update requests, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['title'] = 'sometimes|required|string|max:255';
            $rules['content'] = 'sometimes|required|string';
            $rules['type'] = 'sometimes|required|in:general,urgent,event,notice';
            $rules['priority'] = 'sometimes|required|in:low,normal,high,urgent';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The announcement title is required.',
            'title.max' => 'The announcement title may not be greater than 255 characters.',
            'content.required' => 'The announcement content is required.',
            'type.required' => 'Please select an announcement type.',
            'type.in' => 'The selected announcement type is invalid.',
            'priority.required' => 'Please select a priority level.',
            'priority.in' => 'The selected priority level is invalid.',
            'published_at.date' => 'The published date must be a valid date.',
            'published_at.after_or_equal' => 'The published date must be today or in the future.',
            'expires_at.date' => 'The expiration date must be a valid date.',
            'expires_at.after' => 'The expiration date must be after the published date.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, webp.',
            'image.max' => 'The image may not be greater than 2MB.',
            'attachments.*.file' => 'Each attachment must be a file.',
            'attachments.*.mimes' => 'Attachments must be files of type: pdf, doc, docx, xls, xlsx, ppt, pptx, txt.',
            'attachments.*.max' => 'Each attachment may not be greater than 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'announcement title',
            'content' => 'announcement content',
            'type' => 'announcement type',
            'priority' => 'priority level',
            'published_at' => 'published date',
            'expires_at' => 'expiration date',
            'author_name' => 'author name',
            'author_position' => 'author position',
            'image' => 'announcement image',
            'attachments.*' => 'attachment',
        ];
    }
}
