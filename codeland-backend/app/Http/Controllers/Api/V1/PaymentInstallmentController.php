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


    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless(
            $invoice->payment_plan === 'installments',
            422,
            'Installments are not allowed for this invoice.'
        );

        $data = $request->validate([
            'installment_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('payment_installments', 'installment_number')
                    ->where('invoice_id', $invoice->id),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
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
            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingAmount = $lockedInvoice->installments()
                ->sum('amount');

            $totalCents = (int) round((float) $lockedInvoice->total_amount * 100);
            $existingCents = (int) round((float) $existingAmount * 100);
            $newCents = (int) round((float) $data['amount'] * 100);

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
        abort_unless($installment->invoice_id === $invoice->id, 404);

        abort_if(
            $installment->payments()->exists(),
            422,
            'Installments with payment records cannot be updated.'
        );

        $data = $request->validate([
            'installment_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('payment_installments', 'installment_number')
                    ->where('invoice_id', $invoice->id)
                    ->ignore($installment->id),
            ],
            'amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
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

        DB::transaction(function () use ($invoice, $installment, $data) {
            $lockedInvoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (isset($data['amount'])) {
                $otherAmount = $lockedInvoice->installments()
                    ->where('id', '!=', $installment->id)
                    ->sum('amount');

                $totalCents = (int) round((float) $lockedInvoice->total_amount * 100);
                $otherCents = (int) round((float) $otherAmount * 100);
                $newCents = (int) round((float) $data['amount'] * 100);

                abort_if(
                    $otherCents + $newCents > $totalCents,
                    422,
                    'The total installment amount cannot exceed the invoice amount.'
                );
            }

            $installment->update($data);
        });

        return response()->json([
            'success' => true,
            'message' => 'Installment updated successfully.',
            'data' => $installment->fresh(),
        ]);
    }

    public function destroy(
        Invoice $invoice,
        PaymentInstallment $installment
    ): JsonResponse {
        abort_unless($installment->invoice_id === $invoice->id, 404);

        abort_if(
            $installment->payments()->exists(),
            422,
            'Installments with payment records cannot be deleted.'
        );

        $installment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Installment deleted successfully.',
        ]);
    }
}
