<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseLessonRequest extends FormRequest
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
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'lesson_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
            'duration_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
            'recording_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
            'published_at' => [
                'sometimes',
                'nullable',
                'date',
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
