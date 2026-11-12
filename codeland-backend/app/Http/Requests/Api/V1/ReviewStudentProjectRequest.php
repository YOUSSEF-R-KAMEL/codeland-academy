<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewStudentProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    'reviewed',
                    'revision_required',
                ]),
            ],
            'score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'instructor_feedback' => [
                'nullable',
                'string',
                'max:5000',
                'required_if:status,revision_required',
            ],
        ];
    }
}
