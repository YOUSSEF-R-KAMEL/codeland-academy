<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in([
                    'admin',
                    'instructor',
                    'parent',
                    'student',
                ]),
            ],
            'identifier' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع الحساب مطلوب.',
            'type.in' => 'نوع الحساب غير صحيح.',

            'identifier.required' => 'بيانات تسجيل الدخول مطلوبة.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }
}
