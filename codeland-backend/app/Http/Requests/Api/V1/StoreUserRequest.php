<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $role = $this->input('role');

        $isEmailRole = in_array($role, ['admin', 'instructor'], true);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'role' => [
                'required',
                'string',
                Rule::in(['admin', 'instructor', 'student', 'parent']),
            ],

            'email' => [
                Rule::requiredIf($isEmailRole),
                Rule::prohibitedIf(!$isEmailRole),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('users', 'phone'),
                Rule::unique('users', 'username'),
            ],

            'username' => [
                Rule::requiredIf($role === 'student'),
                Rule::prohibitedIf($role !== 'student'),
                'nullable',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username'),
                Rule::unique('users', 'email'),
                Rule::unique('users', 'phone'),
            ],

            'phone' => [
                Rule::requiredIf($role === 'parent'),
                Rule::prohibitedIf($role !== 'parent'),
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone'),
                Rule::unique('users', 'email'),
                Rule::unique('users', 'username'),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
