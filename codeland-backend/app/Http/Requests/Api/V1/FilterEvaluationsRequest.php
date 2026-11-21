<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class FilterEvaluationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => [
                'nullable',
                'integer',
                'exists:courses,id',
            ],
            'instructor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'rating' => [
                'nullable',
                'integer',
                'between:1,5',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:5,100',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
