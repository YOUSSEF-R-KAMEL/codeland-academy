<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $filters = $request->validate([
            'invoice_id' => ['sometimes', 'integer', 'exists:invoices,id'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'pending',
                    'confirmed',
                    'rejected',
                    'refunded',
                ])
            ],
            'payment_method' => [
                'sometimes',
                'string',
                Rule::in([
                    'instapay',
                    'cash',
                    'bank_transfer',
                    'other',
                ])
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
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

    public function show(Request $request, Payment $payment): JsonResponse
    {
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

    public function store(Request $request): JsonResponse
    {
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

        $payment = DB::transaction(function () use ($data, $request) {
            $invoice = Invoice::query()
                ->whereKey($data['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amountCents = (int) round((float) $data['amount'] * 100);
            $totalCents = (int) round((float) $invoice->total_amount * 100);
            $paidCents = (int) round((float) $invoice->paid_amount * 100);
            $remainingCents = max(0, $totalCents - $paidCents);

            abort_if(
                $amountCents > $remainingCents,
                422,
                'Payment amount exceeds the remaining invoice balance.'
            );

            $installment = null;

            if ($invoice->payment_plan === 'installments') {
                abort_unless(
                    !empty($data['payment_installment_id']),
                    422,
                    'An installment must be selected for this invoice.'
                );

                $installment = PaymentInstallment::query()
                    ->whereKey($data['payment_installment_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    (int) $installment->invoice_id === (int) $invoice->id,
                    422,
                    'The selected installment does not belong to this invoice.'
                );

                $installmentPaidCents = (int) round(
                    (float) $installment->payments()
                        ->where('status', 'confirmed')
                        ->sum('amount') * 100
                );

                $installmentTotalCents = (int) round(
                    (float) $installment->amount * 100
                );

                abort_if(
                    $amountCents > max(
                        0,
                        $installmentTotalCents - $installmentPaidCents
                    ),
                    422,
                    'Payment amount exceeds the remaining installment balance.'
                );
            } else {
                abort_if(
                    !empty($data['payment_installment_id']),
                    422,
                    'Installments cannot be selected for a full-payment invoice.'
                );
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_installment_id' => $installment?->id,
                'recorded_by' => $request->user()->id,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'amount' => $data['amount'],
                'currency' => $invoice->currency,
                'payment_method' => $data['payment_method'],
                'status' => 'confirmed',
                'receipt_url' => $data['receipt_url'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $newPaidCents = $paidCents + $amountCents;

            $invoice->update([
                'paid_amount' => number_format(
                    $newPaidCents / 100,
                    2,
                    '.',
                    ''
                ),
                'status' => match (true) {
                    $newPaidCents >= $totalCents => 'paid',
                    $newPaidCents > 0 => 'partially_paid',
                    default => 'unpaid',
                },
            ]);

            if ($installment) {
                $installmentPaidCents += $amountCents;

                $installmentTotalCents = (int) round(
                    (float) $installment->amount * 100
                );

                $installment->update([
                    'status' => match (true) {
                        $installmentPaidCents >= $installmentTotalCents => 'paid',
                        $installmentPaidCents > 0 => 'partially_paid',
                        default => 'pending',
                    },
                    'paid_at' => $installmentPaidCents >= $installmentTotalCents
                        ? now()->toDateString()
                        : null,
                ]);
            }

            return $payment;
        });

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
