<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'duration_weeks' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
            'sessions_per_week' => [
                'sometimes',
                'integer',
                'min:1',
                'max:65535',
            ],
            'session_duration_minutes' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
            'monthly_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'full_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],
        ];
    }
}
