<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReplyStudentFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'admin_reply' => [
                'required',
                'string',
                'min:2',
                'max:5000',
            ],
        ];
    }
}
