<?php

namespace App\Http\Requests\Api\V1;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformFeedbackRequest extends FormRequest
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
                    'pending',
                    'in_progress',
                    'resolved',
                    'rejected',
                ]),
            ],
            'admin_reply' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
