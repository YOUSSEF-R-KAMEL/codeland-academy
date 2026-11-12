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
            'course_level_id' => [
                'required',
                'integer',
                'exists:course_levels,id',
            ],

            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('groups', 'id')
                    ->where('course_level_id', $this->input('course_level_id')),
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
