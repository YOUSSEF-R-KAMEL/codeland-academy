<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_number' => [
                'required',
                'integer',
                'between:1,3',
            ],

            'students' => [
                'required',
                'array',
                'min:1',
            ],

            'students.*.student_id' => [
                'required',
                'integer',
                'distinct',
                'exists:users,id',
            ],

            'students.*.is_present' => [
                'required',
                'boolean',
            ],
        ];
    }
}
