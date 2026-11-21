<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentEvaluation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportsController extends Controller
{
    public function paymentsReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Payment::query()
            ->where('status', 'confirmed')
            ->when(
                $validated['from'] ?? null,
                fn($q, $from) =>
                    $q->whereDate('paid_at', '>=', $from)
            )
            ->when(
                $validated['to'] ?? null,
                fn($q, $to) =>
                    $q->whereDate('paid_at', '<=', $to)
            );

        return response()->json([
            'success' => true,
            'data' => [
                'payments_count' => (clone $query)->count(),
                'total_amount' => (float) (clone $query)->sum('amount'),
                'by_method' => (clone $query)
                    ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('payment_method')
                    ->get(),
                'by_month' => (clone $query)
                    ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month")
                    ->selectRaw('COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
            ],
        ]);
    }

    public function invoicesReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Invoice::query()
            ->when(
                $validated['from'] ?? null,
                fn($q, $from) =>
                    $q->whereDate('issued_at', '>=', $from)
            )
            ->when(
                $validated['to'] ?? null,
                fn($q, $to) =>
                    $q->whereDate('issued_at', '<=', $to)
            );

        return response()->json([
            'success' => true,
            'data' => [
                'count' => (clone $query)->count(),
                'total_amount' => (float) (clone $query)->sum('total_amount'),
                'paid_amount' => (float) (clone $query)->sum('paid_amount'),
                'remaining_amount' => (float) (clone $query)
                    ->selectRaw('SUM(GREATEST(total_amount - paid_amount, 0)) as remaining')
                    ->value('remaining'),
                'by_status' => (clone $query)
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get(),
                'by_plan' => (clone $query)
                    ->selectRaw('payment_plan, COUNT(*) as count, SUM(total_amount) as total')
                    ->groupBy('payment_plan')
                    ->get(),
            ],
        ]);
    }

    public function outstandingReport(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with([
                'enrollment.student:id,name,email',
                'enrollment.group:id,name,code',
            ])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->when(
                $request->filled('from'),
                fn($q) =>
                    $q->whereDate('due_at', '>=', $request->date('from'))
            )
            ->when(
                $request->filled('to'),
                fn($q) =>
                    $q->whereDate('due_at', '<=', $request->date('to'))
            )
            ->orderBy('due_at')
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }
}
