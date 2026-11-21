<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_payments') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_installment_id' => [
                'nullable',
                'integer',
                'exists:payment_installments,id',
            ],

            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    'cash',
                    'instapay',
                    'bank_transfer',
                ]),
            ],

            'paid_at' => [
                'required',
                'date',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
