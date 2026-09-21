<?php

namespace App\Libraries\Payment;

use Config\PaymentGateway as PaymentGatewayConfig;

/**
 * Written against Razorpay's documented Orders API (v1) — never run
 * against a real Razorpay account, since there were no credentials
 * available while building this (same caveat as LiveZohoMembersClient).
 * Razorpay was picked as the default "live" implementation because it's
 * the most commonly used gateway for Indian nonprofit/membership
 * collections; swapping to a different gateway means writing one more
 * class against this same interface, not touching anything else.
 *
 * Order creation: POST https://api.razorpay.com/v1/orders, Basic Auth with
 * key_id:key_secret, amount in paise.
 * Signature verification: HMAC-SHA256 of "{order_id}|{payment_id}" using
 * key_secret, compared to razorpay_signature — see Razorpay's "Verify
 * Payment Signature" docs.
 */
class LiveRazorpayGateway implements PaymentGatewayInterface
{
    public function __construct(private PaymentGatewayConfig $config)
    {
    }

    public function createOrder(float $amount, string $receiptRef): array
    {
        $client = \Config\Services::curlrequest();

        $response = $client->post('https://api.razorpay.com/v1/orders', [
            'auth' => [$this->config->razorpayKeyId, $this->config->razorpayKeySecret],
            'json' => [
                'amount' => (int) round($amount * 100), // paise
                'currency' => 'INR',
                'receipt' => $receiptRef,
            ],
            'http_errors' => false,
        ]);

        $body = json_decode((string) $response->getBody(), true);

        if ($response->getStatusCode() >= 300 || empty($body['id'])) {
            throw new PaymentGatewayException('Razorpay order creation failed: ' . (string) $response->getBody());
        }

        return [
            'order_id' => $body['id'],
            // The actual checkout is Razorpay's JS widget (Checkout.js) on
            // our own pay page, keyed to this order id — not a hosted URL
            // to redirect to, so this stays null like the simulated driver.
            'checkout_url' => null,
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        $orderId = (string) ($callbackData['razorpay_order_id'] ?? '');
        $paymentId = (string) ($callbackData['razorpay_payment_id'] ?? '');
        $signature = (string) ($callbackData['razorpay_signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return ['success' => false, 'order_id' => $orderId ?: null, 'payment_id' => $paymentId ?: null];
        }

        $expected = hash_hmac('sha256', "{$orderId}|{$paymentId}", $this->config->razorpayKeySecret);
        $valid = hash_equals($expected, $signature);

        return ['success' => $valid, 'order_id' => $orderId, 'payment_id' => $paymentId];
    }
}
