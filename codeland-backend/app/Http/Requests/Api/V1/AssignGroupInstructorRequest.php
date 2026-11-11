<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignGroupInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'is_primary' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
