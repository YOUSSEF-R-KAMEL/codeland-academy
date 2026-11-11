<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],
            'feedback_template_id' => [
                'required',
                'integer',
                Rule::exists('feedback_templates', 'id')
                    ->where('rating', $this->input('rating'))
                    ->where('is_active', true),
            ],
        ];
    }
}
