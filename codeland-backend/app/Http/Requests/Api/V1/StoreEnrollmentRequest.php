<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'payment_plan' => [
                'required',
                'string',
                Rule::in(['full', 'monthly']),
            ],
            'enrolled_at' => [
                'sometimes',
                'required',
                'date',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'pending',
                    'active',
                    'completed',
                    'cancelled',
                ]),
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}
