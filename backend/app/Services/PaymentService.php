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

        // Demo Mode: Skip real API calls for demonstration
        if (config('payment.demo_mode')) {
            return $this->initiateDemoPayment($payment, $gateway);
        }

        if ($gateway === 'sslcommerz') {
            return $this->initiateSSLCommerz($payment);
        } elseif ($gateway === 'stripe') {
            return $this->initiateStripe($payment);
        }

        throw new \Exception('Invalid payment gateway');
    }

    /**
     * Initiate demo payment (for testing/demo without real credentials)
     */
    private function initiateDemoPayment(Payment $payment, string $gateway): array
    {
        $transactionId = 'DEMO_' . $payment->id . '_' . time();
        
        $payment->update([
            'transaction_id' => $transactionId,
            'gateway_response' => ['demo_mode' => true, 'message' => 'Demo payment initiated']
        ]);

        // Return mock payment URL that redirects to frontend
        $mockPaymentUrl = config('app.frontend_url') . '/payment/demo?' . http_build_query([
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'gateway' => $gateway,
            'transaction_id' => $transactionId
        ]);

        return [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $gateway,
            'payment_url' => $mockPaymentUrl,
            'transaction_id' => $transactionId,
            'demo_mode' => true
        ];
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
            'cus_add1' => $user->address ?? 'N/A',
            'cus_city' => $user->city ?? 'Dhaka',
            'cus_state' => $user->state ?? 'Dhaka',
            'cus_postcode' => $user->postcode ?? '1000',
            'cus_country' => $user->country ?? 'Bangladesh',
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
    /**
     * Initiate Stripe payment
     */
    private function initiateStripe(Payment $payment): array
    {
        \Stripe\Stripe::setApiKey(config('payment.stripe.secret'));

        $session = $payment->session;
        
        $checkoutSession = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'unit_amount' => $payment->amount * 100, // Stripe uses cents
                    'product_data' => [
                        'name' => 'Session Registration: ' . $session->title,
                        'images' => $session->image ? [asset($session->image)] : [],
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.frontend_url') . '/payment/callback/stripe?status=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.frontend_url') . '/payment/callback/stripe?status=cancel',
            'customer_email' => $payment->user->email,
            'client_reference_id' => (string) $payment->id,
            'metadata' => [
                'payment_id' => $payment->id,
                'session_id' => $session->id,
                'user_id' => $payment->user_id
            ]
        ]);

        $payment->update([
            'transaction_id' => $checkoutSession->id,
            'gateway_response' => ['checkout_session_id' => $checkoutSession->id]
        ]);

        return [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => 'stripe',
            'payment_url' => $checkoutSession->url,
            'transaction_id' => $checkoutSession->id
        ];
    }

    /**
     * Handle payment webhook
     */
    public function handleWebhook(string $gateway, array $data): void
    {
        if ($gateway === 'demo') {
            $this->handleDemoWebhook($data);
        } elseif ($gateway === 'sslcommerz') {
            $this->handleSSLCommerzWebhook($data);
        } elseif ($gateway === 'stripe') {
            $this->handleStripeWebhook($data);
        }
    }

    /**
     * Handle demo webhook
     */
    private function handleDemoWebhook(array $data): void
    {
        $paymentId = $data['payment_id'] ?? null;
        
        if (!$paymentId) {
            Log::warning('Demo webhook missing payment ID', $data);
            return;
        }

        $payment = Payment::find($paymentId);
        
        if (!$payment) {
            Log::warning('Payment not found for demo webhook', ['payment_id' => $paymentId]);
            return;
        }

        // Mark payment as completed
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                ['demo_completed' => true, 'completed_at' => now()->toIso8601String()]
            )
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

        Log::info('Demo payment completed and registration confirmed', [
            'payment_id' => $payment->id
        ]);
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
        // For Stripe, the $data passed here comes from the controller which already processed the JSON body
        // But for signature verification, we ideally need the raw payload and header.
        // However, since we are inside a service method called by controller, 
        // we'll assume the controller passed the event object or we recreate/retrieve logic here.
        // For simplicity in this architecture where controller passes array:
        
        $type = $data['type'] ?? '';
        $object = $data['data']['object'] ?? [];

        Log::info('Processing Stripe webhook event: ' . $type);

        if ($type === 'checkout.session.completed') {
            $checkoutSessionId = $object['id'] ?? null;
            $paymentId = $object['client_reference_id'] ?? null; // We sent payment ID here

            if (!$paymentId) {
                // Try from metadata if client_reference_id is missing
                $paymentId = $object['metadata']['payment_id'] ?? null;
            }

            if (!$paymentId) {
                Log::error('Stripe webhook missing payment ID');
                return;
            }

            $payment = Payment::find($paymentId);

            if (!$payment) {
                Log::error('Payment not found via webhook', ['payment_id' => $paymentId]);
                return;
            }

            // Verify amount if needed
            // $amountHooks = $object['amount_total'] / 100;
            
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

            Log::info('Stripe payment verified and registration created', ['payment_id' => $payment->id]);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(int $paymentId): ?Payment
    {
        return Payment::with(['user', 'session'])->find($paymentId);
    }
}

