<?php

namespace App\Http\Requests\Api\V1;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseEvaluationRequest extends FormRequest
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
            'course_rating' => [
                'required',
                'integer',
                'between:1,5',
            ],
            'course_comment' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'instructor_rating' => [
                'required',
                'integer',
                'between:1,5',
            ],
            'instructor_comment' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }
}
