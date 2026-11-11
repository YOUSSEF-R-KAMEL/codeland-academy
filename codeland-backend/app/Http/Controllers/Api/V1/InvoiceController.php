<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => [
                'sometimes',
                'string',
                'in:unpaid,partially_paid,paid',
            ],
            'payment_plan' => [
                'sometimes',
                'string',
                'in:full,monthly',
            ],
            'student_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
            'invoice_number' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'student_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
        ]);

        $query = Invoice::query()
            ->with([
                'enrollment.student:id,name',
                'installments',
            ])
            ->orderByDesc('issued_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_plan')) {
            $query->where(
                'payment_plan',
                $request->input('payment_plan')
            );
        }

        if ($request->filled('student_id')) {
            $query->whereHas('enrollment', function ($enrollmentQuery) use ($request) {
                $enrollmentQuery->where(
                    'student_id',
                    $request->input('student_id')
                );
            });
        }

        if ($request->filled('invoice_number')) {
            $query->where(
                'invoice_number',
                'like',
                '%' . $request->input('invoice_number') . '%'
            );
        }

        if ($request->filled('student_name')) {
            $query->whereHas('enrollment.student', function ($studentQuery) use ($request) {
                $studentQuery->where(
                    'name',
                    'like',
                    '%' . $request->input('student_name') . '%'
                );
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            'enrollment.student:id,name',
            'installments.payments',
            'payments.installment',
            'payments.recordedBy:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }
}
