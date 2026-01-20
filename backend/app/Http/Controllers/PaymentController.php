<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initiate payment
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|integer|exists:sessions,id',
            'gateway' => 'required|in:sslcommerz,stripe'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $result = $this->paymentService->initiatePayment(
                $user->id,
                $request->session_id,
                $request->gateway
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get payment status
     */
    public function status($id)
    {
        $payment = $this->paymentService->getPaymentStatus($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // Check if user owns this payment
        if ($payment->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Handle payment webhook
     */
    public function webhook($gateway, Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info("Payment webhook received from {$gateway}", $data);

            $this->paymentService->handleWebhook($gateway, $data);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed'
            ]);

        } catch (\Exception $e) {
            Log::error("Payment webhook error", [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }
}
