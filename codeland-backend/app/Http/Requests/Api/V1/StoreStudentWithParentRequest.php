<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentWithParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $parentPhone = $this->input('parent.phone');

        $existingParent = is_string($parentPhone)
            ? User::where('phone', $parentPhone)
                ->whereHas('roles', fn($query) => $query->where('slug', 'parent'))
                ->exists()
            : false;

        return [
            'student.name' => [
                'required',
                'string',
                'max:255',
            ],

            'student.username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username'),
                Rule::unique('users', 'email'),
                Rule::unique('users', 'phone'),
            ],

            'student.password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'parent.name' => [
                Rule::requiredIf(!$existingParent),
                'nullable',
                'string',
                'max:255',
            ],

            'parent.phone' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($existingParent) {
                    if ($existingParent) {
                        return;
                    }

                    $exists = User::query()
                        ->where('phone', $value)
                        ->orWhere('email', $value)
                        ->orWhere('username', $value)
                        ->exists();

                    if ($exists) {
                        $fail('رقم الهاتف مستخدم بالفعل في حساب آخر.');
                    }
                },
            ],

            'parent.password' => [
                Rule::requiredIf(!$existingParent),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'parent.relationship' => [
                'nullable',
                'string',
                Rule::in(['father', 'mother', 'guardian']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student.name.required' => 'اسم الطالب مطلوب.',
            'student.username.required' => 'اسم المستخدم للطالب مطلوب.',
            'student.username.unique' => 'اسم المستخدم مستخدم بالفعل.',
            'student.password.required' => 'كلمة مرور الطالب مطلوبة.',
            'student.password.confirmed' => 'تأكيد كلمة مرور الطالب غير متطابق.',
            'parent.name.required' => 'اسم ولي الأمر مطلوب.',
            'parent.phone.required' => 'رقم هاتف ولي الأمر مطلوب.',
            'parent.password.required' => 'كلمة مرور ولي الأمر مطلوبة لإنشاء حساب جديد.',
            'parent.password.confirmed' => 'تأكيد كلمة مرور ولي الأمر غير متطابق.',
            'parent.relationship.in' => 'صلة القرابة غير صحيحة.',
        ];
    }
}
