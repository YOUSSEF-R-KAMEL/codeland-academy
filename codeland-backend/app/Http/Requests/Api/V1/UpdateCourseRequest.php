<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
                Rule::unique('courses', 'slug')
                    ->ignore($this->route('course')),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'thumbnail_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],

            'duration_weeks' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],

            'sessions_per_week' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:7',
            ],

            'session_duration_minutes' => [
                'sometimes',
                'required',
                'integer',
                'min:15',
            ],

            'full_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'monthly_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'currency' => [
                'sometimes',
                'required',
                'string',
                'size:3',
                'in:EGP',
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
