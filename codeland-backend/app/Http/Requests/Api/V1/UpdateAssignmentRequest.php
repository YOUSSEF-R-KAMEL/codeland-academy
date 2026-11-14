<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignment = $this->route('assignment');

        return [
            'course_lesson_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:course_lessons,id',
                Rule::unique('assignments', 'course_lesson_id')
                    ->ignore($assignment?->id),
            ],
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'instructions' => [
                'sometimes',
                'required',
                'string',
            ],
            'resource_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],
            'published_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'due_at' => [
                'sometimes',
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
