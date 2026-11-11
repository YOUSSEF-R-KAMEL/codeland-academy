<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        $account = User::with('roles')->findOrFail($userId);

        $role = $account->roles->first()?->slug;

        $rules = [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'password' => [
                'sometimes',
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];

        if (in_array($role, ['admin', 'instructor'], true)) {
            $rules['email'] = [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('users', 'username')->ignore($userId),
                Rule::unique('users', 'phone')->ignore($userId),
            ];
        }

        if ($role === 'student') {
            $rules['username'] = [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($userId),
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('users', 'phone')->ignore($userId),
            ];
        }

        if ($role === 'parent') {
            $rules['phone'] = [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('users', 'username')->ignore($userId),
            ];
        }

        return $rules;
    }
}
