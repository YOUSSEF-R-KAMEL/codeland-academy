<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentInstallmentController extends Controller
{
    public function index(Request $request, Invoice $invoice): JsonResponse
    {
        $installments = $invoice->installments()
            ->with('payments')
            ->when(
                $request->boolean('overdue'),
                fn($query) => $query
                    ->whereDate('due_at', '<', now()->toDateString())
                    ->where('status', '!=', 'paid')
            )
            ->orderBy('installment_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $installments,
        ]);
    }

    public function store(
        Request $request,
        Invoice $invoice
    ): JsonResponse {
        $data = $request->validate([
            'installment_number' => [
                'required',
                'integer',
                'min:1',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'due_at' => [
                'required',
                'date',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $installment = DB::transaction(function () use ($invoice, $data) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            abort_unless(
                $lockedInvoice->payment_plan === 'monthly',
                422,
                'Installments are only allowed for monthly invoices.'
            );

            $numberExists = $lockedInvoice->installments()
                ->where(
                    'installment_number',
                    $data['installment_number']
                )
                ->exists();

            abort_if(
                $numberExists,
                422,
                'This installment number already exists.'
            );

            $existingCents = (int) round(
                (float) $lockedInvoice->installments()
                    ->sum('amount') * 100
            );

            $totalCents = (int) round(
                (float) $lockedInvoice->total_amount * 100
            );

            $newCents = (int) round(
                (float) $data['amount'] * 100
            );

            abort_if(
                $existingCents + $newCents > $totalCents,
                422,
                'The total installment amount cannot exceed the invoice amount.'
            );

            return $lockedInvoice->installments()->create([
                'installment_number' => $data['installment_number'],
                'amount' => $data['amount'],
                'due_at' => $data['due_at'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Installment created successfully.',
            'data' => $installment,
        ], 201);
    }

    public function update(
        Request $request,
        Invoice $invoice,
        PaymentInstallment $installment
    ): JsonResponse {
        abort_unless(
            $installment->invoice_id === $invoice->id,
            404
        );

        $data = $request->validate([
            'installment_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],
            'amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'due_at' => [
                'sometimes',
                'required',
                'date',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $updatedInstallment = DB::transaction(function () use ($invoice, $installment, $data) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            abort_unless(
                $lockedInvoice->payment_plan === 'monthly',
                422,
                'Installments are only allowed for monthly invoices.'
            );

            $lockedInstallment = PaymentInstallment::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->lockForUpdate()
                ->findOrFail($installment->id);

            abort_if(
                $lockedInstallment->payments()->exists(),
                422,
                'Installments with payment records cannot be updated.'
            );

            if (isset($data['installment_number'])) {
                $numberExists = $lockedInvoice->installments()
                    ->where(
                        'installment_number',
                        $data['installment_number']
                    )
                    ->where('id', '!=', $lockedInstallment->id)
                    ->exists();

                abort_if(
                    $numberExists,
                    422,
                    'This installment number already exists.'
                );
            }

            if (isset($data['amount'])) {
                $otherCents = (int) round(
                    (float) $lockedInvoice->installments()
                        ->where('id', '!=', $lockedInstallment->id)
                        ->sum('amount') * 100
                );

                $totalCents = (int) round(
                    (float) $lockedInvoice->total_amount * 100
                );

                $newCents = (int) round(
                    (float) $data['amount'] * 100
                );

                abort_if(
                    $otherCents + $newCents > $totalCents,
                    422,
                    'The total installment amount cannot exceed the invoice amount.'
                );
            }

            $lockedInstallment->update($data);

            return $lockedInstallment->fresh('payments');
        });

        return response()->json([
            'success' => true,
            'message' => 'Installment updated successfully.',
            'data' => $updatedInstallment,
        ]);
    }

    public function destroy(
        Invoice $invoice,
        PaymentInstallment $installment
    ): JsonResponse {
        abort_unless(
            $installment->invoice_id === $invoice->id,
            404
        );

        DB::transaction(function () use ($invoice, $installment) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            abort_unless(
                $lockedInvoice->payment_plan === 'monthly',
                422,
                'Installments are only allowed for monthly invoices.'
            );

            $lockedInstallment = PaymentInstallment::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->lockForUpdate()
                ->findOrFail($installment->id);

            abort_if(
                $lockedInstallment->payments()->exists(),
                422,
                'Installments with payment records cannot be deleted.'
            );

            $lockedInstallment->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Installment deleted successfully.',
        ]);
    }
}
