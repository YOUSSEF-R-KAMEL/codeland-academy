<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_lesson_id' => [
                'required',
                'integer',
                'exists:course_lessons,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'instructions' => [
                'required',
                'string',
            ],
            'resource_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'published_at' => [
                'nullable',
                'date',
            ],
            'due_at' => [
                'nullable',
                'date',
                'after:published_at',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
