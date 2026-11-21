<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_payments') ?? false;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',
            ],

            'payment_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_installments', 'id')
                    ->where('invoice_id', $this->input('invoice_id')),
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
                Rule::in(['cash', 'instapay', 'bank_transfer']),
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
                'max:5000',
            ],
        ];
    }
}
