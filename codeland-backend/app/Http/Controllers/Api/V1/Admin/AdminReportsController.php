<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReportsController extends Controller
{
    public function paymentsReport(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => [
                'nullable',
                Rule::in(['pending', 'confirmed', 'rejected', 'refunded']),
            ],
            'payment_method' => [
                'nullable',
                Rule::in(['instapay', 'cash', 'bank_transfer', 'other']),
            ],
            'recorded_by' => ['nullable', 'integer', 'exists:users,id'],
            'student_id' => ['nullable', 'integer', 'exists:users,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Payment::query()
            ->with([
                'invoice.enrollment.student',
                'invoice.enrollment.group.course',
                'installment',
                'recordedBy',
            ]);

        if (!empty($filters['from'])) {
            $query->whereDate('paid_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('paid_at', '<=', $filters['to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['recorded_by'])) {
            $query->where('recorded_by', $filters['recorded_by']);
        }

        if (!empty($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }

        if (!empty($filters['student_id'])) {
            $query->whereHas('invoice.enrollment', function ($enrollmentQuery) use ($filters) {
                $enrollmentQuery->where('student_id', $filters['student_id']);
            });
        }

        $summary = (clone $query)
            ->setEagerLoads([])
            ->selectRaw('
                COUNT(*) as total_payments,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN status = "confirmed" THEN amount ELSE 0 END), 0) as confirmed_amount,
                COALESCE(SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END), 0) as pending_amount,
                COALESCE(SUM(CASE WHEN status = "rejected" THEN amount ELSE 0 END), 0) as rejected_amount,
                COALESCE(SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END), 0) as refunded_amount
            ')
            ->first();
        $summary = $summary->getAttributes();

        $paymentsByMethod = (clone $query)
            ->setEagerLoads([])
            ->selectRaw('
                payment_method,
                COUNT(*) as payments_count,
                COALESCE(SUM(amount), 0) as total_amount
            ')
            ->groupBy('payment_method')
            ->get();

        $payments = $query
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'payments_by_method' => $paymentsByMethod,
                'payments' => $payments,
            ],
        ]);
    }


    public function invoicesReport(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => [
                'nullable',
                Rule::in(['unpaid', 'partially_paid', 'paid']),
            ],
            'payment_plan' => [
                'nullable',
                Rule::in(['full', 'monthly']),
            ],
            'student_id' => ['nullable', 'integer', 'exists:users,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Invoice::query()
            ->with([
                'enrollment.student',
                'enrollment.group.course',
                'installments.payments',
            ]);

        if (!empty($filters['from'])) {
            $query->whereDate('issued_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('issued_at', '<=', $filters['to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_plan'])) {
            $query->where('payment_plan', $filters['payment_plan']);
        }

        if (!empty($filters['student_id'])) {
            $query->whereHas('enrollment', function ($enrollmentQuery) use ($filters) {
                $enrollmentQuery->where('student_id', $filters['student_id']);
            });
        }

        if (!empty($filters['group_id'])) {
            $query->whereHas('enrollment', function ($enrollmentQuery) use ($filters) {
                $enrollmentQuery->where('group_id', $filters['group_id']);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->whereHas('enrollment.group', function ($groupQuery) use ($filters) {
                $groupQuery->where('course_id', $filters['course_id']);
            });
        }

        $summary = (clone $query)
            ->setEagerLoads([])
            ->selectRaw('
            COUNT(*) as total_invoices,
            COALESCE(SUM(total_amount), 0) as total_amount,
            COALESCE(SUM(paid_amount), 0) as paid_amount,
            COALESCE(SUM(GREATEST(total_amount - paid_amount, 0)), 0) as remaining_amount,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_invoices_count,
            SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_invoices_count,
            SUM(CASE WHEN status = "partially_paid" THEN 1 ELSE 0 END) as partially_paid_invoices_count,
            SUM(
                CASE
                    WHEN due_at < ?
                    AND paid_amount < total_amount
                    THEN 1 ELSE 0
                END
            ) as overdue_invoices_count
        ', [now()->toDateString()])
            ->first();
        $summary = $summary->getAttributes();
        foreach ([
            'total_invoices',
            'paid_invoices_count',
            'unpaid_invoices_count',
            'partially_paid_invoices_count',
            'overdue_invoices_count',
        ] as $key) {
            $summary[$key] = (int) $summary[$key];
        }

        $invoices = $query
            ->latest('issued_at')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'invoices' => $invoices,
            ],
        ]);
    }

    public function outstandingReport(Request $request)
    {
        $filters = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:users,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'overdue_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Invoice::query()
            ->with([
                'enrollment.student',
                'enrollment.group.course',
                'installments.payments',
            ])
            ->whereColumn('paid_amount', '<', 'total_amount');

        if (!empty($filters['student_id'])) {
            $query->whereHas('enrollment', function ($q) use ($filters) {
                $q->where('student_id', $filters['student_id']);
            });
        }

        if (!empty($filters['group_id'])) {
            $query->whereHas('enrollment', function ($q) use ($filters) {
                $q->where('group_id', $filters['group_id']);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->whereHas('enrollment.group', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (filter_var($filters['overdue_only'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('due_at')
                ->whereDate('due_at', '<', now()->toDateString());
        }

        $summary = (clone $query)
            ->setEagerLoads([])
            ->leftJoin('enrollments', 'enrollments.id', '=', 'invoices.enrollment_id')
            ->selectRaw('
        COUNT(DISTINCT invoices.id) as outstanding_invoices_count,
        COUNT(DISTINCT enrollments.student_id) as students_count,
        COALESCE(SUM(invoices.total_amount - invoices.paid_amount), 0) as total_outstanding_amount,
        COALESCE(SUM(
            CASE
                WHEN invoices.due_at < ? THEN invoices.total_amount - invoices.paid_amount
                ELSE 0
            END
        ), 0) as overdue_amount
    ', [now()->toDateString()])
            ->first();

        $summary = $summary->getAttributes();

        $invoices = $query
            ->orderBy('due_at')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'invoices' => $invoices,
            ],
        ]);
    }

}
