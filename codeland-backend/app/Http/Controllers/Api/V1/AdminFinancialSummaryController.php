<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFinancialSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $summary = Invoice::query()
            ->when(
                $request->filled('from'),
                fn($query) => $query->whereDate(
                    'issued_at',
                    '>=',
                    $request->input('from')
                )
            )
            ->when(
                $request->filled('to'),
                fn($query) => $query->whereDate(
                    'issued_at',
                    '<=',
                    $request->input('to')
                )
            )
            ->selectRaw('
                COUNT(*) as total_invoices,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(total_amount - paid_amount), 0) as remaining_amount,
                COALESCE(AVG(total_amount), 0) as average_invoice_amount,
                COALESCE(AVG(paid_amount), 0) as average_paid_amount
            ')
            ->selectRaw("
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices_count,
                SUM(CASE WHEN status != 'paid' THEN 1 ELSE 0 END) as unpaid_invoices_count,
                SUM(
                    CASE
                        WHEN due_at < ? AND paid_amount < total_amount
                        THEN 1 ELSE 0
                    END
                ) as overdue_invoices_count
            ", [now()->toDateString()])
            ->first();

        $paymentsByMethod = Payment::query()
            ->where('status', 'confirmed')
            ->when(
                $request->filled('from'),
                fn($query) => $query->whereDate(
                    'paid_at',
                    '>=',
                    $request->input('from')
                )
            )
            ->when(
                $request->filled('to'),
                fn($query) => $query->whereDate(
                    'paid_at',
                    '<=',
                    $request->input('to')
                )
            )
            ->selectRaw('
                payment_method,
                COUNT(*) as payments_count,
                SUM(amount) as total_amount
            ')
            ->groupBy('payment_method')
            ->get()
            ->map(fn($payment) => [
                'payment_method' => $payment->payment_method,
                'payments_count' => (int) $payment->payments_count,
                'total_amount' => number_format(
                    (float) $payment->total_amount,
                    2,
                    '.',
                    ''
                ),
            ]);

        $paymentsByMonth = Payment::query()
            ->where('status', 'confirmed')
            ->whereNotNull('paid_at')
            ->when(
                !$request->filled('from'),
                fn($query) => $query->where(
                    'paid_at',
                    '>=',
                    now()->subMonths(5)->startOfMonth()
                )
            )
            ->when(
                $request->filled('from'),
                fn($query) => $query->whereDate(
                    'paid_at',
                    '>=',
                    $request->input('from')
                )
            )
            ->when(
                $request->filled('to'),
                fn($query) => $query->whereDate(
                    'paid_at',
                    '<=',
                    $request->input('to')
                )
            )
            ->selectRaw("
                DATE_FORMAT(paid_at, '%Y-%m') as month,
                COUNT(*) as payments_count,
                SUM(amount) as total_amount
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($payment) => [
                'month' => $payment->month,
                'payments_count' => (int) $payment->payments_count,
                'total_amount' => number_format(
                    (float) $payment->total_amount,
                    2,
                    '.',
                    ''
                ),
            ]);

        $invoicesByPlan = Invoice::query()
            ->when(
                $request->filled('from'),
                fn($query) => $query->whereDate(
                    'issued_at',
                    '>=',
                    $request->input('from')
                )
            )
            ->when(
                $request->filled('to'),
                fn($query) => $query->whereDate(
                    'issued_at',
                    '<=',
                    $request->input('to')
                )
            )
            ->selectRaw('
                payment_plan,
                COUNT(*) as invoices_count,
                SUM(total_amount) as total_amount,
                SUM(paid_amount) as paid_amount
            ')
            ->groupBy('payment_plan')
            ->get()
            ->map(fn($invoice) => [
                'payment_plan' => $invoice->payment_plan,
                'invoices_count' => (int) $invoice->invoices_count,
                'total_amount' => number_format(
                    (float) $invoice->total_amount,
                    2,
                    '.',
                    ''
                ),
                'paid_amount' => number_format(
                    (float) $invoice->paid_amount,
                    2,
                    '.',
                    ''
                ),
            ]);

        $overdueInstallments = PaymentInstallment::query()
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('status', '!=', 'paid')
            ->with('payments')
            ->get();

        $overdueInstallmentsSummary = [
            'count' => $overdueInstallments->count(),
            'remaining_amount' => number_format(
                $overdueInstallments->sum(
                    fn($installment) => (float) $installment->remaining_amount
                ),
                2,
                '.',
                ''
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'total_invoices' => (int) $summary->total_invoices,
                'total_amount' => number_format(
                    (float) $summary->total_amount,
                    2,
                    '.',
                    ''
                ),
                'paid_amount' => number_format(
                    (float) $summary->paid_amount,
                    2,
                    '.',
                    ''
                ),
                'remaining_amount' => number_format(
                    (float) $summary->remaining_amount,
                    2,
                    '.',
                    ''
                ),
                'average_invoice_amount' => number_format(
                    (float) $summary->average_invoice_amount,
                    2,
                    '.',
                    ''
                ),
                'average_paid_amount' => number_format(
                    (float) $summary->average_paid_amount,
                    2,
                    '.',
                    ''
                ),
                'paid_invoices_count' => (int) $summary->paid_invoices_count,
                'unpaid_invoices_count' => (int) $summary->unpaid_invoices_count,
                'overdue_invoices_count' => (int) $summary->overdue_invoices_count,
                'payments_by_method' => $paymentsByMethod,
                'payments_by_month' => $paymentsByMonth,
                'invoices_by_plan' => $invoicesByPlan,
                'overdue_installments' => $overdueInstallmentsSummary,
            ],
        ]);
    }
}
