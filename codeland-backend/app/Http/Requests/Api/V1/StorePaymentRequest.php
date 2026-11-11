<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_installment_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:payment_installments,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    'instapay',
                    'cash',
                    'bank_transfer',
                    'other',
                ]),
            ],
            'transaction_reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'receipt_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}
