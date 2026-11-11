<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
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
            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amountInCents = (int) round(
                (float) $data['amount'] * 100
            );

            $totalInCents = (int) round(
                (float) $lockedInvoice->total_amount * 100
            );

            $paidInCents = (int) round(
                (float) Payment::where('invoice_id', $lockedInvoice->id)
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

            if ($lockedInvoice->payment_plan === 'monthly') {
                if (empty($data['payment_installment_id'])) {
                    throw ValidationException::withMessages([
                        'payment_installment_id' => [
                            'An installment is required for monthly payment plans.',
                        ],
                    ]);
                }

                $installment = PaymentInstallment::whereKey(
                    $data['payment_installment_id']
                )
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
                    (float) Payment::where(
                        'payment_installment_id',
                        $installment->id
                    )
                        ->where('status', 'confirmed')
                        ->sum('amount') * 100
                );

                $installmentTotalInCents = (int) round(
                    (float) $installment->amount * 100
                );

                if (
                    $installmentPaidInCents + $amountInCents >
                    $installmentTotalInCents
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

            $payment = Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'payment_installment_id' => $installment?->id,
                'recorded_by' => $recordedBy,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'amount' => $data['amount'],
                'currency' => $lockedInvoice->currency,
                'payment_method' => $data['payment_method'],
                'status' => 'confirmed',
                'receipt_url' => $data['receipt_url'] ?? null,
                'paid_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $newPaidInCents = $paidInCents + $amountInCents;

            $lockedInvoice->update([
                'paid_amount' => $newPaidInCents / 100,
                'status' => $newPaidInCents >= $totalInCents
                    ? 'paid'
                    : 'partially_paid',
            ]);

            if ($installment) {
                $newInstallmentPaidInCents =
                    $installmentPaidInCents + $amountInCents;

                $installmentTotalInCents = (int) round(
                    (float) $installment->amount * 100
                );

                $installment->update([
                    'status' => $newInstallmentPaidInCents >= $installmentTotalInCents
                        ? 'paid'
                        : 'partially_paid',
                    'paid_at' => $newInstallmentPaidInCents >= $installmentTotalInCents
                        ? now()->toDateString()
                        : null,
                ]);
            }

            return $payment->load([
                'invoice',
                'installment',
                'recordedBy:id,name',
            ]);
        });
    }
}
