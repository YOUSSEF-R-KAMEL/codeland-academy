<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with([
                'enrollment.student:id,name',
                'enrollment.group.courseLevel.course:id,title',
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
            'enrollment.group.courseLevel.course:id,title',
            'installments.payments',
            'payments.installment',
            'payments.recordedBy:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
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
                Rule::in(['full', 'installments']),
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

        $enrollment = Enrollment::with([
            'group.courseLevel.course',
        ])->findOrFail($data['enrollment_id']);

        $invoice = DB::transaction(function () use ($data, $enrollment) {
            return Invoice::create([
                'enrollment_id' => $enrollment->id,
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8)),
                'total_amount' => $data['total_amount'],
                'payment_plan' => $data['payment_plan'],
                'paid_amount' => 0,
                'currency' => $data['currency'] ?? 'EGP',
                'status' => 'unpaid',
                'issued_at' => $data['issued_at'] ?? now()->toDateString(),
                'due_at' => $data['due_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully.',
            'data' => $invoice->load([
                'enrollment.student:id,name',
                'installments',
            ]),
        ], 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless(
            (float) $invoice->paid_amount === 0.0,
            422,
            'Paid invoices cannot be updated.'
        );

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
                Rule::in(['full', 'installments']),
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
                'after_or_equal:issued_at',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $invoice->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully.',
            'data' => $invoice->fresh([
                'enrollment.student:id,name',
                'installments',
            ]),
        ]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
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

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully.',
        ]);
    }
}
