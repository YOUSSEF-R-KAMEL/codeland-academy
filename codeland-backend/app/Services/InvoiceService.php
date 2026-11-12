<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function createForEnrollment(
        Enrollment $enrollment,
        string $paymentPlan
    ): Invoice {
        return DB::transaction(function () use ($enrollment, $paymentPlan) {
            $enrollment->loadMissing('group.courseLevel');

            $level = $enrollment->group->courseLevel;

            if ($paymentPlan === 'full') {
                if ($level->full_price === null || $level->full_price <= 0) {
                    throw ValidationException::withMessages([
                        'payment_plan' => [
                            'Full payment is not available for this course level.',
                        ],
                    ]);
                }

                $totalAmount = $level->full_price;
                $installmentCount = 0;
            } elseif ($paymentPlan === 'monthly') {
                if (
                    $level->monthly_price === null ||
                    $level->monthly_price <= 0 ||
                    $level->duration_weeks <= 0 ||
                    $level->duration_weeks % 4 !== 0
                ) {
                    throw ValidationException::withMessages([
                        'payment_plan' => [
                            'Monthly payment is not available for this course level.',
                        ],
                    ]);
                }

                $installmentCount = intdiv($level->duration_weeks, 4);
                $totalAmount = $level->monthly_price * $installmentCount;
            } else {
                throw ValidationException::withMessages([
                    'payment_plan' => [
                        'Invalid payment plan.',
                    ],
                ]);
            }

            $issuedAt = $enrollment->enrolled_at
                ? $enrollment->enrolled_at->copy()
                : now()->startOfDay();

            $invoice = Invoice::create([
                'enrollment_id' => $enrollment->id,
                'invoice_number' => 'INV-' . $enrollment->id . '-' . Str::uuid(),
                'total_amount' => $totalAmount,
                'payment_plan' => $paymentPlan,
                'paid_amount' => 0,
                'currency' => $level->currency,
                'status' => 'unpaid',
                'issued_at' => $issuedAt,
                'due_at' => $paymentPlan === 'full'
                    ? $issuedAt
                    : $issuedAt->copy()->addMonths($installmentCount - 1),
            ]);

            if ($paymentPlan === 'monthly') {
                for ($number = 1; $number <= $installmentCount; $number++) {
                    $invoice->installments()->create([
                        'installment_number' => $number,
                        'amount' => $level->monthly_price,
                        'due_at' => $issuedAt->copy()->addMonths($number - 1),
                        'status' => 'pending',
                    ]);
                }
            }

            return $invoice->load('installments');
        });
    }
}
