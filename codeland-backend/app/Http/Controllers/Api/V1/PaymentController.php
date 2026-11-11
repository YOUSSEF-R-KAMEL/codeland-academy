<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(Invoice $invoice): JsonResponse
    {
        $payments = $invoice->payments()
            ->with([
                'installment',
                'recordedBy:id,name',
            ])
            ->orderByDesc('paid_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(
        StorePaymentRequest $request,
        Invoice $invoice,
        PaymentService $paymentService
    ): JsonResponse {
        $payment = $paymentService->recordPayment(
            $invoice,
            $request->validated(),
            (int) $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $payment,
        ], 201);
    }
}
