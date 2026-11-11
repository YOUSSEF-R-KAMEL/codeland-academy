<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'type' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::in([
                    'video',
                    'document',
                    'link',
                    'code',
                    'image',
                ]),
            ],
            'url' => [
                'sometimes',
                'required',
                'url',
                'max:2048',
            ],
            'is_required' => [
                'sometimes',
                'boolean',
            ],
            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],
        ];
    }
}
