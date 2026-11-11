<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_url' => [
                'required',
                'url',
                'max:2048',
            ],
            'submission_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
