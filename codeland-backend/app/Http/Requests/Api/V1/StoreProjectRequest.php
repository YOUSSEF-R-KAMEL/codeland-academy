<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('groups', 'id')
                    ->where('course_id', $this->input('course_id')),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'due_at' => [
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
