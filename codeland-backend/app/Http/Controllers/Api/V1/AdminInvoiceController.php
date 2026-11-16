<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with([
                'enrollment.student:id,name',
                'enrollment.group.course:id,title',
                'installments.payments',
                'payments.recordedBy:id,name',
            ])
            ->when(
                $request->filled('status'),
                fn($query) => $query->where(
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('enrollment_id'),
                fn($query) => $query->where(
                    'enrollment_id',
                    $request->input('enrollment_id')
                )
            )
            ->when(
                $request->filled('payment_plan'),
                fn($query) => $query->where(
                    'payment_plan',
                    $request->input('payment_plan')
                )
            )
            ->when(
                $request->boolean('overdue'),
                fn($query) => $query
                    ->whereNotNull('due_at')
                    ->whereDate('due_at', '<', now()->toDateString())
                    ->whereColumn('paid_amount', '<', 'total_amount')
            )
            ->orderByDesc('issued_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            'enrollment.student:id,name',
            'enrollment.group.course:id,title',
            'installments.payments',
            'payments.installment',
            'payments.recordedBy:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    public function store(
        Request $request,
        InvoiceService $invoiceService
    ): JsonResponse {
        $data = $request->validate([
            'enrollment_id' => [
                'required',
                'integer',
                'exists:enrollments,id',
                Rule::unique('invoices', 'enrollment_id'),
            ],
            'total_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'payment_plan' => [
                'required',
                Rule::in(['full', 'monthly']),
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],
            'issued_at' => [
                'sometimes',
                'date',
            ],
            'due_at' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:issued_at',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $enrollment = Enrollment::findOrFail(
            $data['enrollment_id']
        );

        $overrides = [
            'total_amount' => $data['total_amount'],
        ];

        foreach ([
            'currency',
            'issued_at',
            'due_at',
            'notes',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $overrides[$field] = $data[$field];
            }
        }

        $invoice = $invoiceService->createForEnrollment(
            $enrollment,
            $data['payment_plan'],
            $overrides
        );

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully.',
            'data' => $invoice->load([
                'enrollment.student:id,name',
                'installments',
            ]),
        ], 201);
    }

    public function update(
        Request $request,
        Invoice $invoice
    ): JsonResponse {
        $data = $request->validate([
            'total_amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'payment_plan' => [
                'sometimes',
                'required',
                Rule::in(['full', 'monthly']),
            ],
            'currency' => [
                'sometimes',
                'required',
                'string',
                'size:3',
            ],
            'issued_at' => [
                'sometimes',
                'required',
                'date',
            ],
            'due_at' => [
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

        $updatedInvoice = DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $invoice->loadMissing(
                'enrollment.group.course'
            );

            $hasPayments = $invoice->payments()->exists()
                || $invoice->installments()
                    ->whereHas('payments')
                    ->exists();

            $financialStructureChanged =
                array_key_exists('total_amount', $data)
                || array_key_exists('payment_plan', $data)
                || array_key_exists('currency', $data);

            if ($financialStructureChanged && $hasPayments) {
                abort(
                    422,
                    'Invoice amount, payment plan and currency cannot be changed after payment records exist.'
                );
            }

            $newPaymentPlan = $data['payment_plan']
                ?? $invoice->payment_plan;

            $newTotalAmount = $data['total_amount']
                ?? $invoice->total_amount;

            $scheduleChanged =
                array_key_exists('total_amount', $data)
                || array_key_exists('payment_plan', $data);

            if ($scheduleChanged) {
                if ($newPaymentPlan === 'monthly') {
                    $course = $invoice->enrollment->group->course;

                    if (
                        !$course ||
                        !$course->duration_weeks ||
                        $course->duration_weeks <= 0 ||
                        $course->duration_weeks % 4 !== 0
                    ) {
                        throw ValidationException::withMessages([
                            'payment_plan' => [
                                'Monthly installments are not available for this course duration.',
                            ],
                        ]);
                    }

                    $installmentCount = intdiv(
                        $course->duration_weeks,
                        4
                    );
                } else {
                    $installmentCount = 0;
                }

                // Existing installments can only be rebuilt
                // when no payment records are attached to them.
                if ($hasPayments) {
                    abort(
                        422,
                        'Installments cannot be rebuilt because payment records exist.'
                    );
                }

                $invoice->installments()->delete();

                if ($newPaymentPlan === 'monthly') {
                    $issuedAt = isset($data['issued_at'])
                        ? Carbon::parse($data['issued_at'])
                        : Carbon::parse($invoice->issued_at);

                    $totalCents = (int) round(
                        (float) $newTotalAmount * 100
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
                            + (
                                $number <= $remainingCents
                                ? 1
                                : 0
                            );

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
            }

            $invoice->update($data);

            return $invoice->fresh([
                'enrollment.student:id,name',
                'enrollment.group.course:id,title',
                'installments',
                'payments',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully.',
            'data' => $updatedInvoice,
        ]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        DB::transaction(function () use ($invoice) {
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            abort_unless(
                (float) $invoice->paid_amount === 0.0,
                422,
                'Paid invoices cannot be cancelled.'
            );

            abort_unless(
                $invoice->status === 'unpaid',
                422,
                'Only unpaid invoices can be cancelled.'
            );

            $hasPayments = $invoice->payments()->exists()
                || $invoice->installments()
                    ->whereHas('payments')
                    ->exists();

            abort_if(
                $hasPayments,
                422,
                'Invoices with payment records cannot be cancelled.'
            );

            $invoice->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully.',
        ]);
    }
}
