<?php

namespace App\Libraries\Payment;

/**
 * No network call, no real money. Generates a fake order id; our own
 * "simulated checkout" view (`membership/pay.php`) posts straight back to
 * PaymentController::confirmSimulated with that order id, which this class
 * accepts as automatically verified — there's no external party to check a
 * signature against.
 */
class SimulatedPaymentGateway implements PaymentGatewayInterface
{
    public function createOrder(float $amount, string $receiptRef): array
    {
        return [
            'order_id' => 'SIM-' . strtoupper(bin2hex(random_bytes(6))),
            'checkout_url' => null,
        ];
    }

    public function verifyPayment(array $callbackData): array
    {
        $orderId = (string) ($callbackData['order_id'] ?? '');

        if ($orderId === '') {
            return ['success' => false, 'order_id' => null, 'payment_id' => null];
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'payment_id' => 'SIMPAY-' . strtoupper(bin2hex(random_bytes(6))),
        ];
    }
}
