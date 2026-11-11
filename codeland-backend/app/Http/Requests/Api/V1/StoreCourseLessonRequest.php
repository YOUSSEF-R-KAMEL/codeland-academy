<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseLessonRequest extends FormRequest
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
            'description' => [
                'nullable',
                'string',
            ],
            'lesson_number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
            'recording_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
            'published_at' => [
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
