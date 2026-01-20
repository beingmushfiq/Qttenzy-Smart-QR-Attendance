<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Session;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Initiate payment for session registration
     */
    public function initiatePayment(int $userId, int $sessionId, string $gateway): array
    {
        $session = Session::findOrFail($sessionId);
        
        if (!$session->requires_payment) {
            throw new \Exception('Session does not require payment');
        }

        // Check if already registered
        $existingRegistration = Registration::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->first();

        if ($existingRegistration && $existingRegistration->isConfirmed()) {
            throw new \Exception('Already registered for this session');
        }

        $payment = Payment::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'amount' => $session->payment_amount,
            'currency' => 'BDT',
            'status' => 'pending',
            'gateway' => $gateway
        ]);

        if ($gateway === 'sslcommerz') {
            return $this->initiateSSLCommerz($payment);
        } elseif ($gateway === 'stripe') {
            return $this->initiateStripe($payment);
        }

        throw new \Exception('Invalid payment gateway');
    }

    /**
     * Initiate SSLCommerz payment
     */
    private function initiateSSLCommerz(Payment $payment): array
    {
        $session = $payment->session;
        $user = $payment->user;
        
        $transactionId = 'TXN' . $payment->id . '_' . time();
        
        $payment->update(['transaction_id' => $transactionId]);

        $data = [
            'store_id' => config('payment.sslcommerz.store_id'),
            'store_passwd' => config('payment.sslcommerz.store_password'),
            'total_amount' => $payment->amount,
            'currency' => $payment->currency,
            'tran_id' => $transactionId,
            'success_url' => config('app.url') . '/api/v1/payment/callback/sslcommerz',
            'fail_url' => config('app.url') . '/api/v1/payment/callback/sslcommerz',
            'cancel_url' => config('app.url') . '/api/v1/payment/callback/sslcommerz',
            'cus_name' => $user->name,
            'cus_email' => $user->email,
            'cus_phone' => $user->phone ?? '01700000000',
            'product_name' => $session->title,
            'product_category' => 'Session Registration',
            'product_profile' => 'general',
            'shipping_method' => 'NO',
            'num_of_item' => 1,
            'emi_option' => 0
        ];

        $mode = config('payment.sslcommerz.mode', 'sandbox');
        $url = $mode === 'sandbox' 
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';

        try {
            $response = Http::asForm()->post($url, $data);
            $responseData = $response->json();

            $payment->update([
                'gateway_response' => $responseData
            ]);

            if (isset($responseData['status']) && $responseData['status'] === 'SUCCESS') {
                return [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'gateway' => 'sslcommerz',
                    'payment_url' => $responseData['GatewayPageURL'] ?? null,
                    'transaction_id' => $transactionId
                ];
            }

            throw new \Exception('Payment initiation failed: ' . ($responseData['failedreason'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('SSLCommerz payment initiation failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()]
            ]);

            throw $e;
        }
    }

    /**
     * Initiate Stripe payment
     */
    private function initiateStripe(Payment $payment): array
    {
        // Stripe implementation would go here
        // For now, return placeholder
        throw new \Exception('Stripe integration not yet implemented');
    }

    /**
     * Handle payment webhook
     */
    public function handleWebhook(string $gateway, array $data): void
    {
        if ($gateway === 'sslcommerz') {
            $this->handleSSLCommerzWebhook($data);
        } elseif ($gateway === 'stripe') {
            $this->handleStripeWebhook($data);
        }
    }

    /**
     * Handle SSLCommerz webhook
     */
    private function handleSSLCommerzWebhook(array $data): void
    {
        $transactionId = $data['tran_id'] ?? null;
        
        if (!$transactionId) {
            Log::warning('SSLCommerz webhook missing transaction ID', $data);
            return;
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();
        
        if (!$payment) {
            Log::warning('Payment not found for transaction', ['transaction_id' => $transactionId]);
            return;
        }

        // Verify payment status
        $status = strtoupper($data['status'] ?? '');
        
        if ($status === 'VALID') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => $data
            ]);

            // Create or update registration
            Registration::updateOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'session_id' => $payment->session_id
                ],
                [
                    'payment_id' => $payment->id,
                    'status' => 'confirmed',
                    'registered_at' => now()
                ]
            );

            Log::info('Payment completed and registration confirmed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId
            ]);

        } else {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => $data
            ]);

            Log::warning('Payment failed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'status' => $status
            ]);
        }
    }

    /**
     * Handle Stripe webhook
     */
    private function handleStripeWebhook(array $data): void
    {
        // Stripe webhook implementation would go here
        Log::info('Stripe webhook received', $data);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(int $paymentId): ?Payment
    {
        return Payment::with(['user', 'session'])->find($paymentId);
    }
}

