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
            $enrollment->loadMissing('group.course');

            $course = $enrollment->group->course;

            if ($paymentPlan === 'full') {
                if ($course->full_price === null || $course->full_price <= 0) {
                    throw ValidationException::withMessages([
                        'payment_plan' => [
                            'Full payment is not available for this course.',
                        ],
                    ]);
                }

                $totalAmount = $course->full_price;
                $installmentCount = 0;
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

                $installmentCount = intdiv($course->duration_weeks, 4);
                $totalAmount = $course->monthly_price * $installmentCount;
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
                'currency' => $course->currency,
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
                        'amount' => $course->monthly_price,
                        'due_at' => $issuedAt->copy()->addMonths($number - 1),
                        'status' => 'pending',
                    ]);
                }
            }

            return $invoice->load('installments');
        });
    }
}
