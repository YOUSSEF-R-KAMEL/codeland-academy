<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\PaymentLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with([
                'invoice.enrollment.student:id,name,email',
                'invoice.enrollment.group:id,name,code',
                'installment:id,invoice_id,installment_number',
                'recordedBy:id,name',
            ])
            ->where('status', 'confirmed')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($q) use ($search) {
                    $q->where('transaction_reference', 'like', "%{$search}%")
                        ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                            $invoiceQuery->where('invoice_number', 'like', "%{$search}%")
                                ->orWhereHas('enrollment.student', function ($studentQuery) use ($search) {
                                    $studentQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when(
                $request->filled('from'),
                fn($q) =>
                    $q->whereDate('paid_at', '>=', $request->date('from'))
            )
            ->when(
                $request->filled('to'),
                fn($q) =>
                    $q->whereDate('paid_at', '<=', $request->date('to'))
            )
            ->latest('paid_at')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = DB::transaction(function () use ($request) {
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($request->integer('invoice_id'));

            if ($invoice->trashed()) {
                abort(422, 'Cannot record a payment for a deleted invoice.');
            }

            $amount = (float) $request->input('amount');
            $remaining = max(
                0,
                round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2)
            );

            if ($remaining <= 0) {
                abort(422, 'This invoice is already fully paid.');
            }

            if ($amount > $remaining) {
                abort(422, 'Payment amount exceeds the remaining invoice balance.');
            }

            $installment = null;

            if ($request->filled('payment_installment_id')) {
                $installment = PaymentInstallment::query()
                    ->where('invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->findOrFail($request->integer('payment_installment_id'));

                $installmentPaid = (float) Payment::query()
                    ->where('payment_installment_id', $installment->id)
                    ->where('status', 'confirmed')
                    ->sum('amount');

                $installmentRemaining = max(
                    0,
                    round((float) $installment->amount - $installmentPaid, 2)
                );

                if ($installmentRemaining <= 0 || $amount > $installmentRemaining) {
                    abort(422, 'Payment amount exceeds the installment remaining balance.');
                }
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_installment_id' => $installment?->id,
                'recorded_by' => $request->user()->id,
                'transaction_reference' => $request->input('transaction_reference'),
                'amount' => $amount,
                'currency' => $invoice->currency,
                'payment_method' => $request->input('payment_method'),
                'status' => 'confirmed',
                'paid_at' => $request->date('paid_at'),
                'notes' => $request->input('notes'),
            ]);

            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newPaidAmount >= (float) $invoice->total_amount
                    ? 'paid'
                    : 'partially_paid',
            ]);

            if ($installment) {
                $newInstallmentPaid = round($installmentPaid + $amount, 2);

                if ($newInstallmentPaid >= (float) $installment->amount) {
                    $installment->update([
                        'status' => 'paid',
                        'paid_at' => $request->date('paid_at'),
                    ]);
                } else {
                    $installment->update([
                        'status' => 'partially_paid',
                    ]);
                }
            }

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $payment->load([
                'invoice.enrollment.student:id,name,email',
                'installment',
                'recordedBy:id,name',
            ]),
        ], 201);
    }


    private function syncInvoiceFinancials(Invoice $invoice): void
    {
        $paidAmount = (float) Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        $paidAmount = round($paidAmount, 2);

        $invoiceStatus = match (true) {
            $paidAmount >= (float) $invoice->total_amount => 'paid',
            $paidAmount > 0 => 'partially_paid',
            default => 'pending',
        };

        $invoice->update([
            'paid_amount' => $paidAmount,
            'status' => $invoiceStatus,
        ]);

        $installments = PaymentInstallment::query()
            ->where('invoice_id', $invoice->id)
            ->lockForUpdate()
            ->get();

        foreach ($installments as $installment) {
            $installmentPaid = (float) Payment::query()
                ->where('payment_installment_id', $installment->id)
                ->where('status', 'confirmed')
                ->sum('amount');

            $installmentPaid = round($installmentPaid, 2);

            $status = match (true) {
                $installmentPaid >= (float) $installment->amount => 'paid',
                $installmentPaid > 0 => 'partially_paid',
                default => 'pending',
            };

            $installmentPaidAt = $status === 'paid'
                ? Payment::query()
                    ->where('payment_installment_id', $installment->id)
                    ->where('status', 'confirmed')
                    ->max('paid_at')
                : null;

            $installment->update([
                'status' => $status,
                'paid_at' => $installmentPaidAt,
            ]);
        }
    }

    public function update(
        UpdatePaymentRequest $request,
        int $payment
    ): JsonResponse {
        $updatedPayment = DB::transaction(function () use ($request, $payment) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment);

            if ($payment->status !== 'confirmed') {
                abort(422, 'Only confirmed payments can be edited.');
            }

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            if ($invoice->trashed()) {
                abort(422, 'Cannot edit a payment for a deleted invoice.');
            }

            $data = $request->validated();

            $oldData = [
                'amount' => $payment->amount,
                'payment_installment_id' => $payment->payment_installment_id,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
                'transaction_reference' => $payment->transaction_reference,
                'notes' => $payment->notes,
            ];

            $newInstallment = null;

            if (!empty($data['payment_installment_id'])) {
                $newInstallment = PaymentInstallment::query()
                    ->where('invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->findOrFail($data['payment_installment_id']);
            }

            $invoicePaidWithoutCurrentPayment = (float) Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'confirmed')
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            $invoiceRemaining = max(
                0,
                round(
                    (float) $invoice->total_amount
                    - $invoicePaidWithoutCurrentPayment,
                    2
                )
            );

            $amount = round((float) $data['amount'], 2);

            if ($amount > $invoiceRemaining) {
                abort(422, 'Payment amount exceeds the remaining invoice balance.');
            }

            if ($newInstallment) {
                $installmentPaidWithoutCurrentPayment = (float) Payment::query()
                    ->where('payment_installment_id', $newInstallment->id)
                    ->where('status', 'confirmed')
                    ->where('id', '!=', $payment->id)
                    ->sum('amount');

                $installmentRemaining = max(
                    0,
                    round(
                        (float) $newInstallment->amount
                        - $installmentPaidWithoutCurrentPayment,
                        2
                    )
                );

                if ($amount > $installmentRemaining) {
                    abort(422, 'Payment amount exceeds the installment remaining balance.');
                }
            }

            $payment->update([
                'payment_installment_id' => $newInstallment?->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'paid_at' => $data['paid_at'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncInvoiceFinancials($invoice);

            PaymentLog::create([
                'payment_id' => $payment->id,
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'from_status' => 'confirmed',
                'to_status' => 'confirmed',
                'reason' => json_encode([
                    'before' => $oldData,
                    'after' => [
                        'amount' => $payment->amount,
                        'payment_installment_id' => $payment->payment_installment_id,
                        'payment_method' => $payment->payment_method,
                        'paid_at' => $payment->paid_at?->toDateTimeString(),
                        'transaction_reference' => $payment->transaction_reference,
                        'notes' => $payment->notes,
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => $updatedPayment->load([
                'invoice.enrollment.student:id,name,email',
                'installment',
                'recordedBy:id,name',
            ]),
        ]);
    }

    public function destroy(Request $request, int $payment): JsonResponse
    {
        DB::transaction(function () use ($request, $payment) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment);

            if ($payment->status !== 'confirmed') {
                abort(422, 'Only confirmed payments can be cancelled.');
            }

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            if ($invoice->trashed()) {
                abort(422, 'Cannot cancel a payment for a deleted invoice.');
            }

            $oldStatus = $payment->status;

            $payment->update([
                'status' => 'cancelled',
            ]);

            $this->syncInvoiceFinancials($invoice);

            PaymentLog::create([
                'payment_id' => $payment->id,
                'user_id' => $request->user()->id,
                'action' => 'cancelled',
                'from_status' => $oldStatus,
                'to_status' => 'cancelled',
                'reason' => 'Payment cancelled by admin.',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully.',
        ]);
    }


    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with([
                'enrollment.student:id,name,email',
                'enrollment.group:id,name,code',
                'installments',
            ])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('enrollment.student', function ($studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('issued_at')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }
}
