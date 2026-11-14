<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        $courseId = $this->input(
            'course_id',
            $project?->course_id
        );

        return [
            'course_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:courses,id',
            ],

            'group_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('groups', 'id')
                    ->where('course_id', $courseId),
            ],

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

            'due_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
