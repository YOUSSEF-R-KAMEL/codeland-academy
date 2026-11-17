<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function recordPayment(
        Invoice $invoice,
        array $data,
        int $recordedBy
    ): Payment {
        return DB::transaction(function () use ($invoice, $data, $recordedBy) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $amountInCents = (int) round((float) $data['amount'] * 100);
            $totalInCents = (int) round((float) $lockedInvoice->total_amount * 100);

            $paidInCents = (int) round(
                (float) $lockedInvoice->payments()
                    ->where('status', 'confirmed')
                    ->sum('amount') * 100
            );

            if ($paidInCents + $amountInCents > $totalInCents) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'The payment amount exceeds the invoice remaining balance.',
                    ],
                ]);
            }

            $installment = null;
            $installmentPaidInCents = 0;
            $installmentTotalInCents = 0;

            if ($lockedInvoice->payment_plan === 'monthly') {
                if (empty($data['payment_installment_id'])) {
                    throw ValidationException::withMessages([
                        'payment_installment_id' => [
                            'An installment is required for monthly payment plans.',
                        ],
                    ]);
                }

                $installment = PaymentInstallment::query()
                    ->whereKey($data['payment_installment_id'])
                    ->where('invoice_id', $lockedInvoice->id)
                    ->lockForUpdate()
                    ->first();

                if (!$installment) {
                    throw ValidationException::withMessages([
                        'payment_installment_id' => [
                            'The selected installment does not belong to this invoice.',
                        ],
                    ]);
                }

                $installmentPaidInCents = (int) round(
                    (float) $installment->payments()
                        ->where('status', 'confirmed')
                        ->sum('amount') * 100
                );

                $installmentTotalInCents = (int) round(
                    (float) $installment->amount * 100
                );

                if (
                    $installmentPaidInCents + $amountInCents
                    > $installmentTotalInCents
                ) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'The payment amount exceeds the installment remaining balance.',
                        ],
                    ]);
                }
            } elseif (!empty($data['payment_installment_id'])) {
                throw ValidationException::withMessages([
                    'payment_installment_id' => [
                        'Installments are not available for full payment plans.',
                    ],
                ]);
            }

            $status = $data['status'] ?? 'confirmed';

            $payment = Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'payment_installment_id' => $installment?->id,
                'recorded_by' => $recordedBy,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'amount' => $data['amount'],
                'currency' => $lockedInvoice->currency,
                'payment_method' => $data['payment_method'],
                'status' => $status,
                'receipt_url' => $data['receipt_url'] ?? null,
                'paid_at' => $status === 'confirmed'
                    ? ($data['paid_at'] ?? now())
                    : null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->logPaymentAction(
                $payment,
                $recordedBy,
                'created',
                null,
                $status
            );

            if ($status === 'confirmed') {
                $newPaidInCents = $paidInCents + $amountInCents;

                $lockedInvoice->update([
                    'paid_amount' => number_format($newPaidInCents / 100, 2, '.', ''),
                    'status' => match (true) {
                        $newPaidInCents >= $totalInCents => 'paid',
                        $newPaidInCents > 0 => 'partially_paid',
                        default => 'unpaid',
                    },
                ]);

                if ($installment) {
                    $newInstallmentPaidInCents =
                        $installmentPaidInCents + $amountInCents;

                    $installment->update([
                        'status' => match (true) {
                            $newInstallmentPaidInCents >= $installmentTotalInCents => 'paid',
                            $newInstallmentPaidInCents > 0 => 'partially_paid',
                            default => 'pending',
                        },
                        'paid_at' => $newInstallmentPaidInCents >= $installmentTotalInCents
                            ? now()->toDateString()
                            : null,
                    ]);
                }
            }

            return $payment->load([
                'invoice',
                'installment',
                'recordedBy:id,name',
            ]);
        });
    }

    public function confirmPayment(
        Payment $payment,
        int $confirmedBy
    ): Payment {
        return DB::transaction(function () use ($payment, $confirmedBy) {
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only pending payments can be confirmed.',
                    ],
                ]);
            }

            $amountInCents = (int) round((float) $lockedPayment->amount * 100);
            $totalInCents = (int) round((float) $invoice->total_amount * 100);

            $paidInCents = (int) round(
                (float) $invoice->payments()
                    ->where('status', 'confirmed')
                    ->sum('amount') * 100
            );

            if ($paidInCents + $amountInCents > $totalInCents) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Confirming this payment would exceed the invoice balance.',
                    ],
                ]);
            }

            $installment = null;

            if ($invoice->payment_plan === 'monthly') {
                if (!$lockedPayment->payment_installment_id) {
                    throw ValidationException::withMessages([
                        'payment_installment_id' => [
                            'A monthly payment must belong to an installment.',
                        ],
                    ]);
                }
            }

            if ($lockedPayment->payment_installment_id) {
                $installment = PaymentInstallment::query()
                    ->whereKey($lockedPayment->payment_installment_id)
                    ->where('invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->first();

                if (!$installment) {
                    throw ValidationException::withMessages([
                        'payment_installment_id' => [
                            'The selected installment does not belong to this invoice.',
                        ],
                    ]);
                }

                $installmentPaidInCents = (int) round(
                    (float) $installment->payments()
                        ->where('status', 'confirmed')
                        ->sum('amount') * 100
                );

                $installmentTotalInCents = (int) round(
                    (float) $installment->amount * 100
                );

                if (
                    $installmentPaidInCents + $amountInCents
                    > $installmentTotalInCents
                ) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'Confirming this payment would exceed the installment balance.',
                        ],
                    ]);
                }
            }

            $lockedPayment->update([
                'status' => 'confirmed',
                'paid_at' => $lockedPayment->paid_at ?? now(),
            ]);

            $this->logPaymentAction(
                $lockedPayment,
                $confirmedBy,
                'confirmed',
                'pending',
                'confirmed'
            );

            $this->syncFinancialStatus($invoice, $installment);

            return $lockedPayment->fresh([
                'invoice',
                'installment',
                'recordedBy:id,name',
            ]);
        });
    }

    public function rejectPayment(
        Payment $payment,
        int $rejectedBy,
        ?string $reason = null
    ): Payment {
        return DB::transaction(function () use ($payment, $rejectedBy, $reason) {
            Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only pending payments can be rejected.',
                    ],
                ]);
            }

            $lockedPayment->update([
                'status' => 'rejected',
            ]);

            $this->logPaymentAction(
                $lockedPayment,
                $rejectedBy,
                'rejected',
                'pending',
                'rejected',
                $reason
            );

            return $lockedPayment->fresh([
                'invoice',
                'installment',
                'recordedBy:id,name',
            ]);
        });
    }

    public function refundPayment(
        Payment $payment,
        int $refundedBy,
        ?string $reason = null
    ): Payment {
        return DB::transaction(function () use ($payment, $refundedBy, $reason) {
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only confirmed payments can be refunded.',
                    ],
                ]);
            }

            $installment = null;

            if ($lockedPayment->payment_installment_id) {
                $installment = PaymentInstallment::query()
                    ->whereKey($lockedPayment->payment_installment_id)
                    ->where('invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $lockedPayment->update([
                'status' => 'refunded',
            ]);

            $this->logPaymentAction(
                $lockedPayment,
                $refundedBy,
                'refunded',
                'confirmed',
                'refunded',
                $reason
            );

            $this->syncFinancialStatus($invoice, $installment);

            return $lockedPayment->fresh([
                'invoice',
                'installment',
                'recordedBy:id,name',
            ]);
        });
    }

    private function syncFinancialStatus(
        Invoice $invoice,
        ?PaymentInstallment $installment = null
    ): void {
        $paidInCents = (int) round(
            (float) $invoice->payments()
                ->where('status', 'confirmed')
                ->sum('amount') * 100
        );

        $totalInCents = (int) round(
            (float) $invoice->total_amount * 100
        );

        $invoice->update([
            'paid_amount' => number_format(
                $paidInCents / 100,
                2,
                '.',
                ''
            ),
            'status' => match (true) {
                $paidInCents >= $totalInCents => 'paid',
                $paidInCents > 0 => 'partially_paid',
                default => 'unpaid',
            },
        ]);

        if (!$installment) {
            return;
        }

        $installmentPaidInCents = (int) round(
            (float) $installment->payments()
                ->where('status', 'confirmed')
                ->sum('amount') * 100
        );

        $installmentTotalInCents = (int) round(
            (float) $installment->amount * 100
        );

        $installment->update([
            'status' => match (true) {
                $installmentPaidInCents >= $installmentTotalInCents => 'paid',
                $installmentPaidInCents > 0 => 'partially_paid',
                default => 'pending',
            },
            'paid_at' => $installmentPaidInCents >= $installmentTotalInCents
                ? now()->toDateString()
                : null,
        ]);
    }

    private function logPaymentAction(
        Payment $payment,
        ?int $userId,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason = null
    ): void {
        PaymentLog::create([
            'payment_id' => $payment->id,
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
        ]);
    }
}
