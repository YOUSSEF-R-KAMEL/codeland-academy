<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $filters = $request->validate([
            'invoice_id' => [
                'sometimes',
                'integer',
                'exists:invoices,id',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'pending',
                    'confirmed',
                    'rejected',
                    'refunded',
                ]),
            ],
            'payment_method' => [
                'sometimes',
                'string',
                Rule::in([
                    'instapay',
                    'cash',
                    'bank_transfer',
                    'other',
                ]),
            ],
            'from' => [
                'nullable',
                'date',
            ],
            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
        ]);

        $payments = Payment::query()
            ->with([
                'invoice.enrollment.student:id,name',
                'installment',
                'recordedBy:id,name',
            ])
            ->when(
                !empty($filters['invoice_id']),
                fn($query) => $query->where(
                    'invoice_id',
                    $filters['invoice_id']
                )
            )
            ->when(
                !empty($filters['status']),
                fn($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                !empty($filters['payment_method']),
                fn($query) => $query->where(
                    'payment_method',
                    $filters['payment_method']
                )
            )
            ->when(
                !empty($filters['from']),
                fn($query) => $query->whereDate(
                    'paid_at',
                    '>=',
                    $filters['from']
                )
            )
            ->when(
                !empty($filters['to']),
                fn($query) => $query->whereDate(
                    'paid_at',
                    '<=',
                    $filters['to']
                )
            )
            ->orderByDesc('paid_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function show(
        Request $request,
        Payment $payment
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $payment->load([
            'invoice.enrollment.student:id,name',
            'installment',
            'recordedBy:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    public function store(
        Request $request,
        PaymentService $paymentService
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',
            ],
            'payment_installment_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:payment_installments,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
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
                Rule::unique('payments', 'transaction_reference'),
            ],
            'receipt_url' => [
                'sometimes',
                'nullable',
                'url:http,https',
                'max:2048',
            ],
            'paid_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $invoice = \App\Models\Invoice::findOrFail(
            $data['invoice_id']
        );

        $payment = $paymentService->recordPayment(
            $invoice,
            $data,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $payment->load([
                'invoice.enrollment.student:id,name',
                'installment',
                'recordedBy:id,name',
            ]),
        ], 201);
    }
}
