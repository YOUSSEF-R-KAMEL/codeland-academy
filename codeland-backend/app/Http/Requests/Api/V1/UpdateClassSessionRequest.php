<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $group = $this->route('group');

        if (!$user->hasRole('instructor') || !$group) {
            return false;
        }

        return $group->instructors()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'course_lesson_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:course_lessons,id',
            ],
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'starts_at' => [
                'sometimes',
                'required',
                'date',
            ],
            'ends_at' => [
                'sometimes',
                'required',
                'date',
                'after:starts_at',
            ],
            'meeting_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    'scheduled',
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
