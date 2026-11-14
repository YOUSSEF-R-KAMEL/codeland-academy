<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('student')) {
            $studentIds = [$user->id];
        } elseif ($user->hasRole('parent')) {
            $studentIds = $user->children()
                ->pluck('users.id')
                ->all();
        } else {
            abort(403);
        }

        $invoices = Invoice::query()
            ->whereHas('enrollment', function ($query) use ($studentIds) {
                $query->whereIn('student_id', $studentIds);
            })
            ->with([
                'enrollment.student:id,name',
                'enrollment.group.course:id,title',
                'installments.payments',
            ])
            ->orderByDesc('issued_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        $studentId = (int) $invoice->enrollment()->value('student_id');

        $hasAccess = $user->hasRole('student')
            && (int) $user->id === $studentId;

        if ($user->hasRole('parent')) {
            $hasAccess = $user->children()
                ->where('users.id', $studentId)
                ->exists();
        }

        abort_unless($hasAccess, 403);

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
}
