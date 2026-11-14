<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:courses,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'thumbnail_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'duration_weeks' => [
                'required',
                'integer',
                'min:1',
            ],

            'sessions_per_week' => [
                'required',
                'integer',
                'min:1',
                'max:7',
            ],

            'session_duration_minutes' => [
                'required',
                'integer',
                'min:15',
            ],

            'full_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'monthly_price' => [
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
