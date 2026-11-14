<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('groups', 'code'),
            ],
            'capacity' => [
                'sometimes',
                'integer',
                'min:1',
                'max:8',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'planned',
                    'active',
                    'completed',
                    'cancelled',
                ]),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
