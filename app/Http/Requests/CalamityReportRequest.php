<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalamityReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'location_notes' => ['nullable', 'string', 'max:1000'],
            'calamity_type' => ['required', 'string', 'in:typhoon,flood,earthquake,fire,landslide,drought,other'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'description' => ['nullable', 'string', 'max:2000'],
            'needs' => ['nullable', 'array'],
            'needs.*' => ['string', 'in:food,water,medicine,shelter,clothing,evacuation,communication,transportation,other'],
            'specific_needs' => ['nullable', 'string', 'max:1000'],
            'number_of_people' => ['required', 'integer', 'min:1', 'max:100'],
            'has_elderly' => ['boolean'],
            'has_children' => ['boolean'],
            'has_pwd' => ['boolean'],
            'has_pregnant' => ['boolean'],
            'medical_conditions' => ['nullable', 'string', 'max:1000'],
            'location_shared' => ['boolean'],
        ];
    }
}
