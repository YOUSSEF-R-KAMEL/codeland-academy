<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
                function ($attribute, $value, $fail) {
                    $parent = User::with('roles')->find($value);

                    if (!$parent || !$parent->hasRole('parent')) {
                        $fail('الحساب المحدد ليس حساب ولي أمر.');
                    }
                },
            ],

            'relationship' => [
                'nullable',
                'string',
                Rule::in(['father', 'mother', 'guardian']),
            ],

            'is_primary_contact' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => 'يجب تحديد ولي الأمر.',
            'parent_id.exists' => 'حساب ولي الأمر غير موجود أو غير نشط.',
            'relationship.in' => 'صلة القرابة غير صحيحة.',
        ];
    }
}
