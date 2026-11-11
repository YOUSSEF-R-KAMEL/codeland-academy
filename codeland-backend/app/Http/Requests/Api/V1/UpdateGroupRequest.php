<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('groups', 'code')->ignore($group?->id),
            ],
            'capacity' => [
                'sometimes',
                'integer',
                'min:1',
                'max:8',
            ],
            'start_date' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'end_date' => [
                'sometimes',
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
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}
