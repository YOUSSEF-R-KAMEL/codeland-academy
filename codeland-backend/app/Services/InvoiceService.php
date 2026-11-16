<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function createForEnrollment(
        Enrollment $enrollment,
        string $paymentPlan,
        array $overrides = []
    ): Invoice {
        return DB::transaction(function () use ($enrollment, $paymentPlan, $overrides) {
            $enrollment->loadMissing('group.course');

            $course = $enrollment->group->course;

            if ($paymentPlan === 'full') {
                if (
                    $course->full_price === null ||
                    $course->full_price <= 0
                ) {
                    throw ValidationException::withMessages([
                        'payment_plan' => [
                            'Full payment is not available for this course.',
                        ],
                    ]);
                }

                $installmentCount = 0;
                $defaultAmount = $course->full_price;

            } elseif ($paymentPlan === 'monthly') {
                if (
                    $course->monthly_price === null ||
                    $course->monthly_price <= 0 ||
                    $course->duration_weeks <= 0 ||
                    $course->duration_weeks % 4 !== 0
                ) {
                    throw ValidationException::withMessages([
                        'payment_plan' => [
                            'Monthly payment is not available for this course.',
                        ],
                    ]);
                }

                $installmentCount = intdiv(
                    $course->duration_weeks,
                    4
                );

                $defaultAmount =
                    $course->monthly_price * $installmentCount;

            } else {
                throw ValidationException::withMessages([
                    'payment_plan' => [
                        'Invalid payment plan.',
                    ],
                ]);
            }

            $totalAmount = $overrides['total_amount']
                ?? $defaultAmount;

            $issuedAt = isset($overrides['issued_at'])
                ? Carbon::parse($overrides['issued_at'])
                : (
                    $enrollment->enrolled_at
                    ? $enrollment->enrolled_at->copy()
                    : now()->startOfDay()
                );

            $defaultDueAt = $paymentPlan === 'full'
                ? $issuedAt
                : $issuedAt->copy()->addMonths(
                    $installmentCount - 1
                );

            $dueAt = array_key_exists('due_at', $overrides)
                ? $overrides['due_at']
                : $defaultDueAt;

            $invoice = Invoice::create([
                'enrollment_id' => $enrollment->id,
                'invoice_number' => 'INV-' .
                    $enrollment->id . '-' . Str::uuid(),
                'total_amount' => $totalAmount,
                'payment_plan' => $paymentPlan,
                'paid_amount' => 0,
                'currency' => $overrides['currency']
                    ?? $course->currency,
                'status' => 'unpaid',
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'notes' => $overrides['notes'] ?? null,
            ]);

            if ($paymentPlan === 'monthly') {
                $totalCents = (int) round(
                    (float) $totalAmount * 100
                );

                $baseCents = intdiv(
                    $totalCents,
                    $installmentCount
                );

                $remainingCents = $totalCents
                    % $installmentCount;

                for (
                    $number = 1;
                    $number <= $installmentCount;
                    $number++
                ) {
                    $amountCents = $baseCents
                        + ($number <= $remainingCents ? 1 : 0);

                    $invoice->installments()->create([
                        'installment_number' => $number,
                        'amount' => number_format(
                            $amountCents / 100,
                            2,
                            '.',
                            ''
                        ),
                        'due_at' => $issuedAt->copy()->addMonths(
                            $number - 1
                        ),
                        'status' => 'pending',
                    ]);
                }
            }

            return $invoice->load('installments');
        });
    }
}
